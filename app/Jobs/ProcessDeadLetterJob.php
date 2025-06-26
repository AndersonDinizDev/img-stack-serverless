<?php

namespace App\Jobs;

use App\Http\Services\WorkerService;
use Exception;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDeadLetterJob
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected array $failedJobData)
    {
        //
    }

    /**
     * Execute the job.
     * @throws Exception
     */
    public function handle(WorkerService $workerService): void
    {
        $jobId = $this->failedJobData['job_id'] ?? null;
        $cacheKey = $this->failedJobData['cache_key'] ?? null;
        $errorMessage = "Job falhou permanentemente e foi processado pela DLQ: {$jobId}";

        Log::critical($errorMessage, [
            'job_id' => $jobId,
            'cache_key' => $cacheKey,
            'original_payload' => $this->failedJobData['original_payload']
        ]);

        $workerService->saveJobStatus($cacheKey, $jobId, 'failed_dlq', 0, $errorMessage);
    }
}
