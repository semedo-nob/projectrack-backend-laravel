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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Leave URL generation on the request host. Forcing APP_URL (or https)
        // sent the owner portal and share pages away from the public
        // http://IP:8088 address Apache is actually serving.
    }
}
