<?php
$source_path = 'c:/xampp/htdocs/Php/Adver/Whatsapp_Automation/assets/hero_bg.jpg';
$dest_webp = 'c:/xampp/htdocs/Php/Adver/Whatsapp_Automation/assets/hero_bg_optimized.webp';
$dest_jpg = 'c:/xampp/htdocs/Php/Adver/Whatsapp_Automation/assets/hero_bg_optimized.jpg';

if (!file_exists($source_path)) {
    die("Source file not found at $source_path\n");
}

echo "Loading source image (13063x7351). This may take a few seconds...\n";
$source_image = imagecreatefromjpeg($source_path);
if (!$source_image) {
    die("Failed to load source image\n");
}

$orig_width = imagesx($source_image);
$orig_height = imagesy($source_image);
echo "Original dimensions: {$orig_width}x{$orig_height}\n";

// Target width 2560 for crisp desktop background, height proportional (16:9)
$target_width = 2560;
$target_height = round(($orig_height / $orig_width) * $target_width);

echo "Resizing to: {$target_width}x{$target_height}...\n";
$optimized_image = imagecreatetruecolor($target_width, $target_height);

// Enable alpha blending and save alpha for webp transparency support just in case
imagealphablending($optimized_image, false);
imagesavealpha($optimized_image, true);

// Resize using bicubic/high-quality resampling
imagecopyresampled(
    $optimized_image, 
    $source_image, 
    0, 0, 0, 0, 
    $target_width, 
    $target_height, 
    $orig_width, 
    $orig_height
);

echo "Saving WebP to $dest_webp...\n";
imagewebp($optimized_image, $dest_webp, 80); // 80% quality is perfect balance

echo "Saving JPEG to $dest_jpg...\n";
imagejpeg($optimized_image, $dest_jpg, 85); // 85% quality for JPEG fallback

// Clean up memory
imagedestroy($source_image);
imagedestroy($optimized_image);

echo "Image optimization complete!\n";
echo "WebP Size: " . round(filesize($dest_webp) / 1024, 2) . " KB\n";
echo "JPEG Size: " . round(filesize($dest_jpg) / 1024, 2) . " KB\n";
?>
