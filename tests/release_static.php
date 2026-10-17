<?php

$root = dirname(__DIR__);
$addon = $root . '/upload/src/addons/WarextStudios/ThreadFreshness';

$addonJson = json_decode((string)file_get_contents($addon . '/addon.json'), true, 512, JSON_THROW_ON_ERROR);
$version = (string)($addonJson['version_string'] ?? '');
if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $m)
    || (int)($addonJson['version_id'] ?? 0) !== ((int)$m[1] * 1000000 + (int)$m[2] * 10000 + (int)$m[3] * 10))
{
    throw new RuntimeException('addon.json version is invalid');
}
if (($addonJson['options'] ?? '') !== 'thread-freshness/settings')
{
    throw new RuntimeException('Add-on options link is missing');
}

$adminNav = (string)file_get_contents($addon . '/_data/admin_navigation.xml');
foreach (['wrxtThreadFreshnessDashboard', 'wrxtThreadFreshnessSettings', 'thread-freshness/settings'] as $needle)
{
    if (!str_contains($adminNav, $needle))
    {
        throw new RuntimeException('Admin navigation entry is missing: ' . $needle);
    }
}

$dashboard = (string)file_get_contents($addon . '/Admin/Controller/Dashboard.php');
foreach (['actionSettings', "assertAdminPermission('option')", 'options/groups/wrxtThreadFreshness'] as $needle)
{
    if (!str_contains($dashboard, $needle))
    {
        throw new RuntimeException('Settings controller contract is missing: ' . $needle);
    }
}

$phrases = (string)file_get_contents($addon . '/_data/phrases.xml');
foreach (['admin_navigation.wrxtThreadFreshnessDashboard', 'admin_navigation.wrxtThreadFreshnessSettings'] as $needle)
{
    if (!str_contains($phrases, $needle))
    {
        throw new RuntimeException('Settings navigation phrase is missing: ' . $needle);
    }
}

$mods = (string)file_get_contents($addon . '/_data/template_modifications.xml');
if (str_contains($mods, '$filters|replace'))
{
    throw new RuntimeException('String replace filter bug still exists');
}
if (!str_contains($mods, 'array_merge($filters'))
{
    throw new RuntimeException('Forum filter does not preserve filter array');
}
if (!str_contains($mods, 'wrxt_freshness_age_mode'))
{
    throw new RuntimeException('Age mode UI is missing');
}
if (preg_match('/\.wrxtFreshness(?:Get|Is|Has|Can|Set)[A-Za-z0-9_]*\s*\(/', $mods))
{
    throw new RuntimeException('Template contains a XenForo 2.3.7-incompatible custom method name');
}
foreach (['.getWrxtFreshnessState()', '.isWrxtFreshnessEligible()', '.canWrxtFreshnessVote()', '.hasWrxtFreshnessOwnerClaim()'] as $needle)
{
    if (!str_contains($mods, $needle))
    {
        throw new RuntimeException('Read-only template method contract is missing: ' . $needle);
    }
}

$options = (string)file_get_contents($addon . '/_data/options.xml');
if (str_contains($options, 'wrxtFreshnessForumIds'))
{
    throw new RuntimeException('Legacy forum IDs option still exists');
}
if (!str_contains($options, 'wrxtFreshnessRules'))
{
    throw new RuntimeException('Configurable status rules are missing');
}

$setup = (string)file_get_contents($addon . '/Setup.php');
foreach (['wrxt_freshness_age_mode', 'reference_date', 'owner_claim_date', 'alternative_thread_id', 'upgrade1010070Step5'] as $needle)
{
    if (!str_contains($setup, $needle))
    {
        throw new RuntimeException('Setup migration missing: ' . $needle);
    }
}

$threadController = (string)file_get_contents($addon . '/XF/Pub/Controller/Thread.php');
foreach (['actionFreshnessVote', 'actionFreshnessModerate', 'actionFreshnessOwnerClaim', 'actionFreshnessReplacement'] as $action)
{
    $pos = strpos($threadController, 'function ' . $action);
    $post = strpos($threadController, '$this->assertPostOnly();', $pos);
    if ($pos === false || $post === false || $post - $pos > 300)
    {
        throw new RuntimeException('POST protection missing for ' . $action);
    }
}

