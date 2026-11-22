<?php // Start of a PHP file

namespace App\Enums; // This file lives in app/Enums

enum ExtensionStatus: string // Approval status of an extension, saved as text
{ // Start of the enum
    case Pending = 'pending'; // Requested by the tenant, waiting for the admin
    case Accepted = 'accepted'; // Accepted: the rental's end date has moved
    case Rejected = 'rejected'; // Rejected: the end date stays as it was

    public function label(): string // Text shown on the screen for this case
    { // Start of label()
        return match ($this) { // Pick the text that belongs to the current case
            self::Pending => 'Waiting for admin', // Label for Pending
            self::Accepted => 'Accepted', // Label for Accepted
            self::Rejected => 'Rejected', // Label for Rejected
        }; // End of match
    } // End of label()
} // End of the enum
