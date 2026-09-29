<?php

namespace App\Providers;

use App\Auth\SarionosNullUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Normalize Laravel's framework auth configuration to the
     * SarionOS capability-module identity contract.
     */
    public function register(): void
    {
        $config = $this->app->make('config');

        $config->set('auth.defaults.guard', 'web');
        $config->set('auth.defaults.passwords', null);

        $config->set('auth.guards', [
            'web' => [
                'driver' => 'session',
                'provider' => 'sarionos_null_users',
            ],
        ]);

        $config->set('auth.providers', [
            'sarionos_null_users' => [
                'driver' => 'sarionos_null',
            ],
        ]);

        $config->set('auth.passwords', []);
    }

    public function boot(): void
    {
        Auth::provider(
            'sarionos_null',
            static fn () => new SarionosNullUserProvider()
        );
    }
}
