<?php

namespace App\Http\Services;

use App\Exceptions\AwsServiceFailureException;
use App\Exceptions\StorageImageFailureException;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    /**
     * Salva o arquivo num disco específico
     * @param string $disk
     * @param string $path
     * @param mixed $file
     * @return bool
     * @throws StorageImageFailureException|AwsServiceFailureException
     */
    public static function saveFile(string $disk, string $path, mixed $file): bool
    {
        try {
            $save = Storage::disk($disk)->put($path, $file);
        } catch (S3Exception $e) {
            self::handleS3Exception($e, 'salvar');
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return $save;
    }

    /**
     * Procura e retorna um arquivo específico
     * @param string $disk
     * @param string $path
     * @return bool
     * @throws StorageImageFailureException|AwsServiceFailureException
     */
    public static function searchFile(string $disk, string $path): bool
    {
        try {
            $search = Storage::disk($disk)->exists($path);
        } catch (S3Exception $e) {
            self::handleS3Exception($e, 'procurar');
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
        }

        return $search;
    }

    /**
     * Retorna um arquivo de um disco específico
     * @param string $disk
     * @param string $path
     * @return string
     * @throws StorageImageFailureException|AwsServiceFailureException
     */
    public static function getFile(string $disk, string $path): string
    {
        try {
            $file = Storage::disk($disk)->get($path);
        } catch (S3Exception $e) {
            self::handleS3Exception($e);
        } catch (AwsException $e) {
            Log::error($e->getMessage());
            throw new AwsServiceFailureException("Falha na comunicação com os serviços. Tente novamente mais tarde.");
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
            default => throw new StorageImageFailureException("Erro ao {$action} o arquivo. Tente novamente mais tarde.")
        };
    }
}
