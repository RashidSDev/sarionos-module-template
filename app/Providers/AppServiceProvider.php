<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * Core is the sole SarionOS identity and authentication authority.
         *
         * Laravel 12 merges its framework auth defaults into application
         * configuration. SarionOS capability modules must not inherit the
         * stock local `web` guard, `users` provider or password broker.
         */
        $config = $this->app->make('config');

        $config->set('auth.defaults.guard', null);
        $config->set('auth.defaults.passwords', null);
        $config->set('auth.guards', []);
        $config->set('auth.providers', []);
        $config->set('auth.passwords', []);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
