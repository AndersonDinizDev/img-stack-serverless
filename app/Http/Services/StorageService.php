<?php

namespace App\Http\Services;

use App\Exceptions\CloudFrontFailureException;
use App\Exceptions\ImageProcessingFailureException;
use App\Exceptions\StorageImageFailureException;
use Aws\CloudFront\Exception\CloudFrontException;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Aws\CloudFront\UrlSigner;

class StorageService
{
    /**
     * Salva o arquivo num disco específico
     * @param string $disk
     * @param string $path
     * @param mixed $file
     * @return bool
     * @throws StorageImageFailureException
     * @throws ImageProcessingFailureException
     */
    public static function saveFile(string $disk, string $path, mixed $file): bool
    {
        try {
            $save = Storage::disk($disk)->put($path, $file);
        } catch (S3Exception $e) {
            self::handleS3Exception($e, 'salvar');
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new ImageProcessingFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return $save;
    }

    /**
     * Procura e retorna um arquivo específico
     * @param string $disk
     * @param string $path
     * @return bool
     * @throws StorageImageFailureException
     * @throws ImageProcessingFailureException
     */
    public static function searchFile(string $disk, string $path): bool
    {
        try {
            $search = Storage::disk($disk)->exists($path);
        } catch (S3Exception $e) {
            self::handleS3Exception($e, 'procurar');
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new ImageProcessingFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return $search;
    }

    /**
     * Retorna a url assinada do objeto
     * @param string $path
     * @return string
     * @throws ImageProcessingFailureException
     * @throws CloudFrontFailureException
     */
    public static function getSignerUrl(string $path): string
    {
        $cloudFront = new UrlSigner(env('CLOUDFRONT_KEY_PAIR_ID'), env('CLOUDFRONT_PRIVATE_KEY'));
        $resourceUrl = "https://" . env('CLOUDFRONT_DOMAIN') . "/{$path}";
        try {
            $url = $cloudFront->getSignedUrl($resourceUrl, time() + 3600);
        } catch (CloudFrontException $e) {
            match ($e->getAwsErrorCode()) {
                'InvalidSignature' => throw new CloudFrontFailureException("Falha na assinatura do URL. Tente novamente mais tarde."),
                'InvalidKeyPairId' => throw new CloudFrontFailureException("ID do par de chaves inválido."),
                'AccessDenied' => throw new CloudFrontFailureException("Acesso negado ao CloudFront."),
                'NoSuchDistribution' => throw new CloudFrontFailureException("Distribuição CloudFront não encontrada."),
                'MalformedPrivateKey' => throw new CloudFrontFailureException("Chave privada com formato inválido."),
                'InvalidArgument' => throw new CloudFrontFailureException("Argumento inválido na requisição."),
                default => throw new ImageProcessingFailureException("Ocorreu um erro ao processar a imagem. Tente novamente mais tarde.")
            };
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new ImageProcessingFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return $url;
    }

    /**
     * Retorna um arquivo de um disco específico
     * @param string $disk
     * @param string $path
     * @return string
     * @throws StorageImageFailureException
     * @throws ImageProcessingFailureException
     */
    public static function getFile(string $disk, string $path): string
    {
        try {
            $file = Storage::disk($disk)->get($path);
        } catch (S3Exception $e) {
            self::handleS3Exception($e);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new ImageProcessingFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return $file;
    }

    /**
     * @param S3Exception $e
     * @param string $action
     * @return void
     * @throws StorageImageFailureException
     */
    private static function handleS3Exception(S3Exception $e, string $action = 'acessar'): void
    {
        match ($e->getAwsErrorCode()) {
            'NoSuchBucket' => throw new StorageImageFailureException("Bucket não encontrado"),
            'NoSuchKey' => throw new StorageImageFailureException("Arquivo não encontrado"),
            'AccessDenied' => throw new StorageImageFailureException("Acesso negado ao bucket"),
            'InvalidAccessKeyId' => throw new StorageImageFailureException("Credenciais inválidas"),
            'SignatureDoesNotMatch' => throw new StorageImageFailureException("Assinatura inválida"),
            'NetworkConnection' => throw new StorageImageFailureException("Erro de conexão com o S3"),
            default => throw new StorageImageFailureException("Erro ao {$action} o arquivo")
        };
    }
}
