<?php

namespace App\Http\Services;

use Aws\Exception\AwsException;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;

class RekognitionService

{

    public function __construct(private RekognitionClient $rekognitionClient)
    {
    }

    /**
     * Analisa a imagem fornecida para detectar rótulos de moderação indicando conteúdo inadequado ou restrito.
     *
     * @param mixed $image
     * @return array
     */
    public function moderateImage(mixed $image): array
    {
        try {
            $checkImage = $this->rekognitionClient->detectModerationLabels([
                'Image' => [
                    'Bytes' => file_get_contents($image),
                ],
                'MinConfidence' => 75,
            ]);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw $e;
        }

        $result = $checkImage->get('ModerationLabels');

        if (!$result) {
            return [
                'is_safe' => true
            ];
        }

        return [
            'is_safe' => false,
            'labels' => $result
        ];
    }
}
