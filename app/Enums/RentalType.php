<?php // Start of a PHP file

namespace App\Enums; // This file lives in app/Enums

enum RentalType: string // How a garage is rented, saved as text
{ // Start of the enum
    case Daily = 'daily'; // Paid per day
    case Monthly = 'monthly'; // Paid as one month (25 x daily price)

    public function label(): string // Text shown on the screen for this case
    { // Start of label()
        return match ($this) { // Pick the text that belongs to the current case
            self::Daily => 'Daily', // Label for Daily
            self::Monthly => 'Monthly', // Label for Monthly
        }; // End of match
    } // End of label()
} // End of the enum