<?php

namespace App\Http\Services;

use App\Exceptions\FailedJobException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkerService
{

    public function __construct(private DynamoDbClient $dynamodb, private Marshaler $marshaler, private RekognitionService $rekognitionService)
    {
    }

    /**
     * Despacha o job usando Laravel Queue (que vai para SQS automaticamente)
     *
     * @param object $data
     * @param string $cacheKey
     * @return bool
     * @throws Exception
     */
    public function dispatchImageProcessing(object $data, string $cacheKey): bool
    {
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
                'ai_analysis' => $data->ai ?? null
            ],
            'created_at' => time(),
            'attempts' => 0
        ];

        if ($data->ai) {

            $check = [];

            foreach ($data->ai as $analysis) {
                match ($analysis) {
                    'faces' => $check['faces'] = $this->rekognitionService->detectFaces($jobData['image_url']),
                    'safe' => $check['safe'] = $this->rekognitionService->detectModeration($jobData['image_url']),
                    default => null
                };
                $jobData['image_check'] = $check;
            }
        }

        try {
            \App\Jobs\ProcessImageJob::dispatch($jobData);

            $this->saveJobStatus($cacheKey, $jobData['job_id'], 'queued', 0);
            return true;

        } catch (Exception $e) {
            Log::error('Falha crítica ao tentar despachar job ou salvar status.', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new FailedJobException(
                'Não foi possível enviar o job para a fila de processamento. Tente novamente mais tarde.',
                500,
                $e
            );
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
     * @throws Exception
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
        } catch (Exception $e) {
            Log::error('Falha ao salvar status do job', [
                'job_id' => $jobId,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Recupera status do job
     *
     * @param string $cacheKey
     * @return ?string
     * @throws Exception
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
        } catch (Exception $e) {
            Log::error('Falha ao obter status do job', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Atualiza progresso do job
     *
     * @param string $jobId
     * @param int $progress
     * @param string $status
     * @return bool
     * @throws Exception
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
        } catch (Exception $e) {
            Log::error('Falha ao atualizar progresso do job', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
