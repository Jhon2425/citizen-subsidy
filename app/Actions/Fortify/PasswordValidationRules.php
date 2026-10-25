<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()   // upper and lower case letters
                ->numbers()     // at least one number
                ->symbols();    // at least one special character
        });
    }
}