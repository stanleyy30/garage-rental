<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\GarageStatus; // The GarageStatus enum
use App\Enums\GarageType; // The GarageType enum
use Carbon\Carbon; // Date library used by Laravel
use Illuminate\Database\Eloquent\Attributes\Fillable; // Attribute that lists the columns we may fill at once
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the primary key
use Illuminate\Database\Eloquent\Model; // Laravel's base class for a database table
use Illuminate\Database\Eloquent\Relations\HasMany; // Return type for "has many"

#[Table(key: 'garage_id', keyType: 'string', incrementing: false)] // Primary key "garage_id" is text ('C001') and not auto-increment
#[Fillable(['garage_id', 'type', 'daily_price', 'electricity_price_per_kwh', 'status'])] // Columns create() may fill
class Garage extends Model // One row of the "garages" table
{ // Start of the class
    public const MAX_TOTAL_GARAGES = 999; // Constant: all garage types together may not exceed 999

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

    public static function generateId(GarageType $type, int $sequence): string // Static: builds an ID like 'C010'
    { // Start of generateId()
        $prefix = match ($type) { // Pick the first letter by garage type
            GarageType::Bicycle => 'S', // Bicycle garages start with S
            GarageType::Car => 'C', // Car garages start with C
            GarageType::ElectricCar => 'E', // Electric car garages start with E
        }; // End of match

        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT); // Letter + number padded to 3 digits (10 -> '010')
    } // End of generateId()

    public function monthlyPrice(): float // Price of one month
    { // Start of monthlyPrice()
        return Rental::MONTHLY_MULTIPLIER * $this->daily_price; // 25 x daily price
    } // End of monthlyPrice()

    public function isAvailable(Carbon $date): bool // True if no rental covers this date
    { // Start of isAvailable()
        return ! $this->rentals() // Look at this garage's rentals ...
            ->whereDate('start_date', '<=', $date) // ... that started on or before the date
            ->whereDate('end_date', '>=', $date) // ... and end on or after the date
            ->exists(); // If one exists the garage is busy, so return the opposite
    } // End of isAvailable()

    public function canFit(Vehicle $vehicle): bool // True if the vehicle may park in this garage type
    { // Start of canFit()
        return in_array($this->type, $vehicle->getCompatibleGarageTypes(), true); // Is this garage's type in the vehicle's allowed list?
    } // End of canFit()

    public function markUsed(): void // Set the status to 'used'
    { // Start of markUsed()
        $this->update(['status' => GarageStatus::Used]); // Save the new status to the database
    } // End of markUsed()

    public function markAvailable(): void // Set the status back to 'available'
    { // Start of markAvailable()
        $this->update(['status' => GarageStatus::Available]); // Save the new status to the database
    } // End of markAvailable()
} // End of the class