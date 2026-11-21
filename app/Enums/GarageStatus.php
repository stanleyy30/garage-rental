<?php // Start of a PHP file

namespace App\Enums; // This file lives in app/Enums

enum GarageStatus: string // Whether a garage is free, saved as text
{ // Start of the enum
    case Available = 'available'; // Free to rent
    case Used = 'used'; // Currently rented
} // End of the enum