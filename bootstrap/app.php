<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }
            return $request->expectsJson();
        });

        $exceptions->renderable(function (\App\Exceptions\ImageProcessingFailureException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'IMAGE_PROCESSING_FAILURE'
            ], 400);
        });

        $exceptions->renderable(function (\App\Exceptions\InvalidImageException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'INVALID_IMAGE'
            ], 400);
        });

        $exceptions->renderable(function (\App\Exceptions\FailedJobException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'FAILED_JOB'
            ], 400);
        });

        $exceptions->renderable(function (\App\Exceptions\CloudFrontFailureException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'CLOUDFRONT_FAILURE'
            ]);
        });

        $exceptions->renderable(function (\App\Exceptions\StorageImageFailureException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'STORAGE_IMAGE_FAILURE'
            ]);
        });

        $exceptions->renderable(function (\App\Exceptions\AwsServiceFailureException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'AWS_SERVICE_FAILURE'
            ]);
        });

        $exceptions->renderable(function (\App\Exceptions\DynamoDBFailureException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'DYNAMODB_FAILURE'
            ]);
        });

        $exceptions->renderable(function (\App\Exceptions\RekognitionFailureException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'REKOGNITION_FAILURE'
            ]);
        });

    })->create();
