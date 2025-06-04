<?php

namespace App\Http\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use App\Http\Services\StorageService;
use App\Http\Services\SkeletonService;
use App\Http\Services\WorkerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Intervention\Image\Interfaces\EncodedImageInterface;

class ImageProcessingService
{
    private SkeletonService $skeletonService;
    private WorkerService $workerService;

    public function __construct()
    {
        $this->skeletonService = new SkeletonService();
        $this->workerService = new WorkerService();
    }

    /**
     * Processa a imagem com skeleton inteligente + worker
     *
     * @param object $data
     * @return JsonResponse|RedirectResponse|Response
     */
    public function processImage(object $data): JsonResponse | RedirectResponse | Response
    {
        $cacheKey = md5($data->image . json_encode($data->all()));
        $cachePath = "{$cacheKey}.{$data->format}";

        try {
            if (StorageService::searchFile('s3_cache', $cachePath)) {
                return redirect()->to(StorageService::getSignerUrl($cachePath));
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $shouldUseAsync = $this->shouldProcessAsynchronously($data);

        if ($shouldUseAsync) {
            return $this->processAsynchronously($data, $cacheKey, $cachePath);
        } else {
            return $this->processSynchronously($data, $cachePath);
        }
    }

    /**
     * Determina se deve usar processamento assíncrono
     *
     * @param object $data
     * @return bool
     */
    private function shouldProcessAsynchronously(object $data): bool
    {
        if (isset($data->skeleton) && $data->skeleton !== 'off') {
            return true;
        }

        if (isset($data->ai_analysis) || $data->transform === 'smart_crop') {
            return true;
        }

        if (isset($data->batch) && is_array($data->batch)) {
            return true;
        }

        if ($this->isLikelyLargeImage($data->image)) {
            return true;
        }

        return false;
    }

    /**
     * Estima se imagem é grande baseado na URL
     *
     * @param string $url
     * @return bool
     */
    private function isLikelyLargeImage(string $url): bool
    {
        $indicators = ['original', 'full', 'raw', 'master', 'high-res', '4k', 'hd'];

        foreach ($indicators as $indicator) {
            if (stripos($url, $indicator) !== false) {
                return true;
            }
        }

        try {
            $headers = get_headers($url, 1);
            if (isset($headers['Content-Length'])) {
                $size = intval($headers['Content-Length']);
                return $size > (3 * 1024 * 1024); // >3MB
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * Processamento assíncrono com skeleton
     *
     * @param object $data
     * @param string $cacheKey
     * @param string $cachePath
     * @return JsonResponse|Response|RedirectResponse
     */
    private function processAsynchronously(object $data, string $cacheKey, string $cachePath): JsonResponse | Response | RedirectResponse
    {
        $jobStatus = $this->workerService->getJobStatus($cacheKey);

        if ($jobStatus === null) {
            $this->workerService->dispatchImageProcessing($data, $cacheKey);
        } elseif ($jobStatus === 'completed') {
            try {
                if (StorageService::searchFile('s3_cache', $cachePath)) {
                    return redirect()->to(StorageService::getSignerUrl($cachePath));
                }
            } catch (\Exception $e) {
                $this->workerService->dispatchImageProcessing($data, $cacheKey);
            }
        }

        $skeleton = $this->skeletonService->generateSkeleton(
            $data->width ?? 400,
            $data->height ?? 300,
            $data->skeleton ?? 'auto'
        );

        return response($skeleton, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Processing-Status' => 'generating',
            'X-Cache-Key' => $cacheKey
        ]);
    }

    /**
     * Processamento síncrono da imagem
     *
     * @param object $data
     * @param string $cachePath
     */
    private function processSynchronously(object $data, string $cachePath)
    {
        try {
            $file = file_get_contents($data->image);
            $imageProcessed = $this->transformImage($file, $data);

            if (StorageService::saveFile('s3_cache', $cachePath, $imageProcessed)) {
                $imageLink = StorageService::getSignerUrl($cachePath);
                return redirect()->to($imageLink);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Modifica a imagem conforme a solicitação
     *
     * @param $file
     * @param object $data
     * @return EncodedImageInterface
     */
    private function transformImage($file, object $data): EncodedImageInterface
    {
        try {
            $image = ImageManager::imagick()->read($file);

            if ($data->transform == 'resize') {
                $image->resize($data->width, $data->height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            $encoder = $this->selectFormatEncoder($data->format, $data->quality ?? 80);
            return $image->encode($encoder);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Define o formato e qualidade da imagem (método existente)
     *
     * @param string $format
     * @param int $quality
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
