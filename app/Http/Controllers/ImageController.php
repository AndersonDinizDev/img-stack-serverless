<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessImageRequest;
use App\Http\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ImageController extends Controller
{

    public function __construct(private ImageProcessingService $imgService)
    {
        $this->imgService = $imgService;
    }

    public function index(ProcessImageRequest $request): JsonResponse
    {
        try {
            $imageData = $this->imgService->processImage($request);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Ocorreu um problema ao processar a imagem. Tente novamente mais tarde'
            ], 500);
        }

        return response()->json($imageData);
    }
}
