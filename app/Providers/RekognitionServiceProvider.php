<?php

namespace App\Providers;

use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\ServiceProvider;

class RekognitionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RekognitionClient::class, function () {
            return new RekognitionClient([
                'region' => config('services.aws.region', 'us-east-1'),
                'version' => 'latest',
            ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
