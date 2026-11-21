<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\GarageStatus; // The GarageStatus enum
use App\Enums\GarageType; // The GarageType enum
use Illuminate\Database\Eloquent\Attributes\Fillable; // Attribute that lists the columns we may fill at once
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the primary key
use Illuminate\Database\Eloquent\Model; // Laravel's base class for a database table
use Illuminate\Database\Eloquent\Relations\HasMany; // Return type for "has many"

#[Table(key: 'garage_id', keyType: 'string', incrementing: false)] // Primary key "garage_id" is text ('C001') and not auto-increment
#[Fillable(['garage_id', 'type', 'daily_price', 'electricity_price_per_kwh', 'status'])] // Columns create() may fill
class Garage extends Model // One row of the "garages" table
{ // Start of the class
    protected function casts(): array // How to convert each column
    { // Start of casts()
        return [ // List of conversions
            'type' => GarageType::class, // Text -> GarageType enum
            'status' => GarageStatus::class, // Text -> GarageStatus enum
            'daily_price' => 'float', // Decimal -> PHP float
            'electricity_price_per_kwh' => 'float', // Decimal -> PHP float
        ]; // End of the list
    } // End of casts()

    public function rentals(): HasMany // A garage has 0..* rentals over time
    { // Start of rentals()
        return $this->hasMany(Rental::class, 'garage_id', 'garage_id'); // rentals.garage_id -> garages.garage_id
    } // End of rentals()
} // End of the class
