<?php // Start of a PHP file

namespace App\Http\Controllers\Concerns; // This file lives in app/Http/Controllers/Concerns

use App\Models\Extension; // The Extension model
use App\Models\Rental; // The Rental model
use Illuminate\Http\UploadedFile; // An uploaded file
use Illuminate\Support\Facades\DB; // Database helper (for a transaction)

trait HandlesExtensions // Steps shared by the tenant's and the admin's extension pages
{ // Start of the trait
    use CalculatesRentals; // The formulas (split, cost, dates)

    protected function calculateExtension(Rental $rental, int $days): array // New end date, split and cost of an extension
    { // Start of calculateExtension()
        $oldEndDate = $rental->end_date; // The extension starts at the current end date
        $dailyPrice = $rental->garage->daily_price; // Price per day of this garage

        return [ // Everything the summary and the save need
            'days' => $days, // Extra days
            'oldEndDate' => $oldEndDate, // End date before
            'newEndDate' => $this->endDate($oldEndDate, $days), // End date after
            'split' => $this->splitExtraDays($oldEndDate, $days), // Whole months + leftover days
            'dailyPrice' => $dailyPrice, // Price per day
            'cost' => $this->extensionCost($oldEndDate, $days, $dailyPrice), // Amount to pay
        ]; // End of the result
    } // End of calculateExtension()

    protected function extensionSummary(Rental $rental, array $extension): array // Label/value rows for the preview page
    { // Start of extensionSummary()
        $split = $extension['split']; // Whole months + leftover days
        $monthlyPart = $split['months'] * self::MONTHLY_MULTIPLIER * $extension['dailyPrice']; // Cost of the whole months
        $dailyPart = $split['daily_days'] * $extension['dailyPrice']; // Cost of the leftover days

        return [ // Rows in display order
            ['label' => 'Garage', 'value' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')'], // Garage
            ['label' => 'Vehicle', 'value' => trim($rental->vehicle->brand_model . ' ' . $rental->vehicle->plate_number)], // Vehicle
            ['label' => 'Current end date', 'value' => $this->date($extension['oldEndDate'])], // Before
            ['label' => 'Extra days', 'value' => $extension['days'] . ' days'], // How many days
            ['label' => 'New end date', 'value' => $this->date($extension['newEndDate'])], // After
            ['label' => 'Monthly part', 'value' => $split['months'] . ' x 25 x ' . $this->rupiah($extension['dailyPrice']) . ' = ' . $this->rupiah($monthlyPart)], // Whole months
            ['label' => 'Daily part', 'value' => $split['daily_days'] . ' x ' . $this->rupiah($extension['dailyPrice']) . ' = ' . $this->rupiah($dailyPart)], // Leftover days
            ['label' => 'Total to pay', 'value' => $this->rupiah($extension['cost'])], // Total
        ]; // End of the rows
    } // End of extensionSummary()

    protected function saveExtension(Rental $rental, array $extension, UploadedFile $proof): void // Save the extension and move the rental's end date
    { // Start of saveExtension()
        $proofPath = $proof->store('payment-proofs', 'public'); // Save the image in storage/app/public/payment-proofs

        DB::transaction(function () use ($rental, $extension, $proofPath) { // Both rows are saved together
            Extension::create([ // History row for the extension
                'rental_id' => $rental->rental_id, // Which rental
                'old_end_date' => $extension['oldEndDate'], // End date before
                'new_end_date' => $extension['newEndDate'], // End date after
                'extra_days' => $extension['days'], // Extra days
                'extra_cost' => $extension['cost'], // Amount paid
                'payment_proof' => $proofPath, // Where the image was saved
            ]); // End of create()

            $rental->update(['end_date' => $extension['newEndDate']]); // The rental now ends later
        }); // End of the transaction
    } // End of saveExtension()
} // End of the trait
