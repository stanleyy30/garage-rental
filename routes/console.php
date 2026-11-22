<?php // Start of a PHP file

use Illuminate\Foundation\Inspiring; // Laravel's list of short quotes
use Illuminate\Support\Facades\Artisan; // Laravel's command-line helper

Artisan::command('inspire', function () { // Laravel's example command: php artisan inspire
    $this->comment(Inspiring::quote()); // Print a random quote
})->purpose('Display an inspiring quote'); // Description shown in php artisan list
