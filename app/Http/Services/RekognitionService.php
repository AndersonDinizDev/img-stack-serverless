<?php

namespace App\Http\Services;

use App\Exceptions\ImageProcessingFailureException;
use App\Exceptions\InvalidImageException;
use Aws\Exception\AwsException;
use Aws\Rekognition\Exception\RekognitionException;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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
     * @throws ImageProcessingFailureException
     * @throws InvalidImageException
     */
    public function detectModeration(mixed $image): array
    {
        $response = $this->getResponse($image);

        try {
            $checkImage = $this->rekognitionClient->detectModerationLabels([
                'Image' => [
                    'Bytes' => $response->body(),
                ],
                'MinConfidence' => 75,
            ]);
        } catch (RekognitionException $e) {
            match ($e->getAwsErrorCode()) {
                'InvalidImageFormatException' => throw new InvalidImageException("O link da imagem fornecida é inválida ou esta em um formato não suportado."),
                'ProvisionedThroughputExceededException' => throw new ImageProcessingFailureException("O serviço de análise está sobrecarregado. Tente novamente em alguns instantes."),
                default => throw new ImageProcessingFailureException("Ocorreu um erro ao processar a imagem. Tente novamente mais tarde.")
            };
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new ImageProcessingFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
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

    /**
     * Detecta faces na imagem fornecida.
     *
     * @param mixed $image
     * @return array
     * @throws InvalidImageException
     * @throws ImageProcessingFailureException
     */
    public function detectFaces(mixed $image): array
    {
        $response = $this->getResponse($image);

        try {
            $checkImage = $this->rekognitionClient->detectFaces([
                'Image' => [
                    'Bytes' => $response->body(),
                ],
            ]);
        } catch (RekognitionException $e) {
            match ($e->getAwsErrorCode()) {
                'InvalidImageFormatException' => throw new InvalidImageException("O link da imagem fornecida é inválida ou esta em um formato não suportado."),
                'ProvisionedThroughputExceededException' => throw new ImageProcessingFailureException("O serviço de análise está sobrecarregado. Tente novamente em alguns instantes."),
                default => throw new ImageProcessingFailureException("Ocorreu um erro ao processar a imagem. Tente novamente mais tarde.")
            };
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new ImageProcessingFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        $result = $checkImage->get('FaceDetails');

        if (!$result) {
            return [
                'is_face' => false,
            ];
        }

        $labels = array_map(function ($face) {
            return $face['BoundingBox'];
        }, $result);

        return [
            'is_face' => true,
            'labels' => $labels,
        ];
    }

    /**
     * @param mixed $image
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
     * @throws InvalidImageException
     */
    public function getResponse(mixed $image): \Illuminate\Http\Client\Response|\GuzzleHttp\Promise\PromiseInterface
    {
        try {
            $response = Http::timeout(10)->get($image);

            if ($response->failed()) {
                Log::warning('URL da imagem retornou um status de erro.', [
                    'url' => $image,
                    'status' => $response->status()
                ]);

                throw new InvalidImageException("Falha ao obter a imagem fornecida. O servidor retornou um status de erro: " . $response->status());
            }
        } catch (ConnectionException $e) {
            Log::warning('Falha ao obter imagem.', [
                'url' => $image,
                'error' => $e->getMessage()
            ]);

            throw new InvalidImageException("Não foi possível conectar com o servidor da imagem fornecida.");
        }
        return $response;
    }
}
