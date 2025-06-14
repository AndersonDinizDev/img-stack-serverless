<?php

namespace App\Http\Services;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Illuminate\Support\Facades\Log;

class WorkerService
{
    private DynamoDbClient $dynamodb;
    private Marshaler $marshaler;

    public function __construct()
    {
        $this->dynamodb = new DynamoDbClient([
            'version' => 'latest',
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1')
        ]);

        $this->marshaler = new Marshaler();
    }

    /**
     * Dispatcha job usando Laravel Queue (que vai para SQS automaticamente)
     *
     * @param object $data
     * @param string $cacheKey
     * @return bool
     */
    public function dispatchImageProcessing(object $data, string $cacheKey): bool
    {
        try {
            $jobData = [
                'job_id' => uniqid('job_', true),
                'cache_key' => $cacheKey,
                'image_url' => $data->image,
                'transformations' => [
                    'width' => $data->r_w ?? null,
                    'height' => $data->r_h ?? null,
                    'format' => $data->i_f ?? 'jpeg',
                    'quality' => $data->i_q ?? 80
                ],
                'options' => [
                    'ai_analysis' => $data->ai_analysis ?? false,
                    'smart_crop' => $data->smart_crop ?? false
                ],
                'created_at' => time(),
                'attempts' => 0
            ];

            \App\Jobs\ProcessImageJob::dispatch($jobData);

            $this->saveJobStatus($cacheKey, $jobData['job_id'], 'queued', 0);

            Log::info('Image processing job dispatched via Laravel Queue', [
                'job_id' => $jobData['job_id'],
                'cache_key' => $cacheKey,
                'queue_connection' => env('QUEUE_CONNECTION')
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch image processing job', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Salva status do job no DynamoDB
     *
     * @param string $cacheKey
     * @param string $jobId
     * @param string $status
     * @param int $progress
     * @param string|null $error
     * @return bool
     */
    public function saveJobStatus(string $cacheKey, string $jobId, string $status, int $progress = 0, string $error = null): bool
    {
        try {
            $item = [
                'job_id' => $jobId,
                'cache_key' => $cacheKey,
                'status' => $status,
                'progress' => $progress,
                'created_at' => (string)time(),
                'updated_at' => time(),
                'ttl' => time() + (24 * 60 * 60) // TTL de 24 horas
            ];

            if ($error) {
                $item['error_message'] = $error;
            }

            if ($status === 'completed') {
                $item['completed_at'] = time();
            }

            $this->dynamodb->putItem([
                'TableName' => env('DYNAMODB_JOBS_TABLE'),
                'Item' => $this->marshaler->marshalItem($item)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save job status', [
                'job_id' => $jobId,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Recupera status do job
     *
     * @param string $cacheKey
     * @return ?string
     */
    public function getJobStatus(string $cacheKey): ?string
    {
        try {
            $result = $this->dynamodb->query([
                'TableName' => env('DYNAMODB_JOBS_TABLE'),
                'IndexName' => 'cache_key-updated_at-index',
                'KeyConditionExpression' => 'cache_key = :cache_key',
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':cache_key' => $cacheKey
                ]),
                'ScanIndexForward' => false,
                'Limit' => 1
            ]);

            if (!empty($result['Items'])) {
                $item = $this->marshaler->unmarshalItem($result['Items'][0]);
                return $item['status'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get job status', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Atualiza progresso do job
     *
     * @param string $jobId
     * @param int $progress
     * @param string $status
     * @return bool
     */
    public function updateJobProgress(string $jobId, int $progress, string $status = 'processing'): bool
    {
        try {
            $this->dynamodb->updateItem([
                'TableName' => env('DYNAMODB_JOBS_TABLE'),
                'Key' => $this->marshaler->marshalItem(['job_id' => $jobId]),
                'UpdateExpression' => 'SET progress = :progress, #status = :status, updated_at = :timestamp',
                'ExpressionAttributeNames' => [
                    '#status' => 'status'
                ],
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':progress' => $progress,
                    ':status' => $status,
                    ':timestamp' => time()
                ])
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update job progress', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Limpa jobs antigos
     *
     * @param int $daysOld
     * @return int
     */
    public function cleanupOldJobs(int $daysOld = 7): int
    {
        try {
            $cutoffTime = time() - ($daysOld * 24 * 60 * 60);

            $result = $this->dynamodb->scan([
                'TableName' => env('DYNAMODB_JOBS_TABLE'),
                'FilterExpression' => 'created_at < :cutoff',
                'ExpressionAttributeValues' => $this->marshaler->marshalItem([
                    ':cutoff' => (string)$cutoffTime
                ]),
                'ProjectionExpression' => 'job_id'
            ]);

            $deletedCount = 0;
            foreach ($result['Items'] as $item) {
                $jobId = $this->marshaler->unmarshalItem($item)['job_id'];

                $this->dynamodb->deleteItem([
                    'TableName' => env('DYNAMODB_JOBS_TABLE'),
                    'Key' => $this->marshaler->marshalItem(['job_id' => $jobId])
                ]);

                $deletedCount++;
            }

            Log::info("Cleaned up {$deletedCount} old jobs");
            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old jobs', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
