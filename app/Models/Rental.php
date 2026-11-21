<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\RentalType; // The RentalType enum
use Illuminate\Database\Eloquent\Attributes\Fillable; // Attribute that lists the columns we may fill at once
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the primary key
use Illuminate\Database\Eloquent\Model; // Laravel's base class for a database table
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Return type for "belongs to one"
use Illuminate\Database\Eloquent\Relations\HasMany; // Return type for "has many"

#[Table(key: 'rental_id')] // Table "rentals", primary key "rental_id"
#[Fillable(['vehicle_id', 'garage_id', 'rental_type', 'days', 'start_date', 'end_date', 'rental_cost', 'battery_level_percent', 'kwh_to_full', 'charging_cost', 'total_cost', 'payment_proof'])] // Columns create() may fill
class Rental extends Model // One row of the "rentals" table
{ // Start of the class
    protected function casts(): array // How to convert each column
    { // Start of casts()
        return [ // List of conversions
            'rental_type' => RentalType::class, // Text -> RentalType enum
            'days' => 'integer', // Make sure days is an int
            'start_date' => 'date', // Text -> Carbon date
            'end_date' => 'date', // Text -> Carbon date
            'rental_cost' => 'float', // Decimal -> float
            'battery_level_percent' => 'integer', // Make sure the battery % is an int
            'kwh_to_full' => 'float', // Decimal -> float
            'charging_cost' => 'float', // Decimal -> float
            'total_cost' => 'float', // Decimal -> float
        ]; // End of the list
    } // End of casts()

    public function vehicle(): BelongsTo // The rented vehicle
    { // Start of vehicle()
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id'); // rentals.vehicle_id -> vehicles.vehicle_id
    } // End of vehicle()

    public function garage(): BelongsTo // The rented garage
    { // Start of garage()
        return $this->belongsTo(Garage::class, 'garage_id', 'garage_id'); // rentals.garage_id -> garages.garage_id
    } // End of garage()

    public function extensions(): HasMany // A rental has 0..* extensions
    { // Start of extensions()
        return $this->hasMany(Extension::class, 'rental_id', 'rental_id'); // extensions.rental_id -> rentals.rental_id
    } // End of extensions()
} // End of the class
