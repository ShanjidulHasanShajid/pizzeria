<?php

declare(strict_types=1);

use App\Modules\Shared\Application\Ports\ImageProcessor;

/** Makes a plain red PNG picture in memory. */
function sampleImage(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, (int) imagecolorallocate($image, 255, 0, 0));

    ob_start();
    imagepng($image);

    return (string) ob_get_clean();
}

it('shrinks an image to fit the box and keeps its shape', function () {
    $result = app(ImageProcessor::class)->resize(sampleImage(400, 200), 100, 100);

    [$width, $height] = getimagesizefromstring($result);

    expect($width)->toBe(100)->and($height)->toBe(50);
});

it('never makes an image bigger', function () {
    $result = app(ImageProcessor::class)->resize(sampleImage(80, 40), 1000, 1000);

    [$width, $height] = getimagesizefromstring($result);

    expect($width)->toBe(80)->and($height)->toBe(40);
});

it('makes a square thumbnail', function () {
    $result = app(ImageProcessor::class)->thumbnail(sampleImage(400, 200), 64);

    [$width, $height] = getimagesizefromstring($result);

    expect($width)->toBe(64)->and($height)->toBe(64);
});

it('converts an image to WebP', function () {
    $result = app(ImageProcessor::class)->toWebp(sampleImage(100, 100));

    expect(getimagesizefromstring($result)['mime'])->toBe('image/webp');
});
