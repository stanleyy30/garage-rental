<?php // Start of a PHP file

namespace App\Models; // This file lives in app/Models

use Illuminate\Database\Eloquent\Attributes\Fillable; // Attribute that lists the columns we may fill at once
use Illuminate\Database\Eloquent\Attributes\Table; // Attribute that sets the primary key
use Illuminate\Database\Eloquent\Model; // Laravel's base class for a database table
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Return type for "belongs to one"

#[Table(key: 'extension_id')] // Table "extensions", primary key "extension_id"
#[Fillable(['rental_id', 'old_end_date', 'new_end_date', 'extra_days', 'extra_cost', 'payment_proof'])] // Columns create() may fill
class Extension extends Model // One row of the "extensions" table
{ // Start of the class
    protected function casts(): array // How to convert each column
    { // Start of casts()
        return [ // List of conversions
            'old_end_date' => 'date', // Text -> Carbon date
            'new_end_date' => 'date', // Text -> Carbon date
            'extra_days' => 'integer', // Make sure extra_days is an int
            'extra_cost' => 'float', // Decimal -> float
        ]; // End of the list
    } // End of casts()

    public function rental(): BelongsTo // The rental that is extended
    { // Start of rental()
        return $this->belongsTo(Rental::class, 'rental_id', 'rental_id'); // extensions.rental_id -> rentals.rental_id
    } // End of rental()

    public function validateNewEndDate(): bool // The new end date must be after the old one
    { // Start of validateNewEndDate()
        return $this->new_end_date->greaterThan($this->old_end_date); // True only if new > old
    } // End of validateNewEndDate()

    public function calculateExtraDays(): int // Number of days added
    { // Start of calculateExtraDays()
        return (int) $this->old_end_date->diffInDays($this->new_end_date); // Days between old and new end date
    } // End of calculateExtraDays()

    public function calculateRemainingCost(): float // Amount still to pay for the extension
    { // Start of calculateRemainingCost()
        return max(0, $this->calculateExtraDays() * $this->rental->garage->daily_price); // Extra days x daily price, never below 0
    } // End of calculateRemainingCost()
} // End of the class