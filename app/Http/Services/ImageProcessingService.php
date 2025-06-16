<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ImageProcessingService
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
            'quality' => $data->i_q ?? 80
        ];

        $cacheKey = md5(json_encode($cacheKeyParams));
        $cachePath = "{$cacheKey}.{$cacheKeyParams['format']}";

        try {
            if (StorageService::searchFile('s3_cache', $cachePath)) {
                $signedUrl = StorageService::getSignerUrl($cachePath);

                return [
                    'status' => 'ready',
                    'url' => $signedUrl
                ];
            }
        } catch (Exception $e) {
            Log::error("Erro ao verificar cache S3", [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        $jobStatus = $this->workerService->getJobStatus($cacheKey);

        if ($jobStatus === null || $jobStatus === 'failed') {
            try {
                $this->workerService->dispatchImageProcessing($data, $cacheKey);
                $jobStatus = 'queued';
            } catch (Exception $e) {
                Log::error("Erro ao processar imagem", [
                    'cache_key' => $cacheKey,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
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
