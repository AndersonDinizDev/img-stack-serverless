<?php

namespace App\Providers;

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\ServiceProvider;

class DynamoDbServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DynamoDbClient::class, function ($app) {
            $config = [
                'region' => config('services.aws.region'),
                'version' => 'latest',
                'credentials' => config('services.aws.credentials'),
            ];

            if (config('services.dynamodb.endpoint')) {
                $config['endpoint'] = config('services.dynamodb.endpoint');
            }

            return new DynamoDbClient($config);
        });
    }
}
