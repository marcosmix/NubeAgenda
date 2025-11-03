<?php

namespace App\Providers;

use App\Models\Meeting;
use App\Observers\MeetingObserver;
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
        Meeting::observe(MeetingObserver::class);
    }
}
