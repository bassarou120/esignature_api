<?php

namespace App\Providers;

use App\Models\Status;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        $sendingStatues = Status::all()->toArray();
        foreach ($sendingStatues as $statut) {
            if (!defined($statut['name'])) define($statut['name'], $statut['id']);
        }
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        Schema::defaultStringLength(191);
    }
}
