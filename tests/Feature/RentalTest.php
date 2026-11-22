<?php // Start of a PHP file

namespace Tests\Feature; // This file lives in tests/Feature

use App\Models\Garage; // The Garage model
use App\Models\Rental; // The Rental model
use App\Models\Tenant; // The Tenant model
use App\Models\Vehicle; // The Vehicle model
use Carbon\Carbon; // Date library (to fix "today" in tests)
use Illuminate\Foundation\Testing\RefreshDatabase; // Fresh database for every test
use Illuminate\Http\UploadedFile; // Fake uploaded files
use Illuminate\Support\Facades\Storage; // File storage (faked in tests)
use Tests\TestCase; // Laravel's base test class

class RentalTest extends TestCase // Tests for Rent New Garage
{ // Start of the class
    use RefreshDatabase; // Rebuild the tables before each test

    private Tenant $tenant; // An accepted tenant used in every test

    protected function setUp(): void // Runs before each test
    { // Start of setUp()
        parent::setUp(); // Laravel's own preparation
        Carbon::setTestNow('2026-02-15 10:00:00'); // "Today" is 15 Feb 2026 (February has 28 days)
        Storage::fake('public'); // Uploaded files go to a fake disk
        $this->tenant = $this->makeTenant('budi@mail.test', '3578010101010001', true); // Accepted tenant
        Garage::create(['garage_id' => 'S001', 'type' => 'bicycle', 'daily_price' => 5000, 'status' => 'available']); // Bicycle garage
        Garage::create(['garage_id' => 'C001', 'type' => 'car', 'daily_price' => 50000, 'status' => 'available']); // Car garage
        Garage::create(['garage_id' => 'E001', 'type' => 'electric_car', 'daily_price' => 70000, 'electricity_price_per_kwh' => 2500, 'status' => 'available']); // Electric car garage
    } // End of setUp()

    protected function tearDown(): void // Runs after each test
    { // Start of tearDown()
        Carbon::setTestNow(); // Back to the real date
        parent::tearDown(); // Laravel's own clean-up
    } // End of tearDown()

    private function makeTenant(string $email, string $nik, bool $accepted): Tenant // Creates a tenant, accepted or pending
    { // Start of makeTenant()
        $tenant = Tenant::create(['nik' => $nik, 'name' => 'Tenant ' . $email, 'phone_number' => '081234567890', 'email' => $email, 'password' => 'secret123']); // Pending tenant
        if ($accepted) { // Accept it when asked
            $tenant->update(['verification_status' => 'accepted', 'verified_at' => now()]); // Accepted
        } // End of the accept step

        return $tenant; // The tenant
    } // End of makeTenant()

    private function carRental(array $changes = []): array // A correct daily car rental form, with optional changes
    { // Start of carRental()
        return array_merge(['vehicle_type' => 'car', 'plate_number' => 'L 1234 AB', 'brand_model' => 'Toyota Avanza', 'rental_type' => 'daily', 'days' => 5, 'start_date' => '2026-02-15', 'garage_id' => 'C001'], $changes); // Defaults + changes (start date = today)
    } // End of carRental()

    private function electricRental(array $changes = []): array // A correct electric car rental in E001, with optional changes
    { // Start of electricRental()
        return array_merge(['vehicle_type' => 'electric_car', 'plate_number' => 'L 9 EV', 'brand_model' => 'Hyundai Ioniq 5', 'battery_capacity_kwh' => 60, 'rental_type' => 'daily', 'days' => 2, 'start_date' => '2026-02-15', 'garage_id' => 'E001', 'remaining_kwh' => 20], $changes); // Defaults + changes
    } // End of electricRental()

    private function proof(): UploadedFile // A fake 100 KB jpg
    { // Start of proof()
        return UploadedFile::fake()->image('proof.jpg')->size(100); // Fake image
    } // End of proof()

    public function test_a_pending_tenant_cannot_rent(): void // Scenario: not verified yet
    { // Start of the test
        $pending = $this->makeTenant('sari@mail.test', '3578010101010002', false); // Pending tenant
        $this->actingAs($pending)->get('/tenant/rentals/create')->assertRedirect('/tenant'); // Sent back to the dashboard
    } // End of the test

