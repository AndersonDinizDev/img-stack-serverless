<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessImageRequest;
use App\Http\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    private $imgService;

    public function __construct(ImageProcessingService $imgService)
    {
        $this->imgService = $imgService;
    }

    public function store(ProcessImageRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $imageData = $this->imgService->processImage($data);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao processar a imagem: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => $imageData
        ]);
    }
}
