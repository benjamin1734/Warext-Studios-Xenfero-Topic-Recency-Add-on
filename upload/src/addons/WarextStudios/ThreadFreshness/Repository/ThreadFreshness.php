<?php

namespace WarextStudios\ThreadFreshness\Repository;

use WarextStudios\ThreadFreshness\Entity\ThreadState;
use WarextStudios\ThreadFreshness\Util\FailureReason;
use WarextStudios\ThreadFreshness\Util\ModeratorStatus;
use WarextStudios\ThreadFreshness\Util\Revalidation;
use WarextStudios\ThreadFreshness\Util\StatusCalculator;
use WarextStudios\ThreadFreshness\Util\VersionSummary;
use XF\Entity\Thread;
use XF\Mvc\Entity\Repository;

class ThreadFreshness extends Repository
{
    public function findVotesForThread(int $threadId)
    {
        return $this->finder('WarextStudios\ThreadFreshness:Vote')
            ->where('thread_id', $threadId)
            ->order('vote_date', 'DESC');
    }

    public function findStateForThread(int $threadId): ?ThreadState
    {
        return $this->em->find('WarextStudios\ThreadFreshness:ThreadState', $threadId);
    }

    public function getOrCreateState(int $threadId): ThreadState
    {
        $state = $this->findStateForThread($threadId);
        if ($state)
        {
            return $state;
        }

        $state = $this->em->create('WarextStudios\ThreadFreshness:ThreadState');
        $state->thread_id = $threadId;
        return $state;
    }

    public function withThreadLock(int $threadId, callable $callback)
    {
        $db = $this->db();
        $db->beginTransaction();

        try
        {
            $db->query(
                'INSERT IGNORE INTO xf_wrxt_thread_freshness_state (thread_id) VALUES (?)',
                $threadId
            );
            $db->query(
                'SELECT thread_id FROM xf_wrxt_thread_freshness_state WHERE thread_id = ? FOR UPDATE',
                $threadId
            );

            $result = $callback();
            $db->commit();

            return $result;
        }
        catch (\Throwable $e)
        {
            $db->rollback();
            throw $e;
        }
    }

    public function getStatusRules(): array
    {
        return StatusCalculator::normalizeRules((array)(\XF::options()->wrxtFreshnessRules ?? []));
    }

