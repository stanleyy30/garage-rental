<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\Role; // The Role enum
use App\Enums\VerificationStatus; // The VerificationStatus enum
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the table name and primary key
use Illuminate\Database\Eloquent\Builder; // Query builder type, used in the global scope
use Illuminate\Database\Eloquent\Relations\HasMany; // Return type for a one-to-many relationship

#[Table(name: 'users', key: 'user_id')] // Tenants are stored in the same "users" table
class Tenant extends User // A Tenant IS A User (inheritance)
{ // Start of the class
    protected static function booted(): void // Runs once when Laravel first uses this model
    { // Start of booted()
        static::addGlobalScope('tenant', function (Builder $query) { // Every Tenant query gets an extra condition
            $query->where('role', Role::Tenant->value); // ... only rows whose role is 'tenant'
        }); // End of the global scope

        static::creating(function (Tenant $tenant) { // Runs just before a new Tenant row is inserted
            $tenant->role = Role::Tenant; // Always save the role as 'tenant'
            $tenant->verification_status = VerificationStatus::Pending; // New tenants always start as 'pending'
        }); // End of the creating hook
    } // End of booted()

    public function vehicles(): HasMany // A tenant owns 0..* vehicles
    { // Start of vehicles()
        return $this->hasMany(Vehicle::class, 'user_id', 'user_id'); // vehicles.user_id points to users.user_id
    } // End of vehicles()

    public static function register(array $data): Tenant // Static: saves a new tenant from the registration form
    { // Start of register()
        return static::create([ // Insert one row into "users" (role and status are set by the creating hook)
            'nik' => $data['nik'], // 16-digit national ID number
            'name' => $data['name'], // Full name
            'phone_number' => $data['phone_number'], // Phone number
            'email' => $data['email'], // Email, also the username
            'password' => $data['password'], // Plain text here, hashed by the "hashed" cast in User
        ]); // End of create()
    } // End of register()

    public function isVerified(): bool // True only when the admin accepted this tenant
    { // Start of isVerified()
        return $this->verification_status === VerificationStatus::Accepted; // Compare with the Accepted case
    } // End of isVerified()

    public static function validateNik(string $nik): bool // Static: checks a NIK without needing a Tenant object
    { // Start of validateNik()
        return preg_match('/^[0-9]{16}$/', $nik) === 1; // Exactly 16 digits, nothing else
    } // End of validateNik()
} // End of the class