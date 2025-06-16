<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Services\StorageService;
use App\Http\Services\WorkerService;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Interfaces\EncodedImageInterface;

class ProcessImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    protected array $jobData;

    public function __construct(array $jobData)
    {
        $this->jobData = $jobData;
    }

    /**
     * @throws Exception
     */
    public function handle(WorkerService $workerService): void
    {
        $jobId = $this->jobData['job_id'];
        $cacheKey = $this->jobData['cache_key'];

        try {
            $workerService->updateJobProgress($jobId, 10, 'processing');

            $imageContent = file_get_contents($this->jobData['image_url']);

            if (!$imageContent) {
                throw new Exception('Failed to download image');
            }
            $workerService->updateJobProgress($jobId, 50);

            $processedImage = $this->transformImage($imageContent, $this->jobData['transformations'], $this->jobData['image_check']);;
            $workerService->updateJobProgress($jobId, 80);

            $cachePath = $cacheKey . '.' . $this->jobData['transformations']['format'];
            if (!StorageService::saveFile('s3_cache', $cachePath, $processedImage)) {
                throw new Exception('Failed to save processed image');
            }

            $workerService->saveJobStatus($cacheKey, $jobId, 'completed', 100);
            Log::info("Job completed successfully: {$jobId}");
        } catch (Exception $e) {
            Log::error("Job failed: {$e->getMessage()}", [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            $workerService->saveJobStatus($cacheKey, $jobId, 'failed', 0, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Aplica as transformações na imagem conforma os parâmetros recebidos
     * @param $imageContent
     * @param array $transformations
     * @return EncodedImageInterface
     */
    private function transformImage($imageContent, array $transformations, array $imageCheck): EncodedImageInterface
    {
        $image = ImageManager::imagick()->read($imageContent);

        $transform = $transformations['transform'] ?? 'resize';

        if ($transform === 'resize' && isset($transformations['width'], $transformations['height'])) {
            $image->resize($transformations['width'], $transformations['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        if ($imageCheck['is_safe'] === false) {
            $image->blur(50);
        }

        $encoder = $this->selectFormatEncoder(
            $transformations['format'] ?? 'jpeg',
            $transformations['quality'] ?? 80
        );

        return $image->encode($encoder);
    }

    /**
     * Define o formato da imagem
     * @param string $format
     * @param int $quality
     * @return PngEncoder|WebpEncoder|JpegEncoder
     */
    private function selectFormatEncoder(string $format, int $quality = 80): PngEncoder|WebpEncoder|JpegEncoder
    {
        return match ($format) {
            'png' => new PngEncoder(),
            'webp' => new WebpEncoder(quality: $quality),
            default => new JpegEncoder(quality: $quality)
        };
    }
}
