<?php
$dir = __DIR__;
$maxDim = 800;
foreach (glob("$dir/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}", GLOB_BRACE) as $file) {
    list($width, $height, $type) = getimagesize($file);
    $ratio = min($maxDim / $width, $maxDim / $height, 1);
    $new_width = (int)($width * $ratio);
    $new_height = (int)($height * $ratio);
    switch ($type) {
        case IMAGETYPE_JPEG: $src_img = imagecreatefromjpeg($file); break;
        case IMAGETYPE_PNG:  $src_img = imagecreatefrompng($file); break;
        case IMAGETYPE_GIF:  $src_img = imagecreatefromgif($file); break;
        default: $src_img = null;
    }
    if ($src_img) {
        $dst_img = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagejpeg($dst_img, $file, 85);
        imagedestroy($src_img);
        imagedestroy($dst_img);
        echo "Optimisé : $file\n";
    }
}
echo "Terminé !\n";