$repo = (string)file_get_contents($addon . '/Repository/ThreadFreshness.php');
foreach ([
    'GREATEST(p.post_date, p.last_edit_date)',
    'p.user_id = t.user_id',
    'tq.solution_post_id',
    'reference_date',
    'moderator_status',
    '$verificationBase',
    '$state->moderator_status = \'\'',
    'getWrxtFreshnessReferenceDate'
] as $needle)
{
    if (!str_contains($repo, $needle))
    {
        throw new RuntimeException('Repository audit rule missing: ' . $needle);
    }
}

$forumController = (string)file_get_contents($addon . '/XF/Pub/Controller/Forum.php');
foreach (['columnSqlName', 'whereSql', 'xf_thread_question', 'referenceSql', 'WrxtFreshnessState.reference_date'] as $needle)
{
    if (!str_contains($forumController, $needle))
    {
        throw new RuntimeException('Forum filter consistency rule missing: ' . $needle);
    }
}

if (!str_contains($repo, 'getRecentNegativeFeedback') || !str_contains($repo, 'getWrxtFreshnessReferenceDate'))
{
    throw new RuntimeException('Current-cycle feedback filtering is missing');
}

$threadEntity = (string)file_get_contents($addon . '/XF/Entity/Thread.php');
foreach (['hasWrxtFreshnessListData', 'setWrxtFreshnessListDataPreloaded', 'getWrxtFreshnessVisitorVoteEntity'] as $needle)
{
    if (!str_contains($threadEntity, $needle))
    {
        throw new RuntimeException('Thread entity regression guard missing: ' . $needle);
    }
}
if (!str_contains($mods, '$thread.hasWrxtFreshnessListData()'))
{
    throw new RuntimeException('Thread list badge is not guarded by bulk preload state');
}

$cron = (string)file_get_contents($addon . '/Cron/Recalculate.php');
if (!str_contains($cron, 'enqueueUnique') || !str_contains($cron, 'wrxtThreadFreshnessRecalculate'))
{
    throw new RuntimeException('Recalculation cron is not uniquely queued');
}

foreach (["addKey('moderator_user_id')", "addKey('replacement_user_id')"] as $needle)
{
    if (!str_contains($setup, $needle))
    {
        throw new RuntimeException('Cleanup index missing: ' . $needle);
    }
}

$freshnessController = (string)file_get_contents($addon . '/Pub/Controller/Freshness.php');
if (!str_contains($freshnessController, 'mb_substr') || !str_contains($freshnessController, '0, 100'))
{
    throw new RuntimeException('Public search query length guard is missing');
}

$threadEntity = (string)file_get_contents($addon . '/XF/Entity/Thread.php');
foreach (['getWrxtFreshnessEligibleDate', 'getWrxtFreshnessDaysUntilEligible'] as $needle)
{
    if (!str_contains($threadEntity, $needle))
    {
        throw new RuntimeException('Thread eligibility display helper is missing: ' . $needle);
    }
}
if ((!str_contains($mods, 'wrxtFreshness-voteButtons') && !str_contains($mods, 'wrxtFreshness-choiceSurface')) || !str_contains($mods, 'wrxtFreshness-help') || !str_contains($mods, 'warext/thread_freshness.js'))
{
    throw new RuntimeException('Responsive side voting widget is missing');
}
if (str_contains($mods, 'modification_key="wrxt_thread_freshness_replacement"') || str_contains($mods, 'modification_key="wrxt_thread_freshness_moderator"'))
{
    throw new RuntimeException('Legacy standalone freshness panels still exist');
}
$hardCodedUi = [
    'Etkin forum konusu',
    'Toplam oy',
    'Etkin forum',
    'Kontrol gerektiren konular',
    'Kontrol gerektiren konu bulunmuyor.',
    'Sistemde çalışmadı',
    'Olumsuz geri bildirim bulunmuyor.',
    'submit="Ara"',
    ' forum aktif)',
    ' Bilgi&lt;/summary&gt;',
    ' İşlemler&lt;/summary&gt;',
    'Oy verme açılış tarihi:'
];
foreach ($hardCodedUi as $literal)
{
    if (str_contains($mods, $literal) || str_contains((string)file_get_contents($addon . '/_data/templates.xml'), $literal))
    {
        throw new RuntimeException('Hard-coded localized UI text remains: ' . $literal);
    }
}
foreach ([
    'wrxt_freshness_error_invalid_vote',
    'wrxt_freshness_error_version_required',
    'wrxt_freshness_error_invalid_vote_payload',
    'wrxt_freshness_error_invalid_moderator_status',
    'wrxt_freshness_error_invalid_replacement'
] as $phraseKey)
{
    if (!str_contains($threadController, "XF::phrase('" . $phraseKey . "')"))
    {
        throw new RuntimeException('Controller error is not phrase-backed: ' . $phraseKey);
    }
}

