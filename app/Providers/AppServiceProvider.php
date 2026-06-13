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
        \App\Models\Fasilitas::observe(\App\Observers\FasilitasObserver::class);
        \App\Models\Kecamatan::observe(\App\Observers\KecamatanObserver::class);
    }
}
