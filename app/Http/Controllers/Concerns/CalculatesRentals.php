<?php // Start of a PHP file

namespace App\Http\Controllers\Concerns; // This file lives in app/Http/Controllers/Concerns

use App\Enums\GarageStatus; // The GarageStatus enum
use App\Enums\GarageType; // The GarageType enum
use App\Enums\RentalType; // The RentalType enum
use App\Enums\VehicleType; // The VehicleType enum
use App\Models\Garage; // The Garage model
use App\Models\Rental; // The Rental model
use Carbon\Carbon; // Date library used by Laravel
use Illuminate\Database\Eloquent\Builder; // A database query that can still be extended

trait CalculatesRentals // Every business rule and formula, shared by all controllers that need it
{ // Start of the trait
    public const MONTHLY_MULTIPLIER = 25; // A month costs 25 x the daily price

    public const MAX_TOTAL_GARAGES = 999; // All garage types together may not exceed 999

    public function isValidNik(string $nik): bool // NIK must be exactly 16 digits
    { // Start of isValidNik()
        return preg_match('/^[0-9]{16}$/', $nik) === 1; // Only digits, exactly 16 of them
    } // End of isValidNik()

    public function maxDailyDays(Carbon $startDate): int // Longest daily rental that starts on this date
    { // Start of maxDailyDays()
        return $startDate->daysInMonth - 1; // Days in that month minus 1 (Feb 28 -> 27, 31-day month -> 30)
    } // End of maxDailyDays()

    public function monthlyDays(Carbon $startDate): int // Length of a monthly rental that starts on this date
    { // Start of monthlyDays()
        return $startDate->daysInMonth; // The number of days in the start month
    } // End of monthlyDays()

    public function isValidDays(RentalType $type, int $days, Carbon $startDate): bool // Booking limits for a rental or a tenant's extension
    { // Start of isValidDays()
        if ($type === RentalType::Monthly) { // Monthly ...
            return $days === $this->monthlyDays($startDate); // ... must be exactly one month
        } // End of the monthly case

        return $days >= 1 && $days <= $this->maxDailyDays($startDate); // Daily: from 1 to (month's days - 1)
    } // End of isValidDays()

    public function endDate(Carbon $startDate, int $days): Carbon // End date = start date + days
    { // Start of endDate()
        return $startDate->copy()->addDays($days); // copy() so the start date itself is not changed
    } // End of endDate()

    public function rentalCost(RentalType $type, float $dailyPrice, int $days): float // Cost of the garage itself
    { // Start of rentalCost()
        if ($type === RentalType::Monthly) { // Monthly rental ...
            return self::MONTHLY_MULTIPLIER * $dailyPrice; // ... costs 25 x daily price
        } // End of the monthly case

        return $dailyPrice * $days; // Daily rental: daily price x days
    } // End of rentalCost()

    public function hasCharger(GarageType $type): bool // Only electric car garages can charge a battery
    { // Start of hasCharger()
        return $type === GarageType::ElectricCar; // True for E garages
    } // End of hasCharger()

    public function isValidBattery(GarageType $type, ?float $remainingKwh, ?float $capacity): bool // Battery numbers for an electric car garage
    { // Start of isValidBattery()
        if (! $this->hasCharger($type)) { // No charger in this garage ...
            return true; // ... so there is nothing to check
        } // End of the no-charging case

        $capacityOk = $capacity !== null && $capacity > 0; // Battery size (max kWh) must be filled and more than 0
        $remainingOk = $remainingKwh !== null && $remainingKwh >= 0 && $remainingKwh < $capacity; // kWh left: 0 up to less than full

        return $capacityOk && $remainingOk; // Valid only if both checks pass
    } // End of isValidBattery()

    public function batteryPercent(float $remainingKwh, float $capacity): int // How full the battery is, in whole percent (0-99)
    { // Start of batteryPercent()
        return min(99, (int) floor($remainingKwh / $capacity * 100)); // kWh left / battery size x 100, rounded down; never 100 because it isn't full
    } // End of batteryPercent()

    public function kwhToFull(float $remainingKwh, float $capacity): float // kWh needed to fill the battery
    { // Start of kwhToFull()
        return round($capacity - $remainingKwh, 2); // Battery size - kWh left
    } // End of kwhToFull()

    public function chargingCost(GarageType $type, ?float $kwhToFull, ?float $pricePerKwh): float // Cost of charging the battery once
    { // Start of chargingCost()
        if (! $this->hasCharger($type)) { // No charger ...
            return 0; // ... no charging cost
        } // End of the no-charging case

        return (float) $kwhToFull * (float) $pricePerKwh; // kWh to full x price per kWh
    } // End of chargingCost()

