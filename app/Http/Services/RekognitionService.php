<?php

namespace App\Http\Services;

use App\Exceptions\AwsServiceFailureException;
use App\Exceptions\RekognitionFailureException;
use Aws\Exception\AwsException;
use Aws\Rekognition\Exception\RekognitionException;
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
     * @throws RekognitionFailureException|AwsServiceFailureException
     */
    public function detectModeration(mixed $image): array
    {

        try {
            $checkImage = $this->rekognitionClient->detectModerationLabels([
                'Image' => [
                    'Bytes' => file_get_contents($image),
                ],
                'MinConfidence' => 75,
            ]);
        } catch (RekognitionException $e) {
            self::handleException($e);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
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
     * @throws RekognitionFailureException|AwsServiceFailureException
     */
    public function detectFaces(mixed $image): array
    {

        try {
            $checkImage = $this->rekognitionClient->detectFaces([
                'Image' => [
                    'Bytes' => file_get_contents($image),
                ],
            ]);
        } catch (RekognitionException $e) {
            self::handleException($e);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
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
     * @param RekognitionException $e
     * @return void
     * @throws RekognitionFailureException
     */
    public function handleException(RekognitionException $e): void
    {
        match ($e->getAwsErrorCode()) {
            'InvalidImageFormatException' => throw new RekognitionFailureException("O link da imagem fornecida é inválida ou esta em um formato não suportado."),
            'ProvisionedThroughputExceededException' => throw new RekognitionFailureException("O serviço de análise está sobrecarregado. Tente novamente em alguns instantes."),
            'ImageTooLargeException', 'ValidationException' => throw new RekognitionFailureException("A imagem é muito grande para ser processada."),
            'AccessDeniedException' => throw new RekognitionFailureException("Sem permissão para acessar o serviço de análise."),
            'InvalidParameterException' => throw new RekognitionFailureException("Parâmetros inválidos na requisição."),
            'ThrottlingException' => throw new RekognitionFailureException("Muitas requisições simultâneas. Tente novamente em alguns instantes."),
            'ServiceQuotaExceededException' => throw new RekognitionFailureException("Cota de serviço excedida. Tente novamente mais tarde."),
            default => throw new RekognitionFailureException("Ocorreu um erro ao processar a imagem. Tente novamente mais tarde.")
        };
    }
}
