<?php

namespace App\Providers;

use App\Consumers\TriviaCrack\TriviaCrackConsumer;
use Illuminate\Support\Arr;
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

        Arr::macro('refMap', function (array &$arr, string $key, callable $callback) {
            $values = Arr::get($arr, $key);
            Arr::set($arr, $key, Arr::map($values, $callback));
            return $arr;
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