    public function test_only_free_garages_that_fit_the_vehicle_are_listed(): void // Scenario: garage list filter
    { // Start of the test
        $this->actingAs($this->tenant)->get('/tenant/rentals/create?vehicle_type=car') // A petrol car
            ->assertSee('C001 - Car Garage')->assertDontSee('E001 - ')->assertDontSee('S001 - '); // Only C garages
        $this->actingAs($this->tenant)->get('/tenant/rentals/create?vehicle_type=electric_car') // An electric car
            ->assertSee('C001 - Car Garage')->assertSee('E001 - Electric Car Garage')->assertDontSee('S001 - '); // C and E garages
    } // End of the test

    public function test_the_calendar_starts_today_and_knows_booked_days(): void // Scenario: booking calendar
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental(['start_date' => '2026-02-20']) + ['payment_proof' => $this->proof()]); // C001 booked 20-25 Feb

        $this->actingAs($this->tenant)->get('/tenant/rentals/create?vehicle_type=car') // The rent form
            ->assertSee('data-today="2026-02-15"', false) // Days before today are greyed out
            ->assertSee('&quot;C001&quot;:[[&quot;2026-02-20&quot;,&quot;2026-02-25&quot;]]', false) // The calendar knows C001 is booked 20-25 Feb
            ->assertSee('Daily (1 to 27 days)')->assertSee('Monthly (28 days)'); // Limits for February 2026
    } // End of the test

    public function test_a_booking_starts_today_or_later_but_never_in_the_past(): void // Scenario: start date checked by the server
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['start_date' => '2026-02-14']))->assertSessionHasErrors('start_date'); // Yesterday: refused
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['start_date' => null]))->assertSessionHasErrors('start_date'); // Missing: refused
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['start_date' => '2026-02-15']))->assertSessionHasNoErrors(); // Today: fine
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental(['start_date' => '2026-03-10', 'days' => 30]) + ['payment_proof' => $this->proof()]); // 10 Mar, 30 days (March has 31)

        $rental = Rental::first(); // The saved rental
        $this->assertSame('2026-04-09', $rental->end_date->toDateString()); // 10 Mar + 30 days
        $this->assertSame('available', Garage::find('C001')->status->value); // Not used yet: the booking starts later
    } // End of the test

    public function test_only_tenants_can_book_a_garage(): void // Scenario: booking is done by the tenant on the website
    { // Start of the test
        $admin = \App\Models\Admin::create(['name' => 'Administrator', 'email' => 'admin@garage.test', 'password' => 'admin12345']); // The admin
        $this->actingAs($admin)->get('/tenant/rentals/create')->assertForbidden(); // The admin has no booking page
        $this->actingAs($admin)->post('/tenant/rentals', $this->carRental() + ['payment_proof' => $this->proof()])->assertForbidden(); // Nor can it save one
    } // End of the test

    public function test_the_preview_shows_the_total_before_paying(): void // Scenario: Calculate total
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental())->assertRedirect('/tenant/rentals/preview'); // Checked, then shown on its own page
        $this->actingAs($this->tenant)->get('/tenant/rentals/preview') // The confirm page
            ->assertOk()->assertSee('Rp 50.000 per day')->assertSee('5 x Rp 50.000 = Rp 250.000')->assertSee('20 Feb 2026'); // Daily price, total and end date
        $this->assertDatabaseCount('rentals', 0); // Nothing saved yet
    } // End of the test

    public function test_a_missing_proof_is_shown_in_red_on_the_confirm_page(): void // Scenario: Confirm and pay without an image
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental()); // Details checked
        $this->actingAs($this->tenant)->get('/tenant/rentals/preview'); // Confirm page opened

        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental()) // Confirm without a proof
            ->assertRedirect('/tenant/rentals/preview')->assertSessionHasErrors('payment_proof'); // Back to the confirm page with the error
        $this->actingAs($this->tenant)->followingRedirects()->post('/tenant/rentals', $this->carRental()) // Same again, following the redirect
            ->assertSee('Confirm Rental')->assertSee('is-invalid')->assertSee('Upload the payment proof image before you confirm.'); // The confirm page with the field in red
        $this->assertDatabaseCount('rentals', 0); // Nothing saved
    } // End of the test

    public function test_change_details_keeps_what_was_typed(): void // Scenario: back from the confirm page
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['brand_model' => 'Daihatsu Xenia'])); // Details checked

        $this->actingAs($this->tenant)->get('/tenant/rentals/create?vehicle_type=car&edit=1') // "Change details"
            ->assertSee('value="Daihatsu Xenia"', false)->assertSee('value="L 1234 AB"', false); // The typed values are still there
    } // End of the test

    public function test_a_daily_rental_is_saved_with_the_right_dates_and_cost(): void // Scenario: daily rental
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental() + ['payment_proof' => $this->proof()]) // Confirm and pay
            ->assertRedirect('/tenant/rentals'); // Goes to My Rentals

        $rental = Rental::first(); // The saved rental
        $this->assertSame('2026-02-20', $rental->end_date->toDateString()); // 15 Feb + 5 days
        $this->assertEquals(250000, $rental->total_cost); // 5 x 50,000
        $this->assertSame('used', Garage::find('C001')->status->value); // Garage is now used
        Storage::disk('public')->assertExists($rental->payment_proof); // The proof image was saved
    } // End of the test

    public function test_a_monthly_rental_lasts_the_days_of_the_start_month_and_costs_25_days(): void // Scenario: monthly rental
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental(['rental_type' => 'monthly', 'days' => null]) + ['payment_proof' => $this->proof()]); // Monthly

        $rental = Rental::first(); // The saved rental
        $this->assertSame(28, $rental->days); // February 2026 has 28 days
        $this->assertSame('2026-03-15', $rental->end_date->toDateString()); // 15 Feb -> 15 Mar
        $this->assertEquals(1250000, $rental->total_cost); // 25 x 50,000
    } // End of the test

    public function test_a_daily_rental_is_at_most_the_month_days_minus_one(): void // Scenario: daily limit follows the start month
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['days' => 27]))->assertSessionHasNoErrors(); // 27 days is allowed from February
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['days' => 28]))->assertSessionHasErrors('days'); // 28 is not
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['start_date' => '2026-03-01', 'days' => 30]))->assertSessionHasNoErrors(); // March has 31 days, so 30 is allowed
    } // End of the test

    public function test_a_monthly_rental_needs_no_days(): void // Scenario: days only for daily
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['rental_type' => 'monthly', 'days' => 7, 'start_date' => '2026-03-05'])); // Days sent by mistake
        $this->actingAs($this->tenant)->get('/tenant/rentals/preview')->assertSee('Monthly, 31 days')->assertSee('05 Apr 2026'); // Ignored: March has 31 days
    } // End of the test

    public function test_charging_is_billed_once_in_an_electric_car_garage(): void // Scenario: battery charging
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->electricRental() + ['payment_proof' => $this->proof()]); // Electric car in E001

        $rental = Rental::first(); // The saved rental
        $this->assertEquals(140000, $rental->rental_cost); // 2 x 70,000
        $this->assertEquals(100000, $rental->charging_cost); // 40 kWh x 2,500
        $this->assertEquals(240000, $rental->total_cost); // Garage + charging
        $this->assertSame(33, $rental->battery_level_percent); // 20 of 60 kWh = 33%, worked out by the app
        $this->assertEquals(40, $rental->kwh_to_full); // 60 - 20 = 40 kWh until full
    } // End of the test

    public function test_battery_numbers_are_checked_in_an_electric_car_garage(): void // Scenario: wrong battery input
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->electricRental(['remaining_kwh' => 60]))->assertSessionHasErrors('remaining_kwh'); // Already full
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->electricRental(['remaining_kwh' => 70]))->assertSessionHasErrors('remaining_kwh'); // More than the 60 kWh battery
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->electricRental(['remaining_kwh' => null]))->assertSessionHasErrors('remaining_kwh'); // Missing
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->electricRental(['battery_capacity_kwh' => null]))->assertSessionHasErrors('battery_capacity_kwh'); // Battery size missing
    } // End of the test

    public function test_an_electric_car_in_a_car_garage_pays_no_charging(): void // Scenario: EV in a C garage
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->electricRental(['garage_id' => 'C001']) + ['payment_proof' => $this->proof()]); // EV in C001

        $rental = Rental::first(); // The saved rental
        $this->assertEquals(0, $rental->charging_cost); // No charger, no charging cost
        $this->assertNull($rental->kwh_to_full); // Battery numbers are not kept
    } // End of the test

    public function test_a_petrol_car_cannot_use_an_electric_car_garage(): void // Scenario: wrong garage type
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['garage_id' => 'E001']))->assertSessionHasErrors('garage_id'); // Refused
    } // End of the test

    public function test_a_rented_garage_is_not_available(): void // Scenario: garage already rented
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental() + ['payment_proof' => $this->proof()]); // First rental of C001
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['plate_number' => 'L 5678 CD'])) // Another car, same garage
            ->assertSessionHasErrors('garage_id'); // Refused
    } // End of the test

    public function test_the_same_plate_cannot_have_two_active_rentals(): void // Scenario: duplicate plate
    { // Start of the test
        Garage::create(['garage_id' => 'C002', 'type' => 'car', 'daily_price' => 50000, 'status' => 'available']); // A second car garage
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental() + ['payment_proof' => $this->proof()]); // L 1234 AB in C001
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['garage_id' => 'C002'])) // Same plate in C002
            ->assertSessionHasErrors('plate_number'); // Refused
    } // End of the test

    public function test_a_bicycle_needs_no_plate_but_other_vehicles_do(): void // Scenario: plate rule
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', ['vehicle_type' => 'bicycle', 'brand_model' => 'Polygon', 'rental_type' => 'daily', 'days' => 3, 'start_date' => '2026-02-15', 'garage_id' => 'S001'])->assertSessionHasNoErrors(); // Bicycle without a plate
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['plate_number' => null]))->assertSessionHasErrors('plate_number'); // Car without a plate
    } // End of the test

    public function test_the_payment_proof_must_be_a_small_image(): void // Scenario: upload rules
    { // Start of the test
        $pdf = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'); // Not an image
        $big = UploadedFile::fake()->image('proof.jpg')->size(2048); // 2 MB image

        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental() + ['payment_proof' => $pdf])->assertSessionHasErrors('payment_proof'); // PDF refused
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental() + ['payment_proof' => $big])->assertSessionHasErrors('payment_proof'); // Too big
        $this->assertDatabaseCount('rentals', 0); // Nothing saved
    } // End of the test

    public function test_the_garage_becomes_available_again_after_the_rental_ends(): void // Scenario: automatic status reset
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental() + ['payment_proof' => $this->proof()]); // Ends 20 Feb
        Carbon::setTestNow('2026-02-21 09:00:00'); // One day after the end date

        $this->actingAs($this->tenant)->get('/tenant/rentals/create?vehicle_type=car')->assertSee('C001 - Car Garage'); // Free again
        $this->assertSame('available', Garage::find('C001')->status->value); // Status updated
        Carbon::setTestNow('2026-02-20 09:00:00'); // The end date itself
        $this->actingAs($this->tenant)->get('/tenant/rentals/create?vehicle_type=car'); // Any page that refreshes the statuses
        $this->assertSame('available', Garage::find('C001')->status->value); // The vehicle leaves on the end date, so the garage is free again
    } // End of the test
    public function test_a_garage_can_be_booked_before_and_after_another_booking(): void // Scenario: future bookings
    { // Start of the test
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental(['start_date' => '2026-02-20']) + ['payment_proof' => $this->proof()]); // C001 booked 20-25 Feb

        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['plate_number' => 'L 2 AB', 'days' => 6])) // 15-21 Feb overlaps
            ->assertSessionHasErrors('garage_id'); // Refused
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['plate_number' => 'L 2 AB', 'days' => 4])) // 15-19 Feb is before it
            ->assertSessionHasNoErrors(); // Free
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['plate_number' => 'L 2 AB', 'start_date' => '2026-02-26'])) // From 26 Feb, after it
            ->assertSessionHasNoErrors(); // Free
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['plate_number' => 'L 2 AB', 'days' => 5])) // 15-20 Feb: leaves on the day the booking starts
            ->assertSessionHasNoErrors(); // Free, like a hotel check-out and check-in on the same day
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['plate_number' => 'L 2 AB', 'start_date' => '2026-02-25'])) // From 25 Feb, the day the booking ends
            ->assertSessionHasNoErrors(); // Free
    } // End of the test

    public function test_a_tenant_can_rent_several_garages_on_the_same_day(): void // Scenario: more than one garage at the same time
    { // Start of the test
        Garage::create(['garage_id' => 'C002', 'type' => 'car', 'daily_price' => 50000, 'status' => 'available']); // A second car garage
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental() + ['payment_proof' => $this->proof()]); // Car 1 in C001
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental(['plate_number' => 'L 5678 CD', 'garage_id' => 'C002']) + ['payment_proof' => $this->proof()]); // Car 2 in C002
        $this->actingAs($this->tenant)->post('/tenant/rentals', ['vehicle_type' => 'motorcycle', 'plate_number' => 'L 77 MC', 'brand_model' => 'Honda Beat', 'rental_type' => 'daily', 'days' => 3, 'start_date' => '2026-02-15', 'garage_id' => 'S001', 'payment_proof' => $this->proof()]); // A motorcycle in S001

        $this->assertDatabaseCount('rentals', 3); // Three garages for one tenant on the same day
    } // End of the test

    public function test_every_vehicle_has_its_own_plate(): void // Scenario: plate rules
    { // Start of the test
        $other = $this->makeTenant('sari@mail.test', '3578010101010002', true); // Another tenant
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental(['plate_number' => 'l1234ab', 'days' => 2]) + ['payment_proof' => $this->proof()]); // Typed without spaces

        $this->assertSame('L 1234 AB', Vehicle::first()->plate_number); // Saved the standard way
        $this->actingAs($other)->post('/tenant/rentals/preview', $this->carRental(['start_date' => '2026-02-20'])) // Same plate by another tenant
            ->assertSessionHasErrors(['plate_number' => "Plate L 1234 AB is registered to another tenant's vehicle."]); // Refused
        $this->actingAs($this->tenant)->post('/tenant/rentals/preview', $this->carRental(['plate_number' => 'ABC'])) // Not a plate
            ->assertSessionHasErrors(['plate_number' => 'Enter a plate number like L 1234 AB.']); // Refused
        $this->actingAs($this->tenant)->post('/tenant/rentals', $this->carRental(['start_date' => '2026-02-20']) + ['payment_proof' => $this->proof()]); // Same car again later
        $this->assertDatabaseCount('vehicles', 1); // Still one vehicle for that plate
    } // End of the test

    public function test_the_charging_part_is_worked_out_from_the_kwh_left(): void // Scenario: charging part and summary
    { // Start of the test
        $this->actingAs($this->tenant)->get('/tenant/rentals/create?vehicle_type=electric_car') // The rent form for an electric car
            ->assertSee('id="charging-box"', false)->assertSee('kWh left in the battery') // Charging part, hidden until an E garage is chosen
            ->assertSee('data-kwh-prices="{&quot;E001&quot;:2500}"', false); // Only E garages have a charging price
        $this->actingAs($this->tenant)->followingRedirects()->post('/tenant/rentals/preview', $this->electricRental()) // 20 of 60 kWh left
            ->assertSee('20 of 60 kWh left (33%), 40 kWh until full')->assertSee('40 kWh x Rp 2.500 = Rp 100.000'); // Worked out by the app
        $this->actingAs($this->tenant)->get('/tenant/rentals/create?vehicle_type=car')->assertDontSee('id="charging-box"', false); // Petrol cars have no charging part
    } // End of the test
} // End of the class
