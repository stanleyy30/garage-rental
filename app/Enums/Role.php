<?php // Start of a PHP file

namespace App\Enums; // This file lives in the App\Enums folder (app/Enums)

enum Role: string // A "backed" enum: every case has a text value that is saved in the database
{ // Start of the enum
    case Admin = 'admin'; // Company staff, saved as 'admin'
    case Tenant = 'tenant'; // Customer who rents garages, saved as 'tenant'
} // End of the enum