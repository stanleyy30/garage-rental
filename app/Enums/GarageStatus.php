<?php // Start of a PHP file

namespace App\Enums; // This file lives in app/Enums

enum GarageStatus: string // Whether a garage is free, saved as text
{ // Start of the enum
    case Available = 'available'; // Free to rent
    case Used = 'used'; // Currently rented

    public function label(): string // Text shown on the screen for this case
    { // Start of label()
        return match ($this) { // Pick the text that belongs to the current case
            self::Available => 'Available', // Label for Available
            self::Used => 'Used', // Label for Used
        }; // End of match
    } // End of label()
} // End of the enum
