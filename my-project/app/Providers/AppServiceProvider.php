<?php

namespace App\Providers;

use App\Support\AdminAccess;
use Illuminate\Support\Facades\Blade;
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
        // @adminCan('Manage Staff') ... @endadminCan
        Blade::if('adminCan', fn (string $permission) => AdminAccess::allows($permission));
    }
}
