<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\StorageService;
use App\Http\Services\WorkerService;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;

class ProcessImageWorker extends Command
{
    protected $signature = 'queue:work-images';
    protected $description = 'Process image transformation jobs from SQS';

    private WorkerService $workerService;

    public function __construct()
    {
        parent::__construct();
        $this->workerService = new WorkerService();
    }

    public function handle()
    {
        // MUDANÇA: Para SQS com Bref, os dados chegam via STDIN
        $input = file_get_contents('php://stdin');
        $event = json_decode($input, true);

        if (empty($event['Records'])) {
            $this->error('No SQS records found in event');
            return 1;
        }

        // Processa cada mensagem do SQS (resto igual)
        foreach ($event['Records'] as $record) {
            $jobData = json_decode($record['body'], true);

            if (!$jobData) {
                $this->error('Invalid job data in SQS message');
                continue;
            }

            $this->processJob($jobData);
        }

        return 0;
    }

    // Todo o resto do código permanece EXATAMENTE igual
    private function processJob(array $jobData)
    {
        $jobId = $jobData['job_id'];
        $cacheKey = $jobData['cache_key'];

        try {
            $this->info("Processing job: {$jobId}");

            // 1. Atualiza status para "processing"
            $this->workerService->updateJobProgress($jobId, 10, 'processing');

            // 2. Baixa imagem original
            $this->line('Downloading image...');
            $imageContent = file_get_contents($jobData['image_url']);

            if (!$imageContent) {
                throw new \Exception('Failed to download image');
            }

            $this->workerService->updateJobProgress($jobId, 30);

            // 3. Processa transformações
            $this->line('Transforming image...');
            $processedImage = $this->transformImage($imageContent, $jobData['transformations']);
            $this->workerService->updateJobProgress($jobId, 70);

            // 4. Salva no S3 Cache
            $this->line('Saving to cache...');
            $cachePath = $cacheKey . '.' . $jobData['transformations']['format'];

            if (!StorageService::saveFile('s3_cache', $cachePath, $processedImage)) {
                throw new \Exception('Failed to save processed image');
            }

            $this->workerService->updateJobProgress($jobId, 90);

            // 5. Finaliza job
            $this->workerService->saveJobStatus($cacheKey, $jobId, 'completed', 100);

            $this->info("Job completed successfully: {$jobId}");

        } catch (\Exception $e) {
            $this->error("Job failed: {$e->getMessage()}");
            $this->workerService->saveJobStatus($cacheKey, $jobId, 'failed', 0, $e->getMessage());
        }
    }

    private function transformImage($imageContent, array $transformations)
    {
        $image = ImageManager::imagick()->read($imageContent);

        $transform = $transformations['transform'] ?? 'resize';

        if ($transform === 'resize' && isset($transformations['width'], $transformations['height'])) {
            $image->resize($transformations['width'], $transformations['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $encoder = $this->selectFormatEncoder(
            $transformations['format'] ?? 'webp',
            $transformations['quality'] ?? 80
        );

        return $image->encode($encoder);
    }

    private function selectFormatEncoder(string $format, int $quality = 80)
    {
        return match($format) {
            'png' => new PngEncoder(),
            'jpeg' => new JpegEncoder(quality: $quality),
            'webp' => new WebpEncoder(quality: $quality),
            default => new WebpEncoder(quality: $quality)
        };
    }
}
