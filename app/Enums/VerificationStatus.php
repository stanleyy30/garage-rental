<?php // Start of a PHP file

namespace App\Enums; // This file lives in app/Enums

enum VerificationStatus: string // Registration status of a tenant, saved as text
{ // Start of the enum
    case Pending = 'pending'; // Just registered, waiting for the admin
    case Accepted = 'accepted'; // Admin accepted, the tenant may rent
    case Rejected = 'rejected'; // Admin rejected, the tenant may not rent
} // End of the enum