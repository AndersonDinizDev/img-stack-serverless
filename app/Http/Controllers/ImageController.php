<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessImageRequest;
use App\Http\Services\ImageProcessingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class ImageController extends Controller
{

    public function __construct(private ImageProcessingService $imgService)
    {
        $this->imgService = $imgService;
    }

    /**
     * @throws Exception
     */
    public function index(ProcessImageRequest $request): JsonResponse
    {
        $imageData = $this->imgService->processImage($request);

        return response()->json($imageData);
    }
}
