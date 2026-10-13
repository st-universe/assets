<?php

declare(strict_types=1);

$root = realpath($argv[1] ?? dirname(__DIR__, 2));
if ($root === false) {
    throw new RuntimeException('Assets-Verzeichnis nicht gefunden');
}

$outputDirectory = $root . '/generated/asset-versions';
if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
    throw new RuntimeException('Versionsverzeichnis konnte nicht erstellt werden');
}

$lock = fopen($outputDirectory . '/build.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX)) {
    throw new RuntimeException('Asset-Versionierung konnte nicht gesperrt werden');
}

$files = [];
$dynamicFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    static function (SplFileInfo $file): bool {
        return !$file->isLink()
            && !in_array($file->getFilename(), ['.git', 'vendor', 'generator', 'dist', 'asset-versions'], true);
    }
));

foreach ($iterator as $file) {
    if (!$file->isFile() || !in_array(strtolower($file->getExtension()), ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'avif', 'ico'], true)) {
        continue;
    }
    $path = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root) + 1));
    $hash = hash_file('sha256', $file->getPathname());
    if ($hash === false) {
        throw new RuntimeException('Grafik konnte nicht gelesen werden: ' . $path);
    }
    $files[$path] = substr($hash, 0, 20);
    if (preg_match('~^(?:(?:buttons|commodities|navigation|rassen|main|anomalies)/|map/(?:[^/]+$|anomalies/|systemtypes/)|generated/buildings/[^/]+/0at\.png$)~', $path)) {
        $dynamicFiles[$path] = $files[$path];
    }
}

ksort($files);
ksort($dynamicFiles);
$javascript = 'window.STU_ASSET_VERSIONS = ' . json_encode($dynamicFiles, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) . ";\n";
$script = 'generated/asset-versions/' . substr(hash('sha256', $javascript), 0, 20) . '.js';
$buckets = [];
foreach ($files as $path => $hash) {
    $bucket = substr(hash('sha256', dirname($path)), 0, 2);
    $buckets[$bucket][$path] = $hash;
}
ksort($buckets);

$write = static function (string $path, string $content): void {
    if (is_file($path) && file_get_contents($path) === $content) {
        return;
    }
    $temporary = tempnam(dirname($path), '.asset-');
    if ($temporary === false) {
        throw new RuntimeException('Temporäre Versionsdatei konnte nicht erstellt werden');
    }
    try {
        if (file_put_contents($temporary, $content) !== strlen($content) || !chmod($temporary, 0644) || !rename($temporary, $path)) {
            throw new RuntimeException('Versionsdatei konnte nicht veröffentlicht werden: ' . $path);
        }
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
};

$shards = [];
foreach ($buckets as $bucket => $entries) {
    $content = json_encode($entries, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $path = 'generated/asset-versions/' . substr(hash('sha256', $content), 0, 20) . '.json';
    $write($root . '/' . $path, $content);
    $shards[$bucket] = $path;
}
$manifest = json_encode(['shards' => (object) $shards, 'script' => $script], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
$write($root . '/' . $script, $javascript);
$write($root . '/asset-manifest.json', $manifest);
echo count($files) . " Grafikversionen erzeugt\n";
