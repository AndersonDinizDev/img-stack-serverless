<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageService
{
    /**
     * Salva o arquivo em um disco específico.
     * @param string $disk
     * @param string $path
     * @param mixed $file
     * @return string
     */
    public static function saveFile(string $disk, string $path, $file): string
    {
        try {
            $save = Storage::disk($disk)->put($path, $file);
        } catch (\Exception $e) {
            Log::error([
                'message' => 'Erro ao salvar arquivo',
                'disk' => $disk,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Erro ao salvar arquivo: ' . $e->getMessage());
        }

        return Storage::disk($disk)->url($path);
    }
}
