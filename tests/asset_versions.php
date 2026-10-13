<?php

declare(strict_types=1);

$root = sys_get_temp_dir() . '/stu-asset-versions-' . bin2hex(random_bytes(8));
mkdir($root . '/buttons', 0775, true);
mkdir($root . '/generator', 0775, true);
mkdir($root . '/map/2', 0775, true);
file_put_contents($root . '/buttons/test.PNG', 'first');
file_put_contents($root . '/buttons/unchanged.png', 'unchanged');
file_put_contents($root . '/map/2/42.png', 'map');
file_put_contents($root . '/generator/source.png', 'source');
$script = dirname(__DIR__) . '/generator/asset_versions/generate.php';
$run = static function () use ($root, $script): array {
    exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($root), $output, $code);
    if ($code !== 0) {
        throw new RuntimeException('Generator fehlgeschlagen');
    }
    $manifest = json_decode(file_get_contents($root . '/asset-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    $manifest['files'] = [];
    foreach ($manifest['shards'] as $shard) {
        $manifest['files'] += json_decode(file_get_contents($root . '/' . $shard), true, 512, JSON_THROW_ON_ERROR);
    }
    return $manifest;
};
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
try {
    $first = $run();
    $check(count($first['files']) === 3, 'Quellen dürfen nicht versioniert werden');
    touch($root . '/buttons/test.PNG', time() + 10);
    $check($first === $run(), 'Zeitstempel darf Hash nicht verändern');
    $dynamic = file_get_contents($root . '/' . $first['script']);
    $check(strpos($dynamic, 'buttons/test.PNG') !== false, 'Dynamische Grafik fehlt');
    $check(strpos($dynamic, 'map/2/42.png') === false, 'Kartenkacheln gehören nicht in die Browser-Zuordnung');
    file_put_contents($root . '/buttons/test.PNG', 'second');
    $second = $run();
    $check($first['files']['buttons/test.PNG'] !== $second['files']['buttons/test.PNG'], 'Geänderter Inhalt braucht neuen Hash');
    $check($first['files']['buttons/unchanged.png'] === $second['files']['buttons/unchanged.png'], 'Unveränderte Datei braucht stabilen Hash');
    $check($first['script'] !== $second['script'], 'Browser-Zuordnung braucht neue URL');
    $check(is_file($root . '/' . $first['script']), 'Vorherige Browser-Zuordnung muss erreichbar bleiben');
    unlink($root . '/buttons/test.PNG');
    $third = $run();
    $check(!isset($third['files']['buttons/test.PNG']), 'Gelöschte Datei darf nicht im Manifest bleiben');
    echo "Asset-Versionierung erfolgreich geprüft\n";
} finally {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($root);
}
