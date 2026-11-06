<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registriert Anwendungsdienste.
     */
    public function register(): void
    {
        //
    }

    /**
     * Initialisiert Anwendungsdienste.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
