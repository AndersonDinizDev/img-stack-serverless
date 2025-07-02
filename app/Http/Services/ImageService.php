<?php

namespace App\Http\Services;

use Exception;

class ImageService
{
    public function __construct(private WorkerService $workerService)
    {
    }

    /**
     * Processa imagem de forma assíncrona
     * @param object $data
     * @return array
     * @throws Exception
     */
    public function processImage(object $data): array
    {
        $cacheKeyParams = [
            'image' => $data->image,
            'width' => $data->r_w ?? null,
            'height' => $data->r_h ?? null,
            'format' => $data->i_f ?? 'webp',
            'quality' => $data->i_q ?? 80,
            'ai_analysis' => $data->ai ?? null,
        ];

        $cacheKey = md5(json_encode($cacheKeyParams));
        $cachePath = "{$cacheKey}.{$cacheKeyParams['format']}";

        if (StorageService::searchFile('s3_cache', $cachePath)) {
            $signedUrl = CloudFrontService::getSignerUrl($cachePath);

            return [
                'status' => 'ready',
                'url' => $signedUrl
            ];
        }

        $jobStatus = $this->workerService->getJobStatus($cacheKey);

        if ($jobStatus === null || $jobStatus === 'failed') {
            $this->workerService->dispatchImageProcessing($data, $cacheKey);
            $jobStatus = 'queued';
        }

        return [
            'status' => $jobStatus,
            'cache_key' => $cacheKey,
            'retry_after' => $this->getRetryAfter($jobStatus),
            'code' => 202
        ];
    }

    /**
     * Calcula tempo de retry baseado no status
     * @param string $status
     * @return int
     */
    private function getRetryAfter(string $status): int
    {
        return match ($status) {
            'queued' => 2,
            'processing' => 1,
            'completed' => 1,
            default => 3
        };
    }
}
