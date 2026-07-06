<?php

/**
 * Generates blog article hero images for seeding and the Next.js public folder.
 * Run: php database/seeders/scripts/generate_blog_images.php
 */

$articles = [
    ['file' => 'article_find_manufacturer.png', 'title' => 'Find the Right Manufacturer', 'color' => [30, 58, 95]],
    ['file' => 'article_rfq_guide.png', 'title' => 'RFQ Guide', 'color' => [45, 80, 22]],
    ['file' => 'article_manufacturer_profiles.png', 'title' => 'Manufacturer Profiles', 'color' => [75, 45, 110]],
    ['file' => 'article_reviewed_manufacturers.png', 'title' => 'Reviewed Manufacturers', 'color' => [120, 75, 20]],
    ['file' => 'article_supplier_comparison.png', 'title' => 'Supplier Comparison', 'color' => [20, 90, 100]],
    ['file' => 'article_private_label.png', 'title' => 'Private Label Sourcing', 'color' => [130, 40, 70]],
    ['file' => 'article_manufacturer_trust.png', 'title' => 'Manufacturer Trust', 'color' => [50, 50, 120]],
    ['file' => 'article_sourcing_platform.png', 'title' => 'Sourcing Platform', 'color' => [35, 70, 55]],
];

$width = 1280;
$height = 720;

$baseDir = dirname(__DIR__);
$apiRoot = dirname($baseDir, 2);
$targets = [
    $baseDir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'images',
    $baseDir . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'images',
    $apiRoot . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'jaawaas_cli' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'blog',
];

foreach ($targets as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

foreach ($articles as $article) {
    $image = imagecreatetruecolor($width, $height);
    [$r, $g, $b] = $article['color'];
    $bg = imagecolorallocate($image, $r, $g, $b);
    imagefilledrectangle($image, 0, 0, $width, $height, $bg);

    // Subtle gradient overlay
    for ($y = 0; $y < $height; $y++) {
        $alpha = (int) (80 * ($y / $height));
        $shade = imagecolorallocatealpha($image, 0, 0, 0, 127 - min(127, $alpha));
        imageline($image, 0, $y, $width, $y, $shade);
    }

    // Grid pattern
    $grid = imagecolorallocatealpha($image, 255, 255, 255, 110);
    for ($x = 0; $x < $width; $x += 40) {
        imageline($image, $x, 0, $x, $height, $grid);
    }
    for ($y = 0; $y < $height; $y += 40) {
        imageline($image, 0, $y, $width, $y, $grid);
    }

    $white = imagecolorallocate($image, 255, 255, 255);
    $muted = imagecolorallocate($image, 220, 230, 240);

    imagestring($image, 5, 64, 64, 'SourceNest Insights', $muted);

    $title = $article['title'];
    imagestring($image, 5, 64, 120, $title, $white);

    $subtitle = 'Global B2B Sourcing Knowledge';
    imagestring($image, 3, 64, 160, $subtitle, $muted);

    foreach ($targets as $dir) {
        $path = $dir . DIRECTORY_SEPARATOR . $article['file'];
        imagepng($image, $path);
        echo "Created: {$path}\n";
    }

    imagedestroy($image);
}

echo "Done.\n";
