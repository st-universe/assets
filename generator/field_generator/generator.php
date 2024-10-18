<?php

declare(strict_types=1);

$basePath = __DIR__;
$layerPath = sprintf(
    '%s/layer/',
    $basePath
);
$originalImagePath = sprintf(
    '%s/../fields/',
    $basePath
);
$destinationPath = sprintf(
    '%s/../../generated/fields/',
    $basePath
);

$content = glob(sprintf('%s/*.png', $originalImagePath));

@mkdir($destinationPath, 0755, true);

foreach ($content as $baseImage) {
    copy(
        $baseImage,
        sprintf('%s/%s', $destinationPath, basename($baseImage))
    );
}

$imageWidth = 40;
$imageHeight = 40;

$config = json_decode(file_get_contents(sprintf('%s/generator_mapping.json', $basePath)));

foreach ($config as $item) {
    $baseImagePath = sprintf(
        '%s/%s.png',
        $originalImagePath,
        $item->base_field_id
    );

    if (!file_exists($baseImagePath)) {
        printf('Base field %s missing, ignoring', $item->base_field_id);
        echo PHP_EOL;
        continue;
    }

    $layerImagePath = sprintf(
        '%s/%s.png',
        $layerPath,
        $item->layer_id
    );

    if (!file_exists($layerImagePath)) {
        printf('Base field %s missing, ignoring', $item->layer_id);
        echo PHP_EOL;
        continue;
    }

    $destinationImage = imagecreatetruecolor($imageWidth, $imageHeight);

    $baseImage = imagecreatefrompng($baseImagePath);
    $layerImage = imagecreatefrompng($layerImagePath);

    imagecopy($destinationImage, $baseImage, 0 ,0, 0, 0, $imageWidth, $imageHeight);
    imagecopy($destinationImage, $layerImage, 0 ,0, 0, 0, $imageWidth, $imageHeight);
    imagepng($destinationImage, sprintf('%s/%s.png', $destinationPath, $item->destination_field_id));
}
