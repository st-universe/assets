<?php

require_once __DIR__ . "/../../vendor/paragonie/sodium_compat/autoload.php";

$basePath = sprintf(
    '%s/original',
    __DIR__
);

$destinationPath = sprintf(
    '%s',
    __DIR__
);

echo print_r($argv, true);
$key = $argv[1];

$list = new DirectoryIterator($basePath);

foreach ($list as $file) {
    if ($file->isDir()) {
        continue;
    }

    $fileName = $file->getFilename();
    $index = str_replace('.png', '', $fileName);

    echo $fileName . " ";

    $hash = hash("sha256", $index);
    //zahl extrahieren
    //chiffrieren
    //kopieren

    if (mb_strlen($key, '8bit') !== 32) {
        throw new RangeException('Key is not the correct size (must be 32 bytes).');
    }
    $nonce = random_bytes(24);

    $cipher = base64_encode(
        sodium_crypto_secretbox(
            $index,
            $nonce,
            $key
        )
    );
    //sodium_memzero($index);
    //sodium_memzero($key);

    $cipher = str_replace('/', '5', $cipher);
    //echo $cipher . "\n";

    $parts = str_split($cipher, 8);

    $newFolderPath = implode("/", [$parts[0], $parts[1], $parts[2]]);
    echo $newFolderPath . "\n";

    $itemDestinationPath = sprintf('%s/%s', $destinationPath, $newFolderPath);

    @mkdir(
        $itemDestinationPath,
        0755,
        true
    );

    $sourcePath = sprintf(
        '%s/%s',
        $basePath,
        $fileName
    );

    copy(
        $sourcePath,
        sprintf(
            '%s/%s.png',
            $itemDestinationPath,
            $parts[3]
        )
    );
}

return;

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

        if (file_exists($sourcePath) === false) {
            echo sprintf('File `%d/%s.png` not found', $baseBuildingId, $fileName) . PHP_EOL;
            continue;
        }

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

            if (file_exists($baseImagePath) === false) {
                echo sprintf('File `%d/%s.png` not found', $baseBuildingId, $fileName) . PHP_EOL;
                continue;
            }

            $baseImage = @imagecreatefrompng($baseImagePath);
            $fragmentImage = @imagecreatefrompng($fragmentImagePath);

            imagecopy($destinationImage, $baseImage, 0, 0, 0, 0, $imageWidth, $imageHeight);
            imagecopy($destinationImage, $fragmentImage, 24, 24, 0, 0, $imageWidth, $imageHeight);
            imagepng(
                $destinationImage,
                sprintf('%s/%s.png', $buildingDestinationPath, $fileName)
            );

            imagedestroy($destinationImage);
            imagedestroy($baseImage);
        }
    }
}
