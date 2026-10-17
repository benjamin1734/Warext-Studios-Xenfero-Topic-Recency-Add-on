<?php
declare(strict_types=1);

namespace XF\Mvc\Entity {
    class Repository
    {
        protected $em;
        private $db;
        private $fakeFinder;
        public function __construct($db, $em, $finder)
        {
            $this->db = $db;
            $this->em = $em;
            $this->fakeFinder = $finder;
        }
        protected function db() { return $this->db; }
        protected function finder($entity) {
            if ($entity !== 'XF:Thread') throw new \RuntimeException('Unexpected finder ' . $entity);
            return $this->fakeFinder;
        }
    }
}
namespace {
    final class FakeThread
    {
        public int $thread_id;
        public string $discussion_state;
        public string $discussion_type;
        public int $post_date;
        private bool $visible;
        public function __construct(int $id, int $date, bool $visible = true, string $state = 'visible', string $type = 'discussion')
        {
            $this->thread_id = $id; $this->post_date = $date;
            $this->visible = $visible; $this->discussion_state = $state; $this->discussion_type = $type;
        }
        public function canView(): bool { return $this->visible; }
    }
    final class FakeDb
    {
        public array $rows;
        public int $limit = 0;
        public function __construct(array $rows) { $this->rows = $rows; }
        public function limit(string $sql, int $limit): string { $this->limit = $limit; return $sql; }
        public function fetchAll(string $sql, array $args): array
        {
            if ($args !== [9, 0] || !str_contains($sql, 'GROUP BY alternative_thread_id'))
            {
                throw new \RuntimeException('Unexpected SQL or arguments');
            }
            return $this->rows;
        }
    }
    final class FakeEntityManager
    {
        public array $finds = [];
        public function find($entity, $id)
        {
            $this->finds[] = [$entity, $id];
            if ($entity !== 'XF:Thread' || $id !== 9)
            {
                throw new \RuntimeException('N+1 per-candidate EntityManager::find call detected');
            }
            return new FakeThread(9, 100);
        }
    }
    final class FakeFinder
    {
        public int $fetches = 0;
        public array $where = [];
        public array $with = [];
        private array $threads;
        public function __construct(array $threads) { $this->threads = $threads; }
        public function where($name, $ids): self { $this->where = [$name, $ids]; return $this; }
        public function with(string $name): self { $this->with[] = $name; return $this; }
        public function fetch(): array { $this->fetches++; return $this->threads; }
    }
    require __DIR__ . '/../upload/src/addons/WarextStudios/ThreadFreshness/Repository/ThreadFreshness.php';
    $rows = [];
    foreach ([2, 3, 4, 5, 9, 6] as $i => $id)
    {
        $rows[] = ['alternative_thread_id' => $id, 'total' => 10 - $i, 'last_date' => 200 - $i];
    }
    $db = new FakeDb($rows);
    $em = new FakeEntityManager();
    $finder = new FakeFinder([
        new FakeThread(5, 200),
        new FakeThread(3, 180, false),
        new FakeThread(2, 150),
        new FakeThread(4, 50),
        new FakeThread(6, 160, true, 'visible', 'redirect'),
        new FakeThread(9, 100)
    ]);
    $repository = new \WarextStudios\ThreadFreshness\Repository\ThreadFreshness($db, $em, $finder);
    $results = $repository->getAlternativeSuggestionsForThread(9, 0, 2);
    if (array_map(static fn(array $row): int => $row['thread']->thread_id, $results) !== [2, 5])
    {
        throw new \RuntimeException('Ranking/filter order changed');
    }
    if ($finder->fetches !== 1 || $finder->where[0] !== 'thread_id' || count($finder->where[1]) !== 6 || $finder->with !== ['Forum'])
    {
        throw new \RuntimeException('Thread candidates are not loaded as one eager batch');
    }
    if ($em->finds !== [['XF:Thread', 9]] || $db->limit !== 4)
    {
        throw new \RuntimeException('N+1 retrieval or query limit regression');
    }

    $emptyDb = new FakeDb([]);
    $emptyEm = new FakeEntityManager();
    $emptyFinder = new FakeFinder([]);
    $emptyRepo = new \WarextStudios\ThreadFreshness\Repository\ThreadFreshness($emptyDb, $emptyEm, $emptyFinder);
    if ($emptyRepo->getAlternativeSuggestionsForThread(9) !== [] || $emptyFinder->fetches !== 0 || $emptyEm->finds)
    {
        throw new \RuntimeException('Empty alternative suggestion result performed unnecessary lookups');
    }
    echo "Alternative suggestion batching and ordering regression: OK\n";
}
