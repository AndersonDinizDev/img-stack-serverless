<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessImageRequest;
use App\Http\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ImageController extends Controller
{
    private $imgService;

    public function __construct(ImageProcessingService $imgService)
    {
        $this->imgService = $imgService;
    }

    public function index(ProcessImageRequest $request): JsonResponse | RedirectResponse | Response
    {

        try {
            $imageData = $this->imgService->processImage($request);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao processar a imagem: ' . $e->getMessage()
            ], 500);
        }

        return $imageData;
    }
}
