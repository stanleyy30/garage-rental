<?php // Start of a PHP file

namespace App\Enums; // This file lives in app/Enums

enum GarageType: string // Kind of garage, saved as text
{ // Start of the enum
    case Bicycle = 'bicycle'; // Bicycle garage, IDs start with S
    case Car = 'car'; // Car garage, IDs start with C
    case ElectricCar = 'electric_car'; // Electric car garage with a charger, IDs start with E

    public function label(): string // Text shown on the screen for this case
    { // Start of label()
        return match ($this) { // Pick the text that belongs to the current case
            self::Bicycle => 'Bicycle Garage', // Label for Bicycle
            self::Car => 'Car Garage', // Label for Car
            self::ElectricCar => 'Electric Car Garage', // Label for ElectricCar
        }; // End of match
    } // End of label()
} // End of the enum