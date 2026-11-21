<?php // Start of a PHP file

use Illuminate\Database\Migrations\Migration; // Base class every migration extends
use Illuminate\Database\Schema\Blueprint; // Object used to describe the columns of a table
use Illuminate\Support\Facades\Schema; // Tool that creates and drops tables

return new class extends Migration // This file returns one migration object
{ // Start of the class body
    public function up(): void // Creates the table
    { // Start of up()
        Schema::create('garages', function (Blueprint $table) { // Create the "garages" table
            $table->string('garage_id', 4)->primary(); // Primary key is text like 'S001', 'C010', 'E003'
            $table->string('type', 15); // 'bicycle' / 'car' / 'electric_car'
            $table->decimal('daily_price', 12, 2); // Price per day: up to 12 digits, 2 after the decimal point
            $table->decimal('electricity_price_per_kwh', 12, 2)->nullable(); // Price per kWh, only for electric car garages
            $table->string('status', 10)->default('available'); // 'available' or 'used', new garages start as 'available'
            $table->timestamps(); // Adds created_at and updated_at
        }); // End of the garages table
    } // End of up()

    public function down(): void // Undo up()
    { // Start of down()
        Schema::dropIfExists('garages'); // Remove the garages table
    } // End of down()
}; // End of the migration class