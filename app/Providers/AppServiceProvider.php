<?php

namespace App\Providers;

use App\Models\Cow;
use App\Observers\CowObserver;
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
        Cow::observe(CowObserver::class);
    }
}
