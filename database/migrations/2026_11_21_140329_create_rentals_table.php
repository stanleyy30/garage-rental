<?php // Start of a PHP file

use Illuminate\Database\Migrations\Migration; // Base class every migration extends
use Illuminate\Database\Schema\Blueprint; // Object used to describe the columns of a table
use Illuminate\Support\Facades\Schema; // Tool that creates and drops tables

return new class extends Migration // This file returns one migration object
{ // Start of the class body
    public function up(): void // Creates the table
    { // Start of up()
        Schema::create('rentals', function (Blueprint $table) { // Create the "rentals" table
            $table->id('rental_id'); // Primary key "rental_id": BIGINT, auto-increment
            $table->foreignId('vehicle_id')->constrained(table: 'vehicles', column: 'vehicle_id'); // FK to the rented vehicle
            $table->string('garage_id', 4); // Column for the garage code, same type as garages.garage_id
            $table->foreign('garage_id')->references('garage_id')->on('garages'); // Make garage_id a FK to garages.garage_id
            $table->string('rental_type', 10); // 'daily' or 'monthly'
            $table->integer('days'); // Number of rented days
            $table->date('start_date'); // Start date (today or a later day picked on the calendar)
            $table->date('end_date'); // End date = start_date + days, the day the vehicle leaves (moves when an extension is accepted)
            $table->decimal('rental_cost', 12, 2); // Cost of the garage itself
            $table->integer('battery_level_percent')->nullable(); // Remaining battery (%) when an electric car enters, empty if no charging
            $table->decimal('kwh_to_full', 6, 2)->nullable(); // kWh needed until the battery is full, empty if no charging
            $table->decimal('charging_cost', 12, 2)->default(0); // kwh_to_full x electricity_price_per_kwh, 0 if no charging
            $table->decimal('total_cost', 12, 2); // rental_cost + charging_cost
            $table->string('payment_proof'); // File path of the payment-proof image
            $table->timestamps(); // Adds created_at and updated_at
        }); // End of the rentals table
    } // End of up()

    public function down(): void // Undo up()
    { // Start of down()
        Schema::dropIfExists('rentals'); // Remove the rentals table
    } // End of down()
}; // End of the migration class