<?php

$templatePath = sprintf(
    '%s/templates/',
    __DIR__
);

$destinationPath = sprintf(
    '%s/generated',
    __DIR__
);

$imageWidth = 30;
$imageHeight = 30;

for ($a = 0; $a < 6; $a++) {
    for ($b = 0; $b < 6; $b++) {
        for ($c = 0; $c < 6; $c++) {
            for ($d = 0; $d < 6; $d++) {

                $destinationImage = imagecreatetruecolor($imageWidth, $imageHeight);
                imagealphablending($destinationImage, true);
                imagesavealpha($destinationImage, true);

                $aPath = sprintf(
                    '%s/%d.png',
                    $templatePath,
                    $a
                );

                $bPath = sprintf(
                    '%s/%d.png',
                    $templatePath,
                    $b
                );

                $cPath = sprintf(
                    '%s/%d.png',
                    $templatePath,
                    $c
                );

                $dPath = sprintf(
                    '%s/%d.png',
                    $templatePath,
                    $d
                );

                $aImage = imagecreatefrompng($aPath);
                $bImage = imagecreatefrompng($bPath);
                $cImage = imagecreatefrompng($cPath);
                $dImage = imagecreatefrompng($dPath);

                // Rotate
                $bImage = imagerotate($bImage, 90, 0);
                $cImage = imagerotate($cImage, 180, 0);
                $dImage = imagerotate($dImage, 270, 0);

                imagecopy($destinationImage, $aImage, 0, 0, 0, 0, $imageWidth, $imageHeight);
                imagecopy($destinationImage, $bImage, 0, 0, 0, 0, $imageWidth, $imageHeight);
                imagecopy($destinationImage, $cImage, 0, 0, 0, 0, $imageWidth, $imageHeight);
                imagecopy($destinationImage, $dImage, 0, 0, 0, 0, $imageWidth, $imageHeight);
                imagepng(
                    $destinationImage,
                    sprintf('%s/%d%d%d%d.png', $destinationPath, $a, $b, $c, $d)
                );

                imagedestroy($destinationImage);
                imagedestroy($aImage);
                imagedestroy($bImage);
                imagedestroy($cImage);
                imagedestroy($dImage);
            }
        }
    }
}
