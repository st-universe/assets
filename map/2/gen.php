<?php

/**
 * USAGE:
 * 
 * 1) put the original files into the original folder.
 * 2) start the script on command line via "php gen.php PASSWORT".
 * 3) commit the changes made to the encoded folder to github.
 */

$basePath = sprintf(
    '%s/original',
    __DIR__
);

$destinationPath = sprintf(
    '%s/encoded',
    __DIR__
);

$key = $argv[1];

$count = 0;
$list = new DirectoryIterator($basePath);

foreach ($list as $file) {
    if ($file->isDir()) {
        continue;
    }

    $count++;

    $fileName = $file->getFilename();
    $index = str_replace('.png', '', $fileName);

    echo $fileName . " " . $index . " ";

    if (mb_strlen($key, '8bit') !== 32) {
        throw new RangeException('Key is not the correct size (must be 32 bytes).');
    }

    $cipher = base64_encode(crypt($index, $key));
    $cipher = bin2hex($cipher);

    $parts = str_split($cipher, 8);
    $partsSize = count($parts);

    $last = $parts[$partsSize - 1];
    unset($parts[$partsSize - 1]);

    $newFolderPath = implode("/", $parts);
    echo $newFolderPath . "/" . $last .  "\n";

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
            $last
        )
    );
}

echo "\n\n";
echo sprintf("%d images have been ciphered to encoded folder. You can commit the encoded folder now.\n\n", $count);
