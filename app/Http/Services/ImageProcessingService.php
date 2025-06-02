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
        $file = file_get_contents($data->image);
        $cacheKey = md5($data->image . json_encode($data->all()));
        $cachePath = "{$cacheKey}.{$data->format}";


        try {
            if (StorageService::searchFile('s3_cache', $cachePath)) {
                return redirect()->to(StorageService::getSignerUrl('s3_cache', $cachePath));
            };
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }


        try {
            $imageProcessed = $this->transformImage($file, $data);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }


        try {
            if (StorageService::saveFile('s3_cache', $cachePath, $imageProcessed)) {
                $imageLink = StorageService::getSignerUrl('s3_cache', $cachePath);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return redirect()->to($imageLink);
    }


    /**
     * Modifica a imagem conforme a solicitação
     * @param object $data
     * @return object
     * @throws \Exception
     */
    private function transformImage($file, object $data)
    {
        try {
            $image = ImageManager::imagick()->read($file);

            foreach ($data->transform as $type) {
                if ($type == 'resize') {
                    $image->resize($data->width, $data->height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
            }

            $encoder = $this->selectFormatEncoder($data->format, $data->quality ?? 80);

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
