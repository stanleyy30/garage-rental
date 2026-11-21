<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\Role; // The Role enum
use App\Enums\VerificationStatus; // The VerificationStatus enum
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the table name and primary key
use Illuminate\Database\Eloquent\Builder; // Query builder type, used in the global scope

#[Table(name: 'users', key: 'user_id')] // Admins are stored in the same "users" table
class Admin extends User // An Admin IS A User (inheritance); there is only one shared admin account
{ // Start of the class
    protected static function booted(): void // Runs once when Laravel first uses this model
    { // Start of booted()
        static::addGlobalScope('admin', function (Builder $query) { // Every Admin query gets an extra condition
            $query->where('role', Role::Admin->value); // ... only rows whose role is 'admin'
        }); // End of the global scope

        static::creating(function (Admin $admin) { // Runs just before a new Admin row is inserted
            $admin->role = Role::Admin; // Always save the role as 'admin'
            $admin->verification_status = VerificationStatus::Accepted; // Admins never need verification
        }); // End of the creating hook
    } // End of booted()
} // End of the class