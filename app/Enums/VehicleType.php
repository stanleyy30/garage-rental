<?php // Start of a PHP file

namespace App\Enums; // This file lives in app/Enums

enum VehicleType: string // Kind of vehicle, saved as text
{ // Start of the enum
    case Bicycle = 'bicycle'; // Bicycle (no plate number)
    case Motorcycle = 'motorcycle'; // Motorcycle
    case Car = 'car'; // Petrol car
    case ElectricCar = 'electric_car'; // Electric car

    public function label(): string // Text shown on the screen for this case
    { // Start of label()
        return match ($this) { // Pick the text that belongs to the current case
            self::Bicycle => 'Bicycle', // Label for Bicycle
            self::Motorcycle => 'Motorcycle', // Label for Motorcycle
            self::Car => 'Car', // Label for Car
            self::ElectricCar => 'Electric Car', // Label for ElectricCar
        }; // End of match
    } // End of label()
} // End of the enum