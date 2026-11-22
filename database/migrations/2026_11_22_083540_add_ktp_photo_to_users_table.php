<?php // Start of a PHP file

use Illuminate\Database\Migrations\Migration; // Base class every migration extends
use Illuminate\Database\Schema\Blueprint; // Object used to describe the columns of a table
use Illuminate\Support\Facades\Schema; // Tool that changes tables

return new class extends Migration // Adds the KTP photo column to an existing table
{ // Start of the class body
    public function up(): void // Runs on "php artisan migrate"
    { // Start of up()
        Schema::table('users', function (Blueprint $table) { // Change the existing "users" table
            $table->string('ktp_photo')->nullable()->after('nik'); // File path of the tenant's KTP photo; empty for the admin
        }); // End of the change
    } // End of up()

    public function down(): void // Undo up()
    { // Start of down()
        Schema::table('users', function (Blueprint $table) { // Change the "users" table back
            $table->dropColumn('ktp_photo'); // Remove the column
        }); // End of the change
    } // End of down()
}; // End of the migration class
