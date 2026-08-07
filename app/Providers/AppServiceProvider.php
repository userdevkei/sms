<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        View::composer(['layouts.app', 'partials.navbar', 'partials.sidebar', 'partials.footer'], function ($view) {
            $view->with('appSettings', setting());
        });

        View::composer('partials.sidebar', function ($view) {
            $view->with('menuItems', config('menu'));
        });

        Gate::before(function ($user, string $ability) {
            return $user->hasPermission($ability) ?: null; // null lets Laravel fall through normally
        });
    }
}
