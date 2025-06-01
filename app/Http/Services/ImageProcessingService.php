<?php

namespace App\Http\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use App\Http\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ImageProcessingService
{

    /**
     * Processa a imagem recebida e redireciona para o cache da imagem processada
     * @param object $data
     * @return array|RedirectResponse
     * @throws \Exception
     */
    public function processImage(object $data): JsonResponse | RedirectResponse
    {
        $cacheKey = md5($data->url . json_encode($data->all()));
        $cachePath = "{$cacheKey}.{$data->format}";


        try {
            if (StorageService::searchFile('s3_cache', $cachePath)) {
                return redirect()->to(StorageService::getUrl('s3_cache', $cachePath));
            };
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return response()->json([
            'key' => $cacheKey,
            'path' => $cachePath
        ]);
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