    public function preloadThreadData($threads, $stickyThreads = null): void
    {
        $threadMap = [];

        foreach ([$threads, $stickyThreads] as $collection)
        {
            if (!$collection)
            {
                continue;
            }

            foreach ($collection as $thread)
            {
                $threadId = (int)$thread->thread_id;
                if ($threadId > 0)
                {
                    $threadMap[$threadId] = $thread;
                }
            }
        }

        if (!$threadMap)
        {
            return;
        }

        $ids = array_keys($threadMap);
        $this->finder('WarextStudios\ThreadFreshness:ThreadState')
            ->where('thread_id', $ids)
            ->fetch();

        $meaningfulIds = [];
        foreach ($threadMap as $threadId => $thread)
        {
            if ($thread->Forum && $thread->Forum->getWrxtFreshnessAgeMode() === 'meaningful')
            {
                $meaningfulIds[] = $threadId;
            }
            else
            {
                $thread->setWrxtFreshnessReferenceDate((int)$thread->last_post_date);
            }
        }

        if (!$meaningfulIds)
        {
            foreach ($threadMap as $thread)
            {
                $thread->setWrxtFreshnessListDataPreloaded();
            }
            return;
        }

        $rows = $this->db()->fetchPairs(
            'SELECT p.thread_id, MAX(GREATEST(p.post_date, p.last_edit_date)) AS meaningful_date
            FROM xf_post p
            INNER JOIN xf_thread t ON t.thread_id = p.thread_id
            LEFT JOIN xf_thread_question tq ON tq.thread_id = t.thread_id
            WHERE p.thread_id IN (' . $this->db()->quote($meaningfulIds) . ")
                AND p.message_state = 'visible'
                AND (
                    p.post_id = t.first_post_id
                    OR (t.user_id > 0 AND p.user_id = t.user_id)
                    OR (tq.solution_post_id > 0 AND p.post_id = tq.solution_post_id)
                )
            GROUP BY p.thread_id"
        );

        foreach ($meaningfulIds as $threadId)
        {
            $thread = $threadMap[$threadId];
            $fallback = (int)$thread->post_date ?: (int)$thread->last_post_date;
            $date = (int)($rows[$threadId] ?? $fallback);
            $thread->setWrxtFreshnessReferenceDate($date);
        }

        foreach ($threadMap as $thread)
        {
            $thread->setWrxtFreshnessListDataPreloaded();
        }
    }

    public function getMeaningfulDateForThread(Thread $thread): int
    {
        if (!$thread->thread_id)
        {
            return 0;
        }

        $date = (int)$this->db()->fetchOne(
            "SELECT MAX(GREATEST(p.post_date, p.last_edit_date))
            FROM xf_post p
            LEFT JOIN xf_thread_question tq ON tq.thread_id = p.thread_id
            WHERE p.thread_id = ?
                AND p.message_state = 'visible'
                AND (
                    p.post_id = ?
                    OR (? > 0 AND p.user_id = ?)
                    OR (tq.solution_post_id > 0 AND p.post_id = tq.solution_post_id)
                )",
            [
                (int)$thread->thread_id,
                (int)$thread->first_post_id,
                (int)$thread->user_id,
                (int)$thread->user_id
            ]
        );

        return $date > 0 ? $date : (int)($thread->post_date ?: $thread->last_post_date);
    }

    public function getVersionSummaryForThread(int $threadId, int $limit = 8, int $minDate = 0): array
    {
        $votes = [];

        foreach ($this->findVotesForThread($threadId)->fetch() as $vote)
        {
            $voteDate = max((int)$vote->vote_date, (int)$vote->updated_date);
            if ($voteDate < $minDate)
            {
                continue;
            }

            $votes[] = [
                'vote' => (int)$vote->vote,
                'vote_date' => $voteDate,
                'version' => (string)$vote->version
            ];
        }

        return VersionSummary::summarize($votes, \XF::$time, $limit, $this->getStatusRules());
    }

    public function getFailureReasonSummaryForThread(int $threadId, int $minDate = 0): array
    {
        $rows = $this->db()->fetchPairs(
            "SELECT reason, COUNT(*)
            FROM xf_wrxt_thread_freshness_vote
            WHERE thread_id = ?
                AND vote = -1
                AND GREATEST(vote_date, updated_date) >= ?
                AND reason <> ''
            GROUP BY reason
            ORDER BY COUNT(*) DESC, reason",
            [$threadId, max(0, $minDate)]
        );

        $summary = [];
        foreach ($rows as $reason => $count)
        {
            $reason = FailureReason::normalize((string)$reason);
            if ($reason === '')
            {
                continue;
            }
            $summary[$reason] = (int)$count;
        }

        return $summary;
    }

    public function getAlternativeSuggestionsForThread(int $threadId, int $minDate = 0, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $rows = $this->db()->fetchAll(
            $this->db()->limit(
                "SELECT alternative_thread_id, COUNT(*) AS total, MAX(GREATEST(vote_date, updated_date)) AS last_date
                FROM xf_wrxt_thread_freshness_vote
                WHERE thread_id = ?
                    AND vote = -1
                    AND alternative_thread_id > 0
                    AND GREATEST(vote_date, updated_date) >= ?
                GROUP BY alternative_thread_id
                ORDER BY total DESC, last_date DESC",
                $limit * 2
            ),
            [$threadId, max(0, $minDate)]
        );

        if (!$rows)
        {
            return [];
        }

        $candidateIds = array_values(array_unique(array_map(
            static fn(array $row): int => (int)$row['alternative_thread_id'],
            $rows
        )));
        $threads = $this->finder('XF:Thread')
            ->where('thread_id', $candidateIds)
            ->with('Forum')
            ->fetch();
        $threadMap = [];
        foreach ($threads as $thread)
        {
            $threadMap[(int)$thread->thread_id] = $thread;
        }

        $sourceThread = $this->em->find('XF:Thread', $threadId);
        $suggestions = [];
        foreach ($rows as $row)
        {
            $thread = $threadMap[(int)$row['alternative_thread_id']] ?? null;
            if (
                !$thread
                || (int)$thread->thread_id === $threadId
                || $thread->discussion_state !== 'visible'
                || $thread->discussion_type === 'redirect'
                || !$thread->canView()
                || ($sourceThread && (int)$thread->post_date < (int)$sourceThread->post_date)
            )
            {
                continue;
            }

            $suggestions[] = [
                'thread' => $thread,
                'total' => (int)$row['total'],
                'last_date' => (int)$row['last_date']
            ];

            if (count($suggestions) >= $limit)
            {
                break;
            }
        }

        return $suggestions;
    }

    public function getEffectiveStatus(?ThreadState $state): string
    {
        if (!$state)
        {
            return 'unverified';
        }

        return ModeratorStatus::effective((string)$state->status, (string)$state->moderator_status);
    }

    public function logStatusChange(
        int $threadId,
        string $oldStatus,
        string $newStatus,
        string $triggerType,
        int $userId = 0
    ): void
    {
        if ($oldStatus === $newStatus)
        {
            return;
        }

        $log = $this->em->create('WarextStudios\ThreadFreshness:StatusLog');
        $log->thread_id = $threadId;
        $log->old_status = $oldStatus;
        $log->new_status = $newStatus;
        $log->trigger_type = $triggerType;
        $log->user_id = $userId;
        $log->save();
    }

    public function notifyStatusChange(
        int $threadId,
        string $oldStatus,
        string $newStatus,
        string $triggerType,
        int $userId = 0
    ): void
    {
        if ($triggerType === 'moderator_prepare')
        {
            return;
        }

        \XF::app()->service(
            'WarextStudios\ThreadFreshness:ThreadFreshness\Notifier'
        )->notify($threadId, $oldStatus, $newStatus, $triggerType, $userId);
    }

    public function recalculateThread(int $threadId, string $triggerType = 'system', int $userId = 0): ThreadState
    {
        $thread = $this->em->find('XF:Thread', $threadId);
        $referenceDate = $thread ? (int)$thread->getWrxtFreshnessReferenceDate() : 0;
        $votes = [];

        foreach ($this->findVotesForThread($threadId)->fetch() as $vote)
        {
            $voteDate = max((int)$vote->vote_date, (int)$vote->updated_date);
            if ($voteDate < $referenceDate)
            {
                continue;
            }

            $votes[] = [
                'vote' => (int)$vote->vote,
                'vote_date' => $voteDate
            ];
        }

        $result = StatusCalculator::calculate($votes, \XF::$time, $this->getStatusRules());
        $state = $this->getOrCreateState($threadId);
        $oldStatus = $this->getEffectiveStatus($state);
        $previousReferenceDate = (int)$state->reference_date;
        $lastVerifiedDate = (int)$state->last_verified_date;

        if ($referenceDate > 0 && $previousReferenceDate > 0 && $referenceDate !== $previousReferenceDate)
        {
            $state->moderator_status = '';
            $state->moderator_user_id = 0;
            $state->moderator_date = 0;
        }
        if ($referenceDate > $lastVerifiedDate)
        {
            $lastVerifiedDate = 0;
            $state->last_verified_date = 0;
        }
        if ($referenceDate > (int)$state->owner_claim_date)
        {
            $state->owner_claim_date = 0;
        }

        $revalidateDays = max(1, (int)(\XF::options()->wrxtFreshnessRevalidateDays ?? 180));
        $verificationBase = $lastVerifiedDate > 0
            ? $lastVerifiedDate
            : (int)$result['last_vote_date'];
        if (Revalidation::shouldRevalidate(
            $result['status'],
            $verificationBase,
            (int)$result['last_vote_date'],
            $revalidateDays,
            \XF::$time
        ))
        {
            $result['status'] = 'revalidating';
        }

        $state->bulkSet($result);
        $state->reference_date = $referenceDate;
        $state->last_calculated_date = \XF::$time;

        if (
            in_array($result['status'], ['current', 'likely_current'], true)
            && $result['last_vote_date'] > $lastVerifiedDate
        )
        {
            $state->last_verified_date = $result['last_vote_date'];
        }

        $state->save();

        $newStatus = $this->getEffectiveStatus($state);
        if ($triggerType !== 'moderator_prepare')
        {
            $this->logStatusChange($threadId, $oldStatus, $newStatus, $triggerType, $userId);
            $this->notifyStatusChange($threadId, $oldStatus, $newStatus, $triggerType, $userId);
        }

        return $state;
    }

    public function recalculateThreadSafely(
        int $threadId,
        string $triggerType = 'system',
        int $userId = 0
    ): ThreadState
    {
        return $this->withThreadLock(
            $threadId,
            fn() => $this->recalculateThread($threadId, $triggerType, $userId)
        );
    }

    public function getDashboardStats(): array
    {
        $db = $this->db();
        $rows = $db->fetchPairs(
            "SELECT
                CASE
                    WHEN s.moderator_status = 'current' THEN 'current'
                    WHEN s.moderator_status = 'not_working' THEN 'not_working'
                    WHEN s.moderator_status = 'review' THEN 'questionable'
                    ELSE s.status
                END AS effective_status,
                COUNT(*) AS total
            FROM xf_wrxt_thread_freshness_state s
            INNER JOIN xf_thread t ON t.thread_id = s.thread_id
            INNER JOIN xf_forum f ON f.node_id = t.node_id
            WHERE t.discussion_state = 'visible'
                AND f.wrxt_freshness_enabled = 1
            GROUP BY effective_status"
        );

        $stats = [
            'total' => array_sum(array_map('intval', $rows)),
            'active_threads' => (int)$db->fetchOne(
                "SELECT COUNT(*)
                FROM xf_thread t
                INNER JOIN xf_forum f ON f.node_id = t.node_id
                WHERE t.discussion_state = 'visible' AND f.wrxt_freshness_enabled = 1"
            ),
            'current' => 0,
            'likely_current' => 0,
            'mixed' => 0,
            'questionable' => 0,
            'not_working' => 0,
            'revalidating' => 0,
            'unverified' => 0,
            'enabled_forums' => (int)$db->fetchOne(
                'SELECT COUNT(*) FROM xf_forum WHERE wrxt_freshness_enabled = 1'
            ),
            'votes' => (int)$db->fetchOne(
                "SELECT COUNT(*)
                FROM xf_wrxt_thread_freshness_vote v
                INNER JOIN xf_thread t ON t.thread_id = v.thread_id
                INNER JOIN xf_forum f ON f.node_id = t.node_id
                WHERE t.discussion_state = 'visible' AND f.wrxt_freshness_enabled = 1"
            ),
            'alternative_suggestions' => (int)$db->fetchOne(
                "SELECT COUNT(*)
                FROM xf_wrxt_thread_freshness_vote v
                INNER JOIN xf_thread t ON t.thread_id = v.thread_id
                INNER JOIN xf_forum f ON f.node_id = t.node_id
                WHERE v.alternative_thread_id > 0
                    AND t.discussion_state = 'visible'
                    AND f.wrxt_freshness_enabled = 1"
            )
        ];

        foreach ($rows as $status => $total)
        {
            if (array_key_exists($status, $stats))
            {
                $stats[$status] = (int)$total;
            }
        }

        return $stats;
    }

    public function getCriticalThreads(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $rows = $this->db()->fetchAll(
            $this->db()->limit(
                "SELECT s.thread_id, s.negative_count, s.vote_count, s.last_calculated_date
                FROM xf_wrxt_thread_freshness_state s
                INNER JOIN xf_thread t ON t.thread_id = s.thread_id
                INNER JOIN xf_forum f ON f.node_id = t.node_id
                WHERE t.discussion_state = 'visible'
                    AND f.wrxt_freshness_enabled = 1
                    AND (
                        s.status IN ('questionable', 'not_working', 'revalidating')
                        OR s.moderator_status IN ('review', 'not_working')
                    )
                ORDER BY s.negative_count DESC, s.last_calculated_date ASC",
                min(1000, $limit * 5)
            )
        );

        if (!$rows)
        {
            return [];
        }

        $ids = array_values(array_unique(array_map(static fn(array $row): int => (int)$row['thread_id'], $rows)));
        $threads = $this->finder('XF:Thread')
            ->where('thread_id', $ids)
            ->with('Forum')
            ->with('WrxtFreshnessState')
            ->fetch();
        $this->preloadThreadData($threads);

        $threadMap = [];
        foreach ($threads as $thread)
        {
            $threadMap[(int)$thread->thread_id] = $thread;
        }

        $results = [];
        foreach ($rows as $row)
        {
            $thread = $threadMap[(int)$row['thread_id']] ?? null;
            if (!$thread || !$thread->isWrxtFreshnessEligible())
            {
                continue;
            }

            $effective = $thread->getWrxtFreshnessDisplayStatus();
            if (!in_array($effective, ['questionable', 'not_working', 'revalidating'], true))
            {
                continue;
            }

            $row['thread'] = $thread;
            $row['effective_status'] = $effective;
            $results[] = $row;

            if (count($results) >= $limit)
            {
                break;
            }
        }

        return $results;
    }

    public function getRecentNegativeFeedback(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $voteIds = $this->db()->fetchAllColumn(
            $this->db()->limit(
                "SELECT v.vote_id
                FROM xf_wrxt_thread_freshness_vote v
                INNER JOIN xf_thread t ON t.thread_id = v.thread_id
                INNER JOIN xf_forum f ON f.node_id = t.node_id
                WHERE v.vote = -1
                    AND t.discussion_state = 'visible'
                    AND f.wrxt_freshness_enabled = 1
                ORDER BY GREATEST(v.vote_date, v.updated_date) DESC, v.vote_id DESC",
                min(500, $limit * 5)
            )
        );

        if (!$voteIds)
        {
            return [];
        }

        $votes = $this->finder('WarextStudios\ThreadFreshness:Vote')
            ->where('vote_id', $voteIds)
            ->with('Thread.Forum')
            ->with('User')
            ->fetch();

        $threadMap = [];
        $voteMap = [];
        foreach ($votes as $vote)
        {
            $voteMap[(int)$vote->vote_id] = $vote;
            if ($vote->Thread)
            {
                $threadMap[(int)$vote->Thread->thread_id] = $vote->Thread;
            }
        }
        $this->preloadThreadData($threadMap);

        $results = [];
        foreach ($voteIds as $voteId)
        {
            $vote = $voteMap[(int)$voteId] ?? null;
            if (!$vote || !$vote->Thread || !$vote->Thread->isWrxtFreshnessEligible())
            {
                continue;
            }

            $voteDate = max((int)$vote->vote_date, (int)$vote->updated_date);
            if ($voteDate < $vote->Thread->getWrxtFreshnessReferenceDate())
            {
                continue;
            }

            $results[] = $vote;
            if (count($results) >= $limit)
            {
                break;
            }
        }

        return $results;
    }

    public function searchVerifiedThreads(string $query, string $status, int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
        $where = [
            "t.discussion_state = 'visible'",
            'f.wrxt_freshness_enabled = 1'
        ];
        $params = [];

        if ($query !== '')
        {
            $where[] = 't.title LIKE ?';
            $params[] = '%' . $query . '%';
        }

        $results = [];
        $offset = 0;
        $batch = 400;
        $maxScan = 5000;

        while (count($results) < $limit && $offset < $maxScan)
        {
            $sql = "SELECT t.thread_id
                FROM xf_thread t
                INNER JOIN xf_forum f ON f.node_id = t.node_id
                LEFT JOIN xf_wrxt_thread_freshness_state s ON s.thread_id = t.thread_id
                WHERE " . implode(' AND ', $where) . '
                ORDER BY COALESCE(s.last_verified_date, 0) DESC, t.last_post_date DESC';

            $rows = $this->db()->fetchAll(
                $this->db()->limit($sql, $batch, $offset),
                $params
            );

            if (!$rows)
            {
                break;
            }

            $offset += count($rows);
            $ids = array_values(array_unique(array_map(
                static fn(array $row): int => (int)$row['thread_id'],
                $rows
            )));

            $threads = $this->finder('XF:Thread')
                ->where('thread_id', $ids)
                ->with('Forum')
                ->with('WrxtFreshnessState')
                ->fetch();
            $this->preloadThreadData($threads);

            $threadMap = [];
            foreach ($threads as $thread)
            {
                $threadMap[(int)$thread->thread_id] = $thread;
            }

            foreach ($rows as $row)
            {
                $thread = $threadMap[(int)$row['thread_id']] ?? null;
                if (!$thread || !$thread->canView() || !$thread->isWrxtFreshnessEligible())
                {
                    continue;
                }

                $effective = $thread->getWrxtFreshnessDisplayStatus();
                if ($status !== 'all' && $effective !== $status)
                {
                    continue;
                }

                $state = $thread->getWrxtFreshnessState();
                $stateCurrent = $thread->isWrxtFreshnessStateCurrent();
                $results[] = [
                    'thread' => $thread,
                    'effective_status' => $effective,
                    'score' => $stateCurrent && $state ? (float)$state->score : 0,
                    'vote_count' => $stateCurrent && $state ? (int)$state->vote_count : 0,
                    'positive_count' => $stateCurrent && $state ? (int)$state->positive_count : 0,
                    'negative_count' => $stateCurrent && $state ? (int)$state->negative_count : 0,
                    'last_verified_date' => $stateCurrent && $state ? (int)$state->last_verified_date : 0
                ];

                if (count($results) >= $limit)
                {
                    break 2;
                }
            }

            if (count($rows) < $batch)
            {
                break;
            }
        }

        return $results;
    }

    public function cleanupOrphans(int $limit = 1000): void
    {
        $limit = max(1, min(5000, $limit));
        $db = $this->db();

        $orphanVotes = $db->fetchAll(
            $db->limit(
                'SELECT v.vote_id, v.thread_id
                FROM xf_wrxt_thread_freshness_vote v
                LEFT JOIN xf_thread t ON t.thread_id = v.thread_id
                LEFT JOIN xf_user u ON u.user_id = v.user_id
                WHERE t.thread_id IS NULL OR u.user_id IS NULL
                ORDER BY v.vote_id',
                $limit
            )
        );

        if ($orphanVotes)
        {
            $voteIds = array_values(array_unique(array_map(
                static fn(array $row): int => (int)$row['vote_id'],
                $orphanVotes
            )));
            $affectedThreadIds = array_values(array_unique(array_filter(array_map(
                static fn(array $row): int => (int)$row['thread_id'],
                $orphanVotes
            ))));

            $db->delete(
                'xf_wrxt_thread_freshness_vote',
                'vote_id IN (' . $db->quote($voteIds) . ')'
            );

            if ($affectedThreadIds)
            {
                $db->update(
                    'xf_wrxt_thread_freshness_state',
                    ['last_calculated_date' => 0],
                    'thread_id IN (' . $db->quote($affectedThreadIds) . ')'
                );
            }
        }

        $logIds = $db->fetchAllColumn(
            $db->limit(
                'SELECT l.log_id
                FROM xf_wrxt_thread_freshness_log l
                LEFT JOIN xf_thread t ON t.thread_id = l.thread_id
                WHERE t.thread_id IS NULL
                ORDER BY l.log_id',
                $limit
            )
        );

        if ($logIds)
        {
            $db->delete(
                'xf_wrxt_thread_freshness_log',
                'log_id IN (' . $db->quote($logIds) . ')'
            );
        }

        $threadIds = $db->fetchAllColumn(
            $db->limit(
                'SELECT s.thread_id
                FROM xf_wrxt_thread_freshness_state s
                LEFT JOIN xf_thread t ON t.thread_id = s.thread_id
                WHERE t.thread_id IS NULL
                ORDER BY s.thread_id',
                $limit
            )
        );

        if ($threadIds)
        {
            $db->delete(
                'xf_wrxt_thread_freshness_state',
                'thread_id IN (' . $db->quote($threadIds) . ')'
            );
        }

        $replacementOwners = $db->fetchAllColumn(
            $db->limit(
                "SELECT s.thread_id
                FROM xf_wrxt_thread_freshness_state s
                INNER JOIN xf_thread source ON source.thread_id = s.thread_id
                LEFT JOIN xf_thread r ON r.thread_id = s.replacement_thread_id
                WHERE s.replacement_thread_id > 0
                    AND (
                        r.thread_id IS NULL
                        OR r.thread_id = source.thread_id
                        OR r.discussion_state <> 'visible'
                        OR r.discussion_type = 'redirect'
                        OR r.post_date < source.post_date
                    )
                ORDER BY s.thread_id",
                $limit
            )
        );

        if ($replacementOwners)
        {
            $db->update(
                'xf_wrxt_thread_freshness_state',
                [
                    'replacement_thread_id' => 0,
                    'replacement_user_id' => 0,
                    'replacement_date' => 0
                ],
                'thread_id IN (' . $db->quote($replacementOwners) . ')'
            );
        }

        $db->query(
            'UPDATE xf_wrxt_thread_freshness_state s
            LEFT JOIN xf_user u ON u.user_id = s.moderator_user_id
            SET s.moderator_user_id = 0
            WHERE s.moderator_user_id > 0 AND u.user_id IS NULL'
        );
        $db->query(
            'UPDATE xf_wrxt_thread_freshness_state s
            LEFT JOIN xf_user u ON u.user_id = s.replacement_user_id
            SET s.replacement_user_id = 0
            WHERE s.replacement_user_id > 0 AND u.user_id IS NULL'
        );
        $db->query(
            "UPDATE xf_wrxt_thread_freshness_vote v
            INNER JOIN xf_thread source ON source.thread_id = v.thread_id
            LEFT JOIN xf_thread alternative ON alternative.thread_id = v.alternative_thread_id
            SET v.alternative_thread_id = 0
            WHERE v.alternative_thread_id > 0
                AND (
                    alternative.thread_id IS NULL
                    OR alternative.thread_id = source.thread_id
                    OR alternative.discussion_state <> 'visible'
                    OR alternative.discussion_type = 'redirect'
                    OR alternative.post_date < source.post_date
                )"
        );
    }
}
