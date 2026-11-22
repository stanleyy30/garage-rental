<?php // Start of a PHP file

namespace Tests\Unit; // This file lives in tests/Unit

use App\Enums\GarageType; // The GarageType enum
use App\Enums\RentalType; // The RentalType enum
use App\Enums\VehicleType; // The VehicleType enum
use App\Http\Controllers\Concerns\CalculatesRentals; // The rules being tested
use Carbon\Carbon; // Date library used by Laravel
use PHPUnit\Framework\Attributes\DataProvider; // Runs one test with several inputs
use PHPUnit\Framework\TestCase; // Plain PHPUnit test (no database needed)

class CalculatesRentalsTest extends TestCase // Unit tests for every formula in CalculatesRentals
{ // Start of the class
    private object $rules; // An object that uses the trait

    protected function setUp(): void // Runs before each test
    { // Start of setUp()
        $this->rules = new class { use CalculatesRentals; }; // A tiny class that only holds the rules
    } // End of setUp()

    public static function maxDailyDaysCases(): array // Start date => longest daily rental
    { // Start of maxDailyDaysCases()
        return [ // Cases
            'February 2026 (28 days)' => ['2026-02-15', 27], // 28 - 1
            'February 2028 (29 days)' => ['2028-02-10', 28], // 29 - 1 (leap year)
            'April (30 days)' => ['2027-04-05', 29], // 30 - 1
            'January (31 days)' => ['2027-01-10', 30], // 31 - 1
        ]; // End of the cases
    } // End of maxDailyDaysCases()

    #[DataProvider('maxDailyDaysCases')] // Run once per case above
    public function test_max_daily_days_is_the_month_length_minus_one(string $start, int $expected): void // Daily limit
    { // Start of the test
        $this->assertSame($expected, $this->rules->maxDailyDays(Carbon::parse($start))); // Compare
    } // End of the test

    public function test_monthly_rental_lasts_the_days_of_the_start_month(): void // Monthly length and end date
    { // Start of the test
        $start = Carbon::parse('2026-02-15'); // 15 Feb 2026
        $days = $this->rules->monthlyDays($start); // 28

        $this->assertSame(28, $days); // February 2026 has 28 days
        $this->assertSame('2026-03-15', $this->rules->endDate($start, $days)->toDateString()); // Ends 15 Mar
        $this->assertSame('2026-02-15', $start->toDateString()); // The start date itself is unchanged
    } // End of the test

    public function test_valid_days_follow_the_booking_limits(): void // isValidDays
    { // Start of the test
        $start = Carbon::parse('2026-02-15'); // February 2026
        $this->assertTrue($this->rules->isValidDays(RentalType::Daily, 27, $start)); // Max daily
        $this->assertFalse($this->rules->isValidDays(RentalType::Daily, 28, $start)); // One too many
        $this->assertFalse($this->rules->isValidDays(RentalType::Daily, 0, $start)); // Zero days
        $this->assertTrue($this->rules->isValidDays(RentalType::Monthly, 28, $start)); // Exactly one month
        $this->assertFalse($this->rules->isValidDays(RentalType::Monthly, 30, $start)); // Not one month
    } // End of the test

    public function test_rental_cost_is_daily_times_days_or_25_times_daily_for_a_month(): void // rentalCost
    { // Start of the test
        $this->assertEquals(250000, $this->rules->rentalCost(RentalType::Daily, 50000, 5)); // 5 x 50,000
        $this->assertEquals(1250000, $this->rules->rentalCost(RentalType::Monthly, 50000, 28)); // 25 x 50,000
    } // End of the test

    public function test_charging_cost_only_in_an_electric_car_garage(): void // chargingCost
    { // Start of the test
        $this->assertEquals(100000, $this->rules->chargingCost(GarageType::ElectricCar, 40, 2500)); // 40 kWh x 2,500
        $this->assertEquals(0, $this->rules->chargingCost(GarageType::Car, 40, 2500)); // No charger
    } // End of the test

    public function test_battery_input_rules(): void // isValidBattery
    { // Start of the test
        $e = GarageType::ElectricCar; // Electric car garage
        $this->assertTrue($this->rules->isValidBattery($e, 30, 40, 60)); // 30%, 40 of 60 kWh
        $this->assertFalse($this->rules->isValidBattery($e, 100, 40, 60)); // 100% is already full
        $this->assertFalse($this->rules->isValidBattery($e, 30, 70, 60)); // More than the battery holds
        $this->assertFalse($this->rules->isValidBattery($e, 30, 0, 60)); // Nothing to charge
        $this->assertFalse($this->rules->isValidBattery($e, 30, 40, null)); // Battery size missing
        $this->assertTrue($this->rules->isValidBattery(GarageType::Car, null, null, null)); // No charger, nothing to check
    } // End of the test

