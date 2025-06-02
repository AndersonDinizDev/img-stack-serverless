<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Storage;
use Aws\CloudFront\UrlSigner;

class StorageService
{
    /**
     * Salva o arquivo em um disco específico
     * @param string $disk
     * @param string $path
     * @param mixed $file
     * @return bool
     */
    public static function saveFile(string $disk, string $path, $file): bool
    {
        try {
            $save = Storage::disk($disk)->put($path, $file);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $save;
    }

    /**
     * Procura e retorna um arquivo específico
     * @param string $disk
     * @param string $path
     * @return bool
     */
    public static function searchFile(string $disk, string $path): bool
    {
        try {
            $search = Storage::disk($disk)->exists($path);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $search;
    }

    /**
     * Retorna a url assinada do objeto
     * @param string $disk
     * @param string $path
     * @return string
     */
    public static function getSignerUrl(string $disk, string $path): string
    {
        $cloudFront = new UrlSigner(env('CLOUDFRONT_KEY_PAIR_ID'), env('CLOUDFRONT_PRIVATE_KEY'));
        $resourceUrl = "https://" . env('CLOUDFRONT_DOMAIN') . "/{$path}";
        try {
            $url = $cloudFront->getSignedUrl($resourceUrl, time() + 3600);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $url;
    }

    public static function getFile(string $disk, string $path): string
    {
        try {
            $file = Storage::disk($disk)->get($path);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $file;
    }
}
