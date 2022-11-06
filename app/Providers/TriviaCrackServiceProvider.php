<?php

namespace App\Providers;

use App\Consumers\TriviaCrack\TriviaCrackConsumer;
use Illuminate\Support\ServiceProvider;

class TriviaCrackServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TriviaCrackConsumer::class, function ($app) {
            return new TriviaCrackConsumer();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
