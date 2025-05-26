<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Support\Str;

class ImageProcessingService
{
    private $storage;

    public function __construct(StorageService $storage)
    {
        $this->storage = $storage;
    }
    /**
     * Salva a imagem no disco e retorna o caminho
     * @param object $data
     * @return array
     * @throws \Exception
     */
    public function processImage(object $data): array
    {
        $file = $data->file('file');
        $imageId = (string) Str::uuid();
        $format = $data->input('format', $file->extension());
        $originalPath = "original/{$imageId}.{$format}";
        $cloudFrontDomain = env('CLOUDFRONT_DOMAIN');

        $params = [
            'format' => $format,
            'quality' => $data->quality ?? 80,
            'width' => $data->width,
            'height' => $data->height,
            'transform' => $data->transform
        ];

        try {
            $this->storage->saveFile('s3', $originalPath, $file);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }

        try {
            $image = $this->transformImage($file, $params);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }

        try {
            $cachePath = "processed/{$imageId}/{$params['width']}x{$params['height']}_q{$params['quality']}.{$format}";
            $imageCache = $this->storage->saveFile('s3_cache', $cachePath, $image);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }

        return [
            'urls' => [
                'cloudfront' => "https://{$cloudFrontDomain}/{$cachePath}",
                'dynamic' => "https://{$cloudFrontDomain}/v1/image/image_id={$imageId}?width={$params['width']}&height={$params['height']}&quality={$params['quality']}&format={$format}"
            ]
        ];
    }

    /**
     * Modifica a imagem conforme a solicitação
     * @param object $file
     * @param array $params
     * @return object
     * @throws \Exception
     */
    private function transformImage(object $file, array $params)
    {
        try {
            $image = ImageManager::imagick()->read($file);

            if ($params['transform'] == 'resize') {
                $image->resize($params['width'], $params['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            $encoder = $this->selectFormatEncoder($params['format'], $params['quality'] ?? 80);

            return $image->encode($encoder);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Define o formato e qualidade da imagem
     * @param string $format
     * @param int $quality
     * @return object
     */
    private function selectFormatEncoder(string $format, int $quality = 80)
    {
        switch ($format) {
            case 'png':
                return new PngEncoder();
            case 'jpeg':
                return new JpegEncoder(quality: $quality);
            case 'webp':
                return new WebpEncoder(quality: $quality);
            default:
                return new PngEncoder();
        }
    }
}
