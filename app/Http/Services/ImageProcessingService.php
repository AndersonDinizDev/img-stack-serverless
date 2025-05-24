<?php

namespace App\Http\Services;

use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;

class ImageProcessingService
{
    /**
     * Salva a imagem no disco e retorna o caminho
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function processImage(array $data): array
    {

        try {
            $image = ImageManager::imagick()->read($data['file']);

            $image->resize(64, 64, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $fileName = $data['filename'] . '.' . $data['filetype'];

            $fileType = $this->selectFormatEncoder($data['filetype']);


            $file = Storage::disk('public')->put($fileName, $image->encode($fileType));
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return [
            'status' => 'ok',
            'filename' => $data['filename'],
            'path' => Storage::url($file),
        ];
    }

    private function selectFormatEncoder(string $filetype)
    {
        switch ($filetype) {
            case 'png':
                return new PngEncoder();
            case 'jpeg':
                return new JpegEncoder();
            case 'webp':
                return new WebpEncoder();
            default:
                return new PngEncoder();
        }
    }
}
