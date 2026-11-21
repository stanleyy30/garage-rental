<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\Role; // The Role enum (Admin / Tenant)
use App\Enums\VerificationStatus; // The VerificationStatus enum (Pending / Accepted / Rejected)
use Illuminate\Database\Eloquent\Attributes\Fillable; // Attribute that lists the columns we may fill at once
use Illuminate\Database\Eloquent\Attributes\Hidden; // Attribute that hides columns when the model is turned into an array/JSON
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the table name and primary key
use Illuminate\Foundation\Auth\User as Authenticatable; // Laravel's base class for users who can log in

#[Table(name: 'users', key: 'user_id')] // Table "users", primary key "user_id" (not Laravel's default "id")
#[Fillable(['nik', 'name', 'phone_number', 'email', 'password', 'role', 'verification_status', 'verified_at'])] // Columns that create()/update() may fill
#[Hidden(['password'])] // Never show the password hash when the user is printed
class User extends Authenticatable // Parent of Tenant and Admin; Laravel login uses this class for both roles
{ // Start of the class
    protected function casts(): array // Tells Laravel how to convert each column when reading/writing
    { // Start of casts()
        return [ // List of conversions
            'password' => 'hashed', // Hash the password automatically when it is saved
            'role' => Role::class, // Turn 'admin'/'tenant' text into the Role enum
            'verification_status' => VerificationStatus::class, // Turn 'pending'/... into the VerificationStatus enum
            'verified_at' => 'datetime', // Turn the timestamp into a Carbon date object
        ]; // End of the list
    } // End of casts()

    public function getName(): string // Getter from the class diagram: returns the full name
    { // Start of getName()
        return $this->name; // The "name" column
    } // End of getName()

    public function getRole(): Role // Getter from the class diagram: returns the role
    { // Start of getRole()
        return $this->role; // The "role" column as a Role enum
    } // End of getRole()
} // End of the class