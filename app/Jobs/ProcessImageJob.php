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

    public function __construct(protected array $jobData)
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
            $workerService->updateJobProgress($jobId, 10);

            $imageContent = file_get_contents($this->jobData['image_url']);

            $workerService->updateJobProgress($jobId, 50);

            $processedImage = $this->transformImage($imageContent, $this->jobData['transformations'], $this->jobData['image_check'] ?? []);;
            $workerService->updateJobProgress($jobId, 80);

            $cachePath = $cacheKey . '.' . $this->jobData['transformations']['format'];

            StorageService::saveFile('s3_cache', $cachePath, $processedImage);

            $workerService->saveJobStatus($cacheKey, $jobId, 'completed', 100);

            Log::info("Job finalizado com sucesso: {$jobId}");
        } catch (Exception $e) {
            Log::error("Falha no job: {$e->getMessage()}", [
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
     * @param array $imageCheck
     * @return EncodedImageInterface
     */
    private function transformImage($imageContent, array $transformations, array $imageCheck = []): EncodedImageInterface
    {
        $image = ImageManager::imagick()->read($imageContent);

        $transform = $transformations['transform'] ?? 'resize';

        if ($transform === 'resize' && isset($transformations['width'], $transformations['height'])) {
            $image->resize($transformations['width'], $transformations['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        if (isset($imageCheck['safe']['is_safe']) && $imageCheck['safe']['is_safe'] === false) {
            $image->blur(50);
        }

        if (isset($imageCheck['faces']['is_face']) && $imageCheck['faces']['is_face'] === true) {
            $faceWidth = $transformations['width'] * $imageCheck['faces']['labels'][0]['Width'];
            $faceHeight = $transformations['height'] * $imageCheck['faces']['labels'][0]['Height'];
            $faceLeft = $transformations['width'] * $imageCheck['faces']['labels'][0]['Left'];
            $faceTop = $transformations['height'] * $imageCheck['faces']['labels'][0]['Top'];

            $imageClone = clone $image;
            $imageClone->crop($faceWidth, $faceHeight, $faceLeft, $faceTop);
            $imageClone->blur(50);
            $image->place($imageClone, 'top-left', $faceLeft, $faceTop);
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
