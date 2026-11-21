<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use App\Enums\GarageType; // The GarageType enum
use App\Enums\VehicleType; // The VehicleType enum
use Illuminate\Database\Eloquent\Attributes\Fillable; // Attribute that lists the columns we may fill at once
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the primary key
use Illuminate\Database\Eloquent\Model; // Laravel's base class for a database table
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Return type for "belongs to one"
use Illuminate\Database\Eloquent\Relations\HasMany; // Return type for "has many"

#[Table(key: 'vehicle_id')] // Table "vehicles" (from the class name), primary key "vehicle_id"
#[Fillable(['user_id', 'type', 'plate_number', 'brand_model', 'battery_capacity_kwh'])] // Columns create() may fill
class Vehicle extends Model // One row of the "vehicles" table
{ // Start of the class
    protected function casts(): array // How to convert each column
    { // Start of casts()
        return [ // List of conversions
            'type' => VehicleType::class, // Text -> VehicleType enum
            'battery_capacity_kwh' => 'float', // Decimal -> PHP float
        ]; // End of the list
    } // End of casts()

    public function tenant(): BelongsTo // Each vehicle belongs to exactly one tenant
    { // Start of tenant()
        return $this->belongsTo(Tenant::class, 'user_id', 'user_id'); // vehicles.user_id -> users.user_id
    } // End of tenant()

    public function rentals(): HasMany // A vehicle has 0..* rentals over time
    { // Start of rentals()
        return $this->hasMany(Rental::class, 'vehicle_id', 'vehicle_id'); // rentals.vehicle_id -> vehicles.vehicle_id
    } // End of rentals()

    public function needsPlate(): bool // Every vehicle needs a plate number except a bicycle
    { // Start of needsPlate()
        return $this->type !== VehicleType::Bicycle; // False only for bicycles
    } // End of needsPlate()

    public function isElectricCar(): bool // True for electric cars
    { // Start of isElectricCar()
        return $this->type === VehicleType::ElectricCar; // Compare with the ElectricCar case
    } // End of isElectricCar()

    public function getCompatibleGarageTypes(): array // Which garage types this vehicle may use
    { // Start of getCompatibleGarageTypes()
        return match ($this->type) { // Choose by vehicle type
            VehicleType::Bicycle, VehicleType::Motorcycle => [GarageType::Bicycle], // Bicycle and motorcycle -> S garages
            VehicleType::Car => [GarageType::Car], // Petrol car -> C garages only
            VehicleType::ElectricCar => [GarageType::Car, GarageType::ElectricCar], // Electric car -> C or E garages
        }; // End of match
    } // End of getCompatibleGarageTypes()
} // End of the class