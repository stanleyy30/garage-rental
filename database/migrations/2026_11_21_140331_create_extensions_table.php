<?php // Start of a PHP file

use Illuminate\Database\Migrations\Migration; // Base class every migration extends
use Illuminate\Database\Schema\Blueprint; // Object used to describe the columns of a table
use Illuminate\Support\Facades\Schema; // Tool that creates and drops tables

return new class extends Migration // This file returns one migration object
{ // Start of the class body
    public function up(): void // Creates the table
    { // Start of up()
        Schema::create('extensions', function (Blueprint $table) { // Create the "extensions" table
            $table->id('extension_id'); // Primary key "extension_id": BIGINT, auto-increment
            $table->foreignId('rental_id')->constrained(table: 'rentals', column: 'rental_id'); // FK to the rental being extended
            $table->date('old_end_date'); // End date before the extension
            $table->date('new_end_date'); // End date after the extension
            $table->integer('extra_days'); // new_end_date - old_end_date
            $table->decimal('extra_cost', 12, 2); // Whole months x 25 x daily price + leftover days x daily price, never negative
            $table->string('payment_proof'); // File path of the payment-proof image
            $table->timestamps(); // Adds created_at and updated_at
        }); // End of the extensions table
    } // End of up()

    public function down(): void // Undo up()
    { // Start of down()
        Schema::dropIfExists('extensions'); // Remove the extensions table
    } // End of down()
}; // End of the migration class