<?php

$root = __DIR__ . '/../upload/src/addons/WarextStudios/ThreadFreshness';
$required = [
    'class_extensions.xml',
    'cron.xml',
    'option_groups.xml',
    'options.xml',
    'permissions.xml',
    'phrases.xml',
    'routes.xml',
    'admin_navigation.xml',
    'template_modifications.xml',
    'templates.xml'
];

foreach ($required as $file)
{
    $path = $root . '/_data/' . $file;
    if (!is_file($path))
    {
        fwrite(STDERR, "Eksik data dosyası: $file\n");
        exit(1);
    }
    libxml_use_internal_errors(true);
    if (simplexml_load_file($path) === false)
    {
        fwrite(STDERR, "Geçersiz XML: $file\n");
        exit(1);
    }
}

$addon = json_decode((string)file_get_contents($root . '/addon.json'), true);
$version = (string)($addon['version_string'] ?? '');
if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $m)
    || (int)($addon['version_id'] ?? 0) !== ((int)$m[1] * 1000000 + (int)$m[2] * 10000 + (int)$m[3] * 10))
{
    fwrite(STDERR, "Sürüm metadata hatalı\n");
    exit(1);
}

$routes = (string)file_get_contents($root . '/_data/routes.xml');
foreach (['thread-freshness', 'guncel-cozumler'] as $needle)
{
    if (strpos($routes, $needle) === false)
    {
        fwrite(STDERR, "Route eksik: $needle\n");
        exit(1);
    }
}

$options = (string)file_get_contents($root . '/_data/options.xml');
if (strpos($options, 'wrxtFreshnessRules') === false || strpos($options, 'wrxtFreshnessForumIds') !== false)
{
    fwrite(STDERR, "Option export tutarsız\n");
    exit(1);
}

$templates = (string)file_get_contents($root . '/_data/templates.xml');
if (strpos($templates, 'wrxt_thread_freshness_option_rules') === false)
{
    fwrite(STDERR, "Kural ayar şablonu eksik\n");
    exit(1);
}

$modifications = (string)file_get_contents($root . '/_data/template_modifications.xml');
if (strpos($modifications, 'array_merge($filters') === false || strpos($modifications, '$filters|replace') !== false)
{
    fwrite(STDERR, "Forum filtre şablonu hatalı\n");
    exit(1);
}

$classExtensions = (string)file_get_contents($root . '/_data/class_extensions.xml');
foreach ([
    'WarextStudios\\ThreadFreshness\\XF\\Entity\\Thread',
    'WarextStudios\\ThreadFreshness\\XF\\Entity\\Forum',
    'WarextStudios\\ThreadFreshness\\XF\\Pub\\Controller\\Thread'
] as $needle)
{
    if (strpos($classExtensions, $needle) === false)
    {
        fwrite(STDERR, "Class extension eksik: $needle\n");
        exit(1);
    }
}

$permissions = (string)file_get_contents($root . '/_data/permissions.xml');
foreach (['vote', 'changeVote', 'voteOwn', 'moderate'] as $needle)
{
    if (strpos($permissions, 'permission_id="' . $needle . '"') === false)
    {
        fwrite(STDERR, "Permission eksik: $needle\n");
        exit(1);
    }
}

if (is_dir($root . '/_output'))
{
    fwrite(STDERR, "Stable kaynakta eski _output bulunmamalı\n");
    exit(1);
}

$basePhraseXml = simplexml_load_file($root . '/_data/phrases.xml');
$languageRoot = dirname(__DIR__) . '/languages';
$englishPhraseXml = simplexml_load_file($languageRoot . '/English.xml');
$turkishPhraseXml = simplexml_load_file($languageRoot . '/Turkish.xml');
if (!$basePhraseXml || !$englishPhraseXml || !$turkishPhraseXml)
{
    fwrite(STDERR, "Dil paketleri okunamadı\n");
    exit(1);
}
$phraseTitles = static function(SimpleXMLElement $xml): array
{
    $titles = [];
    foreach ($xml->phrase as $phrase)
    {
        $titles[(string)$phrase['title']] = true;
    }
    ksort($titles);
    return $titles;
};
$baseTitles = $phraseTitles($basePhraseXml);
$englishTitles = $phraseTitles($englishPhraseXml);
$turkishTitles = $phraseTitles($turkishPhraseXml);
foreach (array_keys($baseTitles) as $title)
{
    if (!isset($englishTitles[$title]) || !isset($turkishTitles[$title]))
    {
        fwrite(STDERR, "Dil paketinde phrase eksik: $title\n");
        exit(1);
    }
}

echo "OK\n";
