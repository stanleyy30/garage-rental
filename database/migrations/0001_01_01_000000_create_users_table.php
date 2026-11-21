<?php // Start of a PHP file

use Illuminate\Database\Migrations\Migration; // Base class every migration extends
use Illuminate\Database\Schema\Blueprint; // Object used to describe the columns of a table
use Illuminate\Support\Facades\Schema; // Tool that creates and drops tables

return new class extends Migration // This file returns one migration object (an anonymous class)
{ // Start of the class body
    public function up(): void // up() runs on "php artisan migrate" and creates the table
    { // Start of up()
        Schema::create('users', function (Blueprint $table) { // Create the "users" table for admins and tenants
            $table->id('user_id'); // Primary key "user_id": BIGINT, auto-increment
            $table->string('nik', 16)->nullable()->unique(); // NIK: 16 characters, empty for admins, no duplicates
            $table->string('name', 100); // Full name, up to 100 characters
            $table->string('phone_number', 15)->nullable(); // Phone number, empty for admins
            $table->string('email', 100)->unique(); // Email, used as the username, no duplicates
            $table->string('password'); // Hashed password (255 characters by default)
            $table->string('role', 10); // 'admin' or 'tenant'
            $table->string('verification_status', 10)->default('pending'); // 'pending' / 'accepted' / 'rejected', starts as 'pending'
            $table->timestamp('verified_at')->nullable(); // When the tenant was accepted or rejected
            $table->timestamps(); // Adds created_at and updated_at
        }); // End of the users table
    } // End of up()

    public function down(): void // down() runs on rollback and removes what up() made
    { // Start of down()
        Schema::dropIfExists('users'); // Remove the users table
    } // End of down()
}; // End of the migration class