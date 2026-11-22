<?php // Start of a PHP file

use Illuminate\Database\Migrations\Migration; // Base class every migration extends
use Illuminate\Database\Schema\Blueprint; // Object used to describe the columns of a table
use Illuminate\Support\Facades\DB; // Database helper
use Illuminate\Support\Facades\Schema; // Tool that changes tables

return new class extends Migration // Adds the admin approval step to extensions
{ // Start of the class body
    public function up(): void // Runs on "php artisan migrate"
    { // Start of up()
        Schema::table('extensions', function (Blueprint $table) { // Change the existing "extensions" table
            $table->string('status', 10)->default('accepted')->after('extra_cost'); // 'pending' / 'accepted' / 'rejected'; extensions entered by the admin are accepted at once
            $table->string('admin_payment_proof')->nullable()->after('payment_proof'); // Payment evidence the admin uploads when accepting a tenant's request
            $table->timestamp('decided_at')->nullable()->after('admin_payment_proof'); // When the admin accepted or rejected the request
        }); // End of the change

        DB::table('extensions')->whereNull('decided_at')->update(['decided_at' => DB::raw('created_at')]); // Older extensions were accepted when they were made
    } // End of up()

    public function down(): void // Undo up()
    { // Start of down()
        Schema::table('extensions', function (Blueprint $table) { // Change the "extensions" table back
            $table->dropColumn(['status', 'admin_payment_proof', 'decided_at']); // Remove the three columns
        }); // End of the change
    } // End of down()
}; // End of the migration class
