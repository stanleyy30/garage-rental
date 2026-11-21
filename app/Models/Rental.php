<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\GarageType; // The GarageType enum
use App\Enums\RentalType; // The RentalType enum
use Carbon\Carbon; // Date library used by Laravel
use Illuminate\Database\Eloquent\Attributes\Fillable; // Attribute that lists the columns we may fill at once
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the primary key
use Illuminate\Database\Eloquent\Model; // Laravel's base class for a database table
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Return type for "belongs to one"
use Illuminate\Database\Eloquent\Relations\HasMany; // Return type for "has many"

#[Table(key: 'rental_id')] // Table "rentals", primary key "rental_id"
#[Fillable(['vehicle_id', 'garage_id', 'rental_type', 'days', 'start_date', 'end_date', 'rental_cost', 'battery_level_percent', 'kwh_to_full', 'charging_cost', 'total_cost', 'payment_proof'])] // Columns create() may fill
class Rental extends Model // One row of the "rentals" table
{ // Start of the class
    public const MONTHLY_MULTIPLIER = 25; // Constant: monthly price = 25 x daily price

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

    public static function maxDailyDays(Carbon $startDate): int // Static: longest daily rental for a start month
    { // Start of maxDailyDays()
        return $startDate->daysInMonth - 1; // Days in that month minus 1 (Feb 28 -> 27, 31-day month -> 30)
    } // End of maxDailyDays()

    public function validateDays(): bool // Checks the number of days follows the rules
    { // Start of validateDays()
        if ($this->rental_type === RentalType::Monthly) { // Monthly rental ...
            return $this->days === $this->start_date->daysInMonth; // ... must last exactly the days of the start month
        } // End of the monthly check

        return $this->days >= 1 && $this->days <= self::maxDailyDays($this->start_date); // Daily: from 1 to the maximum
    } // End of validateDays()

    public function calculateEndDate(): Carbon // End date = start date + days
    { // Start of calculateEndDate()
        return $this->start_date->copy()->addDays($this->days); // copy() so the start date itself is not changed
    } // End of calculateEndDate()

    public function calculateRentalCost(): float // Cost of the garage itself
    { // Start of calculateRentalCost()
        if ($this->rental_type === RentalType::Monthly) { // Monthly rental ...
            return self::MONTHLY_MULTIPLIER * $this->garage->daily_price; // ... costs 25 x daily price
        } // End of the monthly case

        return $this->garage->daily_price * $this->days; // Daily rental: daily price x days
    } // End of calculateRentalCost()

    public function validateBatteryInput(): bool // Checks the battery numbers for an electric car garage
    { // Start of validateBatteryInput()
        if ($this->garage->type !== GarageType::ElectricCar) { // No charger in this garage ...
            return true; // ... so there is nothing to check
        } // End of the no-charging case
        $capacity = $this->vehicle->battery_capacity_kwh; // Max kWh of the car's battery (full battery size)
        $capacityOk = $capacity !== null && $capacity > 0; // Max kWh must be filled and more than 0

        $percentOk = $this->battery_level_percent !== null // Battery % must be filled ...
            && $this->battery_level_percent >= 0 // ... at least 0 ...
            && $this->battery_level_percent <= 99; // ... and at most 99 (100% is already full)

        $kwhOk = $this->kwh_to_full !== null // kWh to full must be filled ...
            && $this->kwh_to_full > 0 // ... more than 0 ...
            && $this->kwh_to_full <= $capacity; // ... and not more than the max kWh

        return $capacityOk && $percentOk && $kwhOk; // Valid only if all three checks pass
    } // End of validateBatteryInput()

    public function calculateChargingCost(): float // Cost of charging the battery
    { // Start of calculateChargingCost()
        if ($this->garage->type !== GarageType::ElectricCar) { // Only electric car garages have a charger
            return 0; // No charging, no cost
        } // End of the no-charging case

        return $this->kwh_to_full * $this->garage->electricity_price_per_kwh; // kWh to full x price per kWh
    } // End of calculateChargingCost()

    public function calculateTotal(): float // Total the tenant pays
    { // Start of calculateTotal()
        return $this->calculateRentalCost() + $this->calculateChargingCost(); // Rental cost + charging cost
    } // End of calculateTotal()

    public function isActive(Carbon $date): bool // True if the rental covers the given date
    { // Start of isActive()
        return $date->between($this->start_date, $this->end_date); // start_date <= date <= end_date
    } // End of isActive()
} // End of the class