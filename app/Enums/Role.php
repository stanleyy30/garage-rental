<?php // Start of a PHP file

namespace App\Enums; // This file lives in the App\Enums folder (app/Enums)

enum Role: string // A "backed" enum: every case has a text value that is saved in the database
{ // Start of the enum
    case Admin = 'admin'; // Company staff, saved as 'admin'
    case Tenant = 'tenant'; // Customer who rents garages, saved as 'tenant'

    public function label(): string // Text shown on the screen for this case
    { // Start of label()
        return match ($this) { // Pick the text that belongs to the current case
            self::Admin => 'Admin', // Label for Admin
            self::Tenant => 'Tenant', // Label for Tenant
        }; // End of match
    } // End of label()
} // End of the enum
