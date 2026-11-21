<?php // Start of a PHP file

namespace App\Enums; // This file lives in app/Enums

enum VerificationStatus: string // Registration status of a tenant, saved as text
{ // Start of the enum
    case Pending = 'pending'; // Just registered, waiting for the admin
    case Accepted = 'accepted'; // Admin accepted, the tenant may rent
    case Rejected = 'rejected'; // Admin rejected, the tenant may not rent

    public function label(): string // Text shown on the screen for this case
    { // Start of label()
        return match ($this) { // Pick the text that belongs to the current case
            self::Pending => 'Pending', // Label for Pending
            self::Accepted => 'Accepted', // Label for Accepted
            self::Rejected => 'Rejected', // Label for Rejected
        }; // End of match
    } // End of label()
} // End of the enum
