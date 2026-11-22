<?php // Start of a PHP file

namespace App\Providers; // This file lives in app/Providers

use Illuminate\Support\ServiceProvider; // Laravel's base class for service providers

class AppServiceProvider extends ServiceProvider // Laravel's place for app-wide setup; this app needs none
{ // Start of the class
    public function register(): void // Runs while Laravel registers its services
    { // Start of register()
        // Nothing to register
    } // End of register()

    public function boot(): void // Runs after every service is registered
    { // Start of boot()
        // Nothing to set up
    } // End of boot()
} // End of the class
