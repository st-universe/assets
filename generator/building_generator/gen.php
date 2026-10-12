<?php

$basePath = sprintf(
    '%s/../buildings/',
    __DIR__
);

$destinationPath = sprintf(
    '%s/../../generated/buildings/',
    __DIR__
);

$imageWidth = 40;
$imageHeight = 40;

$fileSuffixes = [
    'an',
    'at',
    'bn',
    'bt'
];

$dirContent = glob(
    sprintf(
        '%s/*',
        $basePath
    ),
    GLOB_ONLYDIR
);

foreach ($dirContent as $dir) {

    $configFilePath = sprintf(
        '%s/config.json',
        $dir
    );

    if (!file_exists($configFilePath)) {
        continue;
    }
    $config = json_decode(file_get_contents($configFilePath));

    $baseBuildingId = $config->base_building_id;

    $buildingSourcePath = sprintf(
        '%s/%d',
        $basePath,
        $baseBuildingId
    );

    $fileNames = [];
    foreach ([0, 90, 180, 270] as $rotation) {
        foreach ($fileSuffixes as $fileSuffix) {
            $fileName = sprintf('%d%s', $rotation, $fileSuffix);
            $sourcePath = sprintf('%s/%s.png', $buildingSourcePath, $fileName);

            if (file_exists($sourcePath) === false) {
                if ($rotation === 0) {
                    echo sprintf('File `%d/%s.png` not found', $baseBuildingId, $fileName).PHP_EOL;
                }
                continue;
            }

            $fileNames[] = $fileName;
        }
    }

    $buildingDestinationPath = sprintf(
        '%s/%d',
        $destinationPath,
        $baseBuildingId
    );

    @mkdir(
        $buildingDestinationPath,
        0755,
        true
    );

    foreach ($fileNames as $fileName) {
        $sourcePath = sprintf(
            '%s/%s.png',
            $buildingSourcePath,
            $fileName
        );

        copy(
            $sourcePath,
            sprintf(
                '%s/%s.png',
                $buildingDestinationPath,
                $fileName
            )
        );
    }

    $images = array_merge(
        $config->buildable_fields,
        $config->upgrade_to
    );

    foreach ($images as $item) {
        if ($item->bonus_type === 0) {
            continue;
        }

        $buildingDestinationPath = sprintf(
            '%s/%d',
            $destinationPath,
            $item->building_id
        );

        @mkdir(
            $buildingDestinationPath,
            0755,
            true
        );

        foreach ($fileNames as $fileName) {
            $baseImagePath = sprintf(
                '%s/%s.png',
                $buildingSourcePath,
                $fileName
            );

            $fragmentImagePath = sprintf(
                '%s/fragment/bonus%d.png',
                __DIR__,
                $item->bonus_type
            );

            $destinationImage = imagecreatetruecolor($imageWidth, $imageHeight);
            imagesavealpha($destinationImage, true);
            imagealphablending($destinationImage, false);

            $baseImage = @imagecreatefrompng($baseImagePath);
            $fragmentImage = @imagecreatefrompng($fragmentImagePath);

            imagecopy($destinationImage, $baseImage, 0 ,0, 0, 0, $imageWidth, $imageHeight);
            imagecopy($destinationImage, $fragmentImage, 24 ,24, 0, 0, $imageWidth, $imageHeight);
            imagepng(
                $destinationImage,
                sprintf('%s/%s.png', $buildingDestinationPath, $fileName)
            );

            imagedestroy($destinationImage);
            imagedestroy($baseImage);
        }
    }
}