$templatesData = (string)file_get_contents($addon . '/_data/templates.xml');
if (!str_contains($templatesData, 'wrxt_thread_freshness.less'))
{
    throw new RuntimeException('Freshness widget stylesheet template is missing');
}
$widgetJs = $root . '/upload/js/warext/thread_freshness.js';
if (!is_file($widgetJs) || !str_contains((string)file_get_contents($widgetJs), "document.querySelector('.p-title')"))
{
    throw new RuntimeException('Freshness widget responsive placement script is missing');
}

if (!str_contains($mods, '$thread.isWrxtFreshnessEnabled()') || !str_contains($mods, 'wrxt_freshness_voting_opens'))
{
    throw new RuntimeException('Pre-eligibility thread panel is missing');
}
if (!str_contains($adminNav, 'wrxtThreadFreshnessForums') || !str_contains($dashboard, 'actionForums'))
{
    throw new RuntimeException('Central forum/category settings are missing');
}

if (!str_contains($mods, 'wrxtFreshness-choiceSurface') || !str_contains($mods, "phrase('wrxt_freshness_update') : phrase('wrxt_freshness_save')") || !str_contains($mods, 'required="required"'))
{
    throw new RuntimeException('Two-step selectable vote UI is missing');
}
foreach (['fa-history', 'fa-chart-bar', 'fa-comments', 'fa-cog'] as $icon)
{
    if (!str_contains($adminNav, $icon))
    {
        throw new RuntimeException('ACP navigation icon missing: ' . $icon);
    }
}

$voteService = (string)file_get_contents($addon . '/Service/ThreadFreshness/Vote.php');
if (str_contains($threadEntity, "hasPermission('wrxtFreshness', 'changeVote')") || str_contains($voteService, "hasPermission('wrxtFreshness', 'changeVote')"))
{
    throw new RuntimeException('Vote changing is still restricted by legacy changeVote permission');
}
if (!str_contains($threadEntity, 'return !$this->hasWrxtFreshnessOwnerClaim();'))
{
    throw new RuntimeException('Owner vote controls do not unlock after removing owner verification');
}
if (str_contains($voteService, "if (!\$this->user->hasPermission('wrxtFreshness', 'vote'))"))
{
    throw new RuntimeException('Vote service still blocks the topic owner after owner verification is removed');
}
if (!str_contains($mods, "phrase('wrxt_freshness_update') : phrase('wrxt_freshness_save')"))
{
    throw new RuntimeException('Vote update UX is missing');
}

if (str_contains($mods, 'Bu çözüm sende çalıştı mı?') || str_contains($mods, 'Seçimini yap, ardından oyu kaydet.') || str_contains($mods, 'Çözüm hâlâ geçerli') || str_contains($mods, 'Çözüm artık geçersiz'))
{
    throw new RuntimeException('Verbose vote UI text still exists');
}
if (!str_contains($mods, "phrase('wrxt_freshness_update') : phrase('wrxt_freshness_save')") || !str_contains($templatesData, 'grid-template-columns:minmax(0,1fr) auto') || !str_contains($templatesData, 'min-height:32px'))
{
    throw new RuntimeException('Compact vote UI contract is missing');
}

if (!str_contains($mods, 'wrxtFreshness-fieldsHead') || !str_contains($templatesData, 'right:calc(100% + 10px)') || !str_contains($templatesData, '@media(max-width:900px)'))
{
    throw new RuntimeException('Side vote details panel / responsive sheet contract is missing');
}
if (!str_contains($widgetJsSource = (string)file_get_contents($widgetJs), 'wrxtFreshness-detailBackdrop') || !str_contains($widgetJsSource, 'data-wrxt-detail-close') || !str_contains($widgetJsSource, "widget.style.maxWidth = '340px'"))
{
    throw new RuntimeException('Responsive vote details behavior is missing');
}

$widgetJsSource = (string)file_get_contents($widgetJs);
if (!str_contains($widgetJsSource, 'title.appendChild(widget)') || !str_contains($widgetJsSource, "title.insertAdjacentElement('afterend', widget)"))
{
    throw new RuntimeException('Desktop title-row / mobile below-title placement is missing');
}

echo "OK\n";
