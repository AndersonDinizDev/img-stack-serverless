<?php

namespace App\Http\Services;

use App\Exceptions\ImageProcessingFailureException;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;

class ImageProcessingService
{
    /**
     * Aplica as transformações na imagem conforma os parâmetros recebidos
     * @param $imageContent
     * @param array $transformations
     * @param array $imageCheck
     * @return EncodedImageInterface
     * @throws ImageProcessingFailureException
     */
    public function transformImage($imageContent, array $transformations, array $imageCheck = []): EncodedImageInterface
    {

        try {
            $image = ImageManager::imagick()->read($imageContent);

            $transform = $transformations['transform'] ?? 'resize';

            if ($transform === 'resize' && isset($transformations['width'], $transformations['height'])) {
                $image->resize($transformations['width'], $transformations['height'], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            if (isset($imageCheck['safe']['is_safe']) && $imageCheck['safe']['is_safe'] === false) {
                $image->blur(50);
            }

            if (isset($imageCheck['faces']['is_face']) && $imageCheck['faces']['is_face'] === true) {
                $faceWidth = (int)$transformations['width'] * $imageCheck['faces']['labels'][0]['Width'];
                $faceHeight = (int)$transformations['height'] * $imageCheck['faces']['labels'][0]['Height'];
                $faceLeft = (int)$transformations['width'] * $imageCheck['faces']['labels'][0]['Left'];
                $faceTop = (int)$transformations['height'] * $imageCheck['faces']['labels'][0]['Top'];

                $imageClone = clone $image;
                $imageClone->crop($faceWidth, $faceHeight, $faceLeft, $faceTop);
                $imageClone->blur(50);
                $image->place($imageClone, 'top-left', $faceLeft, $faceTop);
            }

            $encoder = $this->selectFormatEncoder(
                $transformations['format'] ?? 'jpeg',
                $transformations['quality'] ?? 80
            );

        } catch (\Intervention\Image\Exceptions\DecoderException $e) {
            throw new ImageProcessingFailureException("Erro ao decodificar a imagem");
        } catch (\Intervention\Image\Exceptions\EncoderException $e) {
            throw new ImageProcessingFailureException("Erro ao codificar a imagem");
        } catch (\Intervention\Image\Exceptions\RuntimeException $e) {
            throw new ImageProcessingFailureException("Erro ao processar a imagem");
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new ImageProcessingFailureException("Ocorreu um erro ao processar a imagem. Tente novamente mais tarde");
        }

        return $image->encode($encoder);
    }

    /**
     * Define o formato da imagem
     * @param string $format
     * @param int $quality
     * @return PngEncoder|WebpEncoder|JpegEncoder
     * @throws ImageProcessingFailureException
     */
    private function selectFormatEncoder(string $format, int $quality = 80): PngEncoder|WebpEncoder|JpegEncoder
    {
        try {
            return match ($format) {
                'png' => new PngEncoder(),
                'webp' => new WebpEncoder(quality: $quality),
                default => new JpegEncoder(quality: $quality)
            };
        } catch (\Intervention\Image\Exceptions\EncoderException $e) {
            throw new ImageProcessingFailureException("Erro ao codificar a imagem");
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new ImageProcessingFailureException("Ocorreu um erro ao processar a imagem. Tente novamente mais tarde");
        }
    }
}
