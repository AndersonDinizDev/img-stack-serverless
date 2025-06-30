<?php

namespace App\Jobs;

use App\Http\Services\ImageProcessingService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Services\StorageService;
use App\Http\Services\WorkerService;
use Illuminate\Support\Facades\Log;

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
    public function handle(WorkerService $workerService, ImageProcessingService $imageService): void
    {
        $jobId = $this->jobData['job_id'];
        $cacheKey = $this->jobData['cache_key'];

        try {
            $workerService->updateJobProgress($jobId, 10);

            $imageContent = file_get_contents($this->jobData['image_url']);

            $workerService->updateJobProgress($jobId, 50);

            $processedImage = $imageService->transformImage($imageContent, $this->jobData['transformations'], $this->jobData['image_check'] ?? []);

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

}
