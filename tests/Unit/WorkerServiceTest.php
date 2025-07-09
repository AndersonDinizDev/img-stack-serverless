<?php

namespace Tests\Unit;

use App\Http\Services\DynamoDBService;
use App\Http\Services\RekognitionService;
use App\Http\Services\WorkerService;
use App\Jobs\ProcessImageJob;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class WorkerServiceTest extends TestCase
{

    #[Test]
    public function it_dispatches_a_job_with_ai_faces_detection(): void
    {

        Queue::fake();

        $requestData = (object)[
            'image' => 'http://example.com/image.jpg',
            'r_w' => 300,
            'r_h' => 200,
            'ai' => ['faces']
        ];
        $fakeRekognitionResponse = ['is_face' => true, 'labels' => ['fake-label-data']];
        $cacheKey = 'cache-key';

        $rekognitionMock = $this->mock(RekognitionService::class);
        $dynamoMock = $this->mock(DynamoDbService::class);

        $rekognitionMock->shouldReceive('detectFaces')
            ->once()
            ->with($requestData->image)
            ->andReturn($fakeRekognitionResponse);

        $dynamoMock->shouldReceive('createItem')
            ->once()
            ->with(config('services.dynamodb.tables.jobs'), Mockery::any());

        $workerService = new WorkerService($dynamoMock, $rekognitionMock);

        $result = $workerService->dispatchImageProcessing($requestData, $cacheKey);

        $this->assertTrue($result);

        Queue::assertPushed(ProcessImageJob::class, function ($job) use ($fakeRekognitionResponse) {
            return $job->getJobData()['image_check']['faces'] === $fakeRekognitionResponse;
        });
    }

    #[Test]
    public function it_dispatches_a_job_with_ai_moderation_detection(): void
    {
        Queue::fake();

        $respondeData = (object)[
            'image' => 'http://example.com/image.jpg',
            'r_w' => 100,
            'r_h' => 100,
            'ai' => ['safe']
        ];

        $fakeRekognitionResponse = [
            'safe' => [
                'is_safe' => false,
                'labels' => ['fake-label-data']
            ]
        ];

        $cacheKey = 'cache-key';

        $rekognitionMock = $this->mock(RekognitionService::class);
        $dynamoMock = $this->mock(DynamoDbService::class);

        $rekognitionMock->shouldReceive('detectModeration')
            ->once()
            ->with($respondeData->image)
            ->andReturn($fakeRekognitionResponse);

        $dynamoMock->shouldReceive('createItem')
            ->once()
            ->with(config('services.dynamodb.tables.jobs'), Mockery::any());

        $workerService = new WorkerService($dynamoMock, $rekognitionMock);

        $result = $workerService->dispatchImageProcessing($respondeData, $cacheKey);

        $this->assertTrue($result);

        Queue::assertPushed(ProcessImageJob::class, function ($job) use ($fakeRekognitionResponse) {
            return $job->getJobData()['image_check']['safe'] === $fakeRekognitionResponse;
        });
    }
}
