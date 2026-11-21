<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\Role; // The Role enum
use App\Enums\VerificationStatus; // The VerificationStatus enum
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the table name and primary key
use Illuminate\Database\Eloquent\Relations\HasMany; // Return type for a one-to-many relationship

#[Table(name: 'users', key: 'user_id')] // Tenants are stored in the same "users" table
class Tenant extends User // A Tenant IS A User (inheritance)
{ // Start of the class
    protected static function booted(): void // Runs once when Laravel first uses this model
    { // Start of booted()
        static::restrictToRole(Role::Tenant, VerificationStatus::Pending); // Only tenant rows; new tenants start as Pending
    } // End of booted()

    public function vehicles(): HasMany // A tenant owns 0..* vehicles
    { // Start of vehicles()
        return $this->hasMany(Vehicle::class, 'user_id', 'user_id'); // vehicles.user_id points to users.user_id
    } // End of vehicles()
} // End of the class
