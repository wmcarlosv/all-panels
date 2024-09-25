<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use TCG\Voyager\Facades\Voyager;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Artisan::call("cache:clear");
        \Artisan::call("config:clear");
        \Artisan::call("view:clear");

        Voyager::addAction(\App\Actions\ViewActiveSessionsAction::class);
        Voyager::addAction(\App\Actions\ExtendSubscriptionAction::class);
        Voyager::addAction(\App\Actions\RefreshServerLibrariesAction::class);
        Voyager::addAction(\App\Actions\ConvertJellyfinCustomer::class);
        Voyager::addAction(\App\Actions\ChangePasswordJellyfinCustomerAction::class);
        Voyager::addAction(\App\Actions\ViewActiveSessionsByUserJellyfinAction::class);
        Voyager::addAction(\App\Actions\JellyfinUserChangeServerAction::class);
        Voyager::addAction(\App\Actions\JellyfinConnectDeviceAction::class);
        Voyager::addAction(\App\Actions\AddToUserAction::class);
        Paginator::useBootstrap();
    }
}