    public static function extensionCases(): array // Old end date, extra days => months, daily days, cost at Rp 1,000/day
    { // Start of extensionCases()
        return [ // Cases
            '30 Nov + 31 days' => ['2026-11-30', 31, 1, 1, 26000], // Nov 30 + 1 day
            '30 Nov + 92 days' => ['2026-11-30', 92, 3, 0, 75000], // Nov 30 + Dec 31 + Jan 31
            '30 Nov + 63 days' => ['2026-11-30', 63, 2, 2, 52000], // Nov 30 + Dec 31 + 2 days
            '10 Jan + 47 days' => ['2027-01-10', 47, 1, 16, 41000], // Jan 31 + 16 days
            '15 Feb 2026 + 27 days' => ['2026-02-15', 27, 0, 27, 27000], // Under one month: all daily
            '15 Feb 2026 + 28 days' => ['2026-02-15', 28, 1, 0, 25000], // Exactly one February
        ]; // End of the cases
    } // End of extensionCases()

    #[DataProvider('extensionCases')] // Run once per case above
    public function test_extension_days_split_into_real_months_and_leftover_days(string $oldEnd, int $days, int $months, int $dailyDays, int $cost): void // splitExtraDays + extensionCost
    { // Start of the test
        $from = Carbon::parse($oldEnd); // Old end date

        $this->assertSame(['months' => $months, 'daily_days' => $dailyDays], $this->rules->splitExtraDays($from, $days)); // Split
        $this->assertEquals($cost, $this->rules->extensionCost($from, $days, 1000)); // Price
        $this->assertSame($oldEnd, $from->toDateString()); // The old end date is unchanged
    } // End of the test

    public function test_the_tenant_may_extend_alone_only_within_booking_limits(): void // isWithinBookingLimit
    { // Start of the test
        $from = Carbon::parse('2026-11-30'); // Old end date
        $this->assertTrue($this->rules->isWithinBookingLimit($from, 29)); // Daily max
        $this->assertTrue($this->rules->isWithinBookingLimit($from, 30)); // One month
        $this->assertFalse($this->rules->isWithinBookingLimit($from, 31)); // 1 month + 1 day: ask the admin
    } // End of the test

    public function test_extension_cost_is_never_negative(): void // Never below zero
    { // Start of the test
        $this->assertGreaterThanOrEqual(0, $this->rules->extensionCost(Carbon::parse('2026-11-30'), 1, 1000)); // At least 0
    } // End of the test

    public function test_garage_ids_use_the_type_letter_and_three_digits(): void // generateGarageId
    { // Start of the test
        $this->assertSame('S001', $this->rules->generateGarageId(GarageType::Bicycle, 1)); // Bicycle
        $this->assertSame('C010', $this->rules->generateGarageId(GarageType::Car, 10)); // Car
        $this->assertSame('E999', $this->rules->generateGarageId(GarageType::ElectricCar, 999)); // Electric car
    } // End of the test

    public function test_vehicles_fit_the_right_garage_types(): void // compatibleGarageTypes + needsPlate
    { // Start of the test
        $this->assertSame([GarageType::Bicycle], $this->rules->compatibleGarageTypes(VehicleType::Motorcycle)); // Motorcycle -> S
        $this->assertSame([GarageType::Car], $this->rules->compatibleGarageTypes(VehicleType::Car)); // Car -> C
        $this->assertSame([GarageType::Car, GarageType::ElectricCar], $this->rules->compatibleGarageTypes(VehicleType::ElectricCar)); // EV -> C or E
        $this->assertFalse($this->rules->needsPlate(VehicleType::Bicycle)); // No plate
        $this->assertTrue($this->rules->needsPlate(VehicleType::Motorcycle)); // Plate
    } // End of the test

    public function test_nik_must_be_exactly_16_digits(): void // isValidNik
    { // Start of the test
        $this->assertTrue($this->rules->isValidNik('3578010101010001')); // 16 digits
        $this->assertFalse($this->rules->isValidNik('357801010101000')); // 15 digits
        $this->assertFalse($this->rules->isValidNik('35780101010100AB')); // Letters
    } // End of the test

    public function test_plates_are_written_the_standard_way(): void // normalizePlate
    { // Start of the test
        $this->assertSame('L 1234 AB', $this->rules->normalizePlate('l1234ab')); // Without spaces
        $this->assertSame('B 1 A', $this->rules->normalizePlate(' b  1   a ')); // Extra spaces
        $this->assertSame('AD 99', $this->rules->normalizePlate('AD99')); // No letters at the end
        $this->assertNull($this->rules->normalizePlate('ABC')); // No number
        $this->assertNull($this->rules->normalizePlate('L 12345 AB')); // Five digits
    } // End of the test

} // End of the class
