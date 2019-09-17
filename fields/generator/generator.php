<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

$basePath = __DIR__;
$layerPath = $basePath . '/layer';

$imageWidth = 40;
$imageHeight = 40;

$config = json_decode(file_get_contents('generator_mapping.json'));

foreach ($config as $item) {
    $baseImagePath = sprintf(
        '%s/../png/%d.png',
        $basePath,
        $item->base_field_id
    );

    if (!file_exists($baseImagePath)) {
        printf('Base field %d missing, ignoring', $item->base_field_id);
        echo PHP_EOL;
        continue;
    }

    $layerImagePath = sprintf(
        '%s/%d.png',
        $layerPath,
        $item->layer_id
    );

    if (!file_exists($layerImagePath)) {
        printf('Base field %d missing, ignoring', $item->layer_id);
        echo PHP_EOL;
        continue;
    }

    $destinationImage = imagecreatetruecolor($imageWidth, $imageHeight);

    $baseImage = imagecreatefrompng($baseImagePath);
    $layerImage = imagecreatefrompng($layerImagePath);

    imagecopy($destinationImage, $baseImage, 0 ,0, 0, 0, $imageWidth, $imageHeight);
    imagecopy($destinationImage, $layerImage, 0 ,0, 0, 0, $imageWidth, $imageHeight);
    imagepng($destinationImage, sprintf('%s/../png/%d.png', $basePath, $item->destination_field_id));
}
