<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\Role; // The Role enum
use App\Enums\VerificationStatus; // The VerificationStatus enum
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the table name and primary key

#[Table(name: 'users', key: 'user_id')] // The admin is stored in the same "users" table
class Admin extends User // An Admin IS A User (inheritance); there is only one shared admin account
{ // Start of the class
    protected static function booted(): void // Runs once when Laravel first uses this model
    { // Start of booted()
        static::restrictToRole(Role::Admin, VerificationStatus::Accepted); // Only admin rows; the admin never needs verification
    } // End of booted()
} // End of the class
