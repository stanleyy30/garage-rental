<?php // Start of a PHP file

use Illuminate\Database\Migrations\Migration; // Base class every migration extends
use Illuminate\Database\Schema\Blueprint; // Object used to describe the columns of a table
use Illuminate\Support\Facades\Schema; // Tool that creates and drops tables

return new class extends Migration // This file returns one migration object
{ // Start of the class body
    public function up(): void // Creates the table
    { // Start of up()
        Schema::create('vehicles', function (Blueprint $table) { // Create the "vehicles" table
            $table->id('vehicle_id'); // Primary key "vehicle_id": BIGINT, auto-increment
            $table->foreignId('user_id')->constrained(table: 'users', column: 'user_id'); // FK to the tenant who owns the vehicle
            $table->string('type', 15); // 'bicycle' / 'motorcycle' / 'car' / 'electric_car'
            $table->string('plate_number', 12)->nullable(); // Plate number, empty for bicycles
            $table->string('brand_model', 100); // Brand and model, e.g. 'Honda Beat'
            $table->decimal('battery_capacity_kwh', 6, 2)->nullable(); // Battery size in kWh, only for electric cars
            $table->timestamps(); // Adds created_at and updated_at
        }); // End of the vehicles table
    } // End of up()

    public function down(): void // Undo up()
    { // Start of down()
        Schema::dropIfExists('vehicles'); // Remove the vehicles table
    } // End of down()
}; // End of the migration class