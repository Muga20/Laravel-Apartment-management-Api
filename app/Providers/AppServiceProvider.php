<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

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
        Validator::extend('password', function ($attribute, $value, $parameters, $validator) {
            //return preg_match('/^(?=.*[A-Z])(?=.*[@])(?=.{8,})/', $value);
            return strlen($value) >= 8;
        });
    }
}
