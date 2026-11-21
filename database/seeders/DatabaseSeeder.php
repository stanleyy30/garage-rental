<?php // Start of a PHP file

namespace Database\Seeders; // This file lives in database/seeders

use App\Models\Admin; // The Admin model
use Illuminate\Database\Seeder; // Laravel's base class for seeders

class DatabaseSeeder extends Seeder // Runs on "php artisan db:seed" to put starting data in the database
{ // Start of the class
    public function run(): void // The seeding itself
    { // Start of run()
        Admin::create([ // Insert one admin row (role and status are set by the Admin model)
            'name' => 'Administrator', // Admin's display name
            'email' => 'admin@garage.test', // Admin's login email
            'password' => 'admin12345', // Plain text here, hashed automatically by the "hashed" cast
        ]); // End of create()
    } // End of run()
} // End of the class