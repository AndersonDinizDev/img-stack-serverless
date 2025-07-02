<?php

namespace App\Http\Services;

use App\Exceptions\AwsServiceFailureException;
use App\Exceptions\CloudFrontFailureException;
use Aws\CloudFront\Exception\CloudFrontException;
use Aws\CloudFront\UrlSigner;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class CloudFrontService
{
    /**
     * Retorna a url assinada do objeto
     * @param string $path
     * @return string
     * @throws CloudFrontFailureException|AwsServiceFailureException
     */
    public static function getSignerUrl(string $path): string
    {
        $cloudFront = new UrlSigner(config('services.cloudfront.keys.pair_id'), config('services.cloudfront.keys.private_key'));
        $resourceUrl = "https://" . config('services.cloudfront.domain') . "/{$path}";
        try {
            $url = $cloudFront->getSignedUrl($resourceUrl, time() + 3600);
        } catch (CloudFrontException $e) {
            self::handleException($e);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return $url;
    }


    /**
     * @throws CloudFrontFailureException
     */
    private function handleException(CloudFrontException $e): void
    {
        match ($e->getAwsErrorCode()) {
            'InvalidSignature' => throw new CloudFrontFailureException("Falha na assinatura do URL. Tente novamente mais tarde."),
            'InvalidKeyPairId' => throw new CloudFrontFailureException("ID do par de chaves inválido."),
            'AccessDenied' => throw new CloudFrontFailureException("Acesso negado ao CloudFront."),
            'NoSuchDistribution' => throw new CloudFrontFailureException("Distribuição CloudFront não encontrada."),
            'MalformedPrivateKey' => throw new CloudFrontFailureException("Chave privada com formato inválido."),
            'InvalidArgument' => throw new CloudFrontFailureException("Argumento inválido na requisição."),
            default => throw new CloudFrontFailureException("Ocorreu ao gerar a assinatura do URL. Tente novamente mais tarde.")
        };
    }
}
