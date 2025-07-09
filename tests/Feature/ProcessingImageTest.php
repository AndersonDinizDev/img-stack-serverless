<?php

namespace Tests\Feature;

use App\Http\Services\ImageProcessingService;
use Illuminate\Http\Testing\File;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessingImageTest extends TestCase
{
    protected object $image;
    protected ImageProcessingService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->image = File::createWithContent('test.png', $this->createTestImageWithTwoColors());
        $this->service = app()->make(ImageProcessingService::class);
    }

    #[Test]
    public function it_applies_the_transformations_to_the_image_without_checking_by_ia(): void
    {
        $transformations = [
            'width' => '800',
            'height' => '600',
            'format' => 'webp'
        ];

        $imageProcessed = $this->service->transformImage($this->image->getContent(), $transformations);

        $imageDecode = ImageManager::imagick()->read($imageProcessed);

        $this->assertEquals($transformations['width'], $imageDecode->width(), "A largura não foi aplicada corretamente");
        $this->assertEquals($transformations['height'], $imageDecode->height(), "A altura não foi aplicada corretamente");
        $this->assertEquals("image/{$transformations['format']}", $imageDecode->origin()->mediaType(), "O formato não foi aplicado corretamente");
    }

    #[Test]
    public function it_applies_the_transformations_to_the_image_with_face_detection_by_ai(): void
    {
        $transformation = [
            'width' => 500,
            'height' => 500,
            'format' => 'png',
            'ai_analisis' => [
                'faces' => [
                    'is_face' => true,
                    'labels' => [[
                        "Width" => 0.34740129113197,
                        "Height" => 0.30669620633125,
                        "Left" => 0.28709068894386,
                        "Top" => 0.27170634269714,
                    ]],
                ],
            ]
        ];

        $imageProcessed = $this->service->transformImage($this->image->getContent(), $transformation, $transformation['ai_analisis']);

        $originalImageDecode = ImageManager::imagick()->read($this->image->getContent());
        $imageDecode = ImageManager::imagick()->read($imageProcessed);

        $faceData = $transformation['ai_analisis']['faces']['labels'][0];
        $faceCenterX = floor($imageDecode->width() * ($faceData['Left'] + $faceData['Width'] / 2));
        $faceCenterY = floor($imageDecode->height() * ($faceData['Top'] + $faceData['Height'] / 2));

        $originalPixelColor = $originalImageDecode->pickColor($faceCenterX, $faceCenterY);
        $processedPixelColor = $imageDecode->pickColor($faceCenterX, $faceCenterY);

        $this->assertNotEquals($originalPixelColor, $processedPixelColor, "O blur não foi aplicado na imagem");
    }

    #[Test]
    public function it_applies_the_transformations_to_the_image_with_moderation_detection_by_ai(): void
    {
        $transformation = [
            'width' => 500,
            'height' => 500,
            'format' => 'jpeg',
            'ai_analisis' => [
                'safe' => [
                    'is_safe' => false,
                ]
            ]
        ];

        $imageProcessed = $this->service->transformImage($this->image->getContent(), $transformation, $transformation['ai_analisis']);

        $imageDecode = ImageManager::imagick()->read($imageProcessed);
        $originalImageDecode = ImageManager::imagick()->read($this->image->getContent());

        $originalPixelColor = $originalImageDecode->pickColor(100, 100);
        $imageProcessedPixelColor = $imageDecode->pickColor(100, 100);

        $this->assertNotEquals($originalPixelColor->toString(), $imageProcessedPixelColor->toString(), "O blur não foi aplicado na imagem");
    }

    /**
     * @return string
     */
    private function createTestImageWithTwoColors(): string
    {
        $image = imagecreatetruecolor(200, 200);
        $blue = imagecolorallocate($image, 0, 0, 255);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $blue);
        imagefilledrectangle($image, 50, 50, 150, 150, $red);

        ob_start();
        imagepng($image);
        $content = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        return $content;
    }
}
