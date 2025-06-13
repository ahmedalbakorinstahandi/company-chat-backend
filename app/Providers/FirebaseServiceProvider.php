<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('firebase', function ($app) {
            return (new Factory)
                ->withServiceAccount($app['config']['firebase.credentials'])
                ->withDatabaseUri($app['config']['firebase.database_url'])
                ->createDatabase();
        });

        $this->app->singleton('firebase.messaging', function ($app) {
            return (new Factory)
                ->withServiceAccount($app['config']['firebase.credentials'])
                ->createMessaging();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/firebase.php' => config_path('firebase.php'),
        ], 'firebase-config');
    }
} 