    public function splitExtraDays(Carbon $oldEndDate, int $days): array // Extension days -> whole months + leftover days
    { // Start of splitExtraDays()
        $blockStart = $oldEndDate->copy(); // Start counting from the old end date
        $daysLeft = $days; // Days still to be split
        $months = 0; // Whole months found so far

        while ($daysLeft >= $blockStart->daysInMonth) { // Enough days left to fill the month this block starts in
            $monthLength = $blockStart->daysInMonth; // Length of that month (28, 29, 30 or 31)
            $daysLeft -= $monthLength; // Use up one month of days
            $blockStart->addDays($monthLength); // The next block starts one month later
            $months++; // Count this month
        } // End of the loop: what is left is always less than that month's days

        return ['months' => $months, 'daily_days' => $daysLeft]; // e.g. 30 Nov + 31 days -> 1 month + 1 day
    } // End of splitExtraDays()

    public function extensionCost(Carbon $oldEndDate, int $days, float $dailyPrice): float // Price of an extension
    { // Start of extensionCost()
        $split = $this->splitExtraDays($oldEndDate, $days); // Whole months + leftover days

        $monthlyCost = $split['months'] * self::MONTHLY_MULTIPLIER * $dailyPrice; // Each whole month costs 25 x daily price
        $dailyCost = $split['daily_days'] * $dailyPrice; // Each leftover day costs the daily price

        return max(0, $monthlyCost + $dailyCost); // Never below 0
    } // End of extensionCost()

    public function isWithinBookingLimit(Carbon $oldEndDate, int $days): bool // May a tenant do this extension alone in the app?
    { // Start of isWithinBookingLimit()
        $split = $this->splitExtraDays($oldEndDate, $days); // Whole months + leftover days

        $isOneMonth = $split['months'] === 1 && $split['daily_days'] === 0; // Exactly one month
        $isDailyOnly = $split['months'] === 0 && $split['daily_days'] >= 1; // 1 to (month's days - 1) days

        return $isOneMonth || $isDailyOnly; // Anything else must be asked from the admin
    } // End of isWithinBookingLimit()

    public function normalizePlate(?string $plate): ?string // Writes a plate the standard way ('l1234ab' -> 'L 1234 AB'); null if it isn't a valid plate
    { // Start of normalizePlate()
        $compact = strtoupper(preg_replace('/\s+/', '', (string) $plate)); // Capitals, no spaces
        if (preg_match('/^([A-Z]{1,2})([0-9]{1,4})([A-Z]{0,3})$/', $compact, $parts) !== 1) { // 1-2 letters, 1-4 digits, 0-3 letters ...
            return null; // ... anything else is not a plate
        } // End of the shape check

        return trim($parts[1] . ' ' . $parts[2] . ' ' . $parts[3]); // Area code, number and letters with one space between
    } // End of normalizePlate()

    public function rentalsOverlapping(Carbon $start, Carbon $end): Builder // Rentals that use the garage on any night from start to end (the end date is the day the vehicle leaves)
    { // Start of rentalsOverlapping()
        return Rental::whereDate('start_date', '<', $end) // Starts before the new booking ends ...
            ->whereDate('end_date', '>', $start); // ... and ends after it starts; a booking may end on the day another starts
    } // End of rentalsOverlapping()

    public function needsPlate(VehicleType $type): bool // Every vehicle needs a plate number except a bicycle
    { // Start of needsPlate()
        return $type !== VehicleType::Bicycle; // False only for bicycles
    } // End of needsPlate()

    public function compatibleGarageTypes(VehicleType $type): array // Which garage types a vehicle may use
    { // Start of compatibleGarageTypes()
        return match ($type) { // Choose by vehicle type
            VehicleType::Bicycle, VehicleType::Motorcycle => [GarageType::Bicycle], // Bicycle and motorcycle -> S garages
            VehicleType::Car => [GarageType::Car], // Petrol car -> C garages only
            VehicleType::ElectricCar => [GarageType::Car, GarageType::ElectricCar], // Electric car -> C or E garages
        }; // End of match
    } // End of compatibleGarageTypes()

    public function garageIdPrefix(GarageType $type): string // First letter of a garage ID
    { // Start of garageIdPrefix()
        return match ($type) { // Pick the letter by garage type
            GarageType::Bicycle => 'S', // S001, S002, ...
            GarageType::Car => 'C', // C001, C002, ...
            GarageType::ElectricCar => 'E', // E001, E002, ...
        }; // End of match
    } // End of garageIdPrefix()

    public function generateGarageId(GarageType $type, int $sequence): string // Builds an ID like 'C010'
    { // Start of generateGarageId()
        return $this->garageIdPrefix($type) . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT); // Letter + number padded to 3 digits
    } // End of generateGarageId()

    public function refreshGarageStatuses(): void // A garage is used while a rental covers today, otherwise available
    { // Start of refreshGarageStatuses()
        $busyGarageIds = Rental::whereDate('start_date', '<=', today()) // Rentals that started today or earlier ...
            ->whereDate('end_date', '>', today()) // ... and end after today (on the end date the vehicle leaves, so the garage is free again)
            ->pluck('garage_id'); // Only their garage IDs

        Garage::whereIn('garage_id', $busyGarageIds)->update(['status' => GarageStatus::Used]); // Those garages are used
        Garage::whereNotIn('garage_id', $busyGarageIds)->update(['status' => GarageStatus::Available]); // All others are available
    } // End of refreshGarageStatuses()
} // End of the trait
