<?php

namespace App\Providers;

use App\Auth\PlaintextUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */

    public function boot(): void
    {
        // Register the custom plaintext user provider
        Auth::provider('plaintext', function ($app, array $config) {
            return new PlaintextUserProvider(
                $app['hash'],
                $config['model']
            );
        });
    }
}
