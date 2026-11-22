<?php // Start of a PHP file

namespace Tests\Feature; // This file lives in tests/Feature

use App\Models\Admin; // The Admin model
use App\Models\Extension; // The Extension model
use App\Models\Garage; // The Garage model
use App\Models\Rental; // The Rental model
use App\Models\Tenant; // The Tenant model
use App\Models\Vehicle; // The Vehicle model
use Carbon\Carbon; // Date library (to fix "today" in tests)
use Illuminate\Foundation\Testing\RefreshDatabase; // Fresh database for every test
use Illuminate\Http\UploadedFile; // Fake uploaded files
use Illuminate\Support\Facades\Storage; // File storage (faked in tests)
use Tests\TestCase; // Laravel's base test class

class ExtensionTest extends TestCase // Tests for Extend Own Rental (tenant) and Extend Garage Rental (admin)
{ // Start of the class
    use RefreshDatabase; // Rebuild the tables before each test

    private Tenant $tenant; // The tenant who owns the rental

    private Admin $admin; // The admin

    private Rental $rental; // A rental of C001 that ends on 30 Nov 2026

    protected function setUp(): void // Runs before each test
    { // Start of setUp()
        parent::setUp(); // Laravel's own preparation
        Carbon::setTestNow('2026-11-25 10:00:00'); // "Today" is 25 Nov 2026
        Storage::fake('public'); // Uploaded files go to a fake disk
        $this->admin = Admin::create(['name' => 'Administrator', 'email' => 'admin@garage.test', 'password' => 'admin12345']); // The admin
        $this->tenant = $this->acceptedTenant('budi@mail.test', '3578010101010001'); // The owner
        Garage::create(['garage_id' => 'C001', 'type' => 'car', 'daily_price' => 1000, 'status' => 'used']); // Rp 1,000 per day, easy to check
        $vehicle = Vehicle::create(['user_id' => $this->tenant->user_id, 'type' => 'car', 'plate_number' => 'L 1 AB', 'brand_model' => 'Avanza']); // The car
        $this->rental = Rental::create(['vehicle_id' => $vehicle->vehicle_id, 'garage_id' => 'C001', 'rental_type' => 'daily', 'days' => 10, 'start_date' => '2026-11-20', 'end_date' => '2026-11-30', 'rental_cost' => 10000, 'charging_cost' => 0, 'total_cost' => 10000, 'payment_proof' => 'x.jpg']); // Ends 30 Nov
    } // End of setUp()

    protected function tearDown(): void // Runs after each test
    { // Start of tearDown()
        Carbon::setTestNow(); // Back to the real date
        parent::tearDown(); // Laravel's own clean-up
    } // End of tearDown()

    private function acceptedTenant(string $email, string $nik): Tenant // Creates an accepted tenant
    { // Start of acceptedTenant()
        $tenant = Tenant::create(['nik' => $nik, 'name' => 'Tenant ' . $email, 'phone_number' => '081234567890', 'email' => $email, 'password' => 'secret123']); // Pending
        $tenant->update(['verification_status' => 'accepted', 'verified_at' => now()]); // Accepted

        return $tenant; // The tenant
    } // End of acceptedTenant()

    private function proof(): UploadedFile // A fake 100 KB jpg
    { // Start of proof()
        return UploadedFile::fake()->image('proof.jpg')->size(100); // Fake image
    } // End of proof()

    public function test_a_tenant_can_extend_monthly_by_the_days_of_the_month_it_starts_in(): void // Scenario: tenant monthly from 30 Nov
    { // Start of the test
        $this->actingAs($this->tenant)->post("/tenant/rentals/{$this->rental->rental_id}/extend", ['rental_type' => 'monthly', 'payment_proof' => $this->proof()]) // Monthly
            ->assertRedirect('/tenant/rentals'); // Back to My Rentals

        $this->assertSame('2026-12-30', $this->rental->fresh()->end_date->toDateString()); // November has 30 days -> 30 Dec
        $this->assertEquals(25000, Extension::first()->extra_cost); // 25 x 1,000
    } // End of the test

    public function test_a_tenant_daily_extension_is_at_most_the_month_days_minus_one(): void // Scenario: tenant daily limit
    { // Start of the test
        $this->actingAs($this->tenant)->post("/tenant/rentals/{$this->rental->rental_id}/extend/preview", ['rental_type' => 'daily', 'days' => 29])->assertOk()->assertSee('Rp 29.000'); // 29 allowed
        $this->actingAs($this->tenant)->post("/tenant/rentals/{$this->rental->rental_id}/extend/preview", ['rental_type' => 'daily', 'days' => 30])->assertSessionHasErrors('days'); // 30 must go to the admin
    } // End of the test

    public function test_a_tenant_cannot_extend_someone_elses_rental(): void // Scenario: not the owner
    { // Start of the test
        $other = $this->acceptedTenant('sari@mail.test', '3578010101010002'); // Another tenant
        $this->actingAs($other)->get("/tenant/rentals/{$this->rental->rental_id}/extend")->assertNotFound(); // Hidden
    } // End of the test

    public function test_an_ended_rental_cannot_be_extended(): void // Scenario: rental already over
    { // Start of the test
        Carbon::setTestNow('2026-12-05 10:00:00'); // After the end date
        $this->actingAs($this->tenant)->get("/tenant/rentals/{$this->rental->rental_id}/extend")->assertForbidden(); // Tenant refused
        $this->actingAs($this->admin)->get("/admin/extensions/{$this->rental->rental_id}/create")->assertForbidden(); // Admin refused
    } // End of the test

    public function test_the_admin_sees_garages_rented_today(): void // Scenario: admin list
    { // Start of the test
        $this->actingAs($this->admin)->get('/admin/extensions')->assertOk()->assertSee('C001 (Car Garage)')->assertSee('30 Nov 2026'); // Listed with its end date
    } // End of the test

    public function test_admin_extension_of_31_days_from_30_november_is_one_month_and_one_day(): void // Scenario: 30 Nov + 31 days
    { // Start of the test
        $this->actingAs($this->admin)->post("/admin/extensions/{$this->rental->rental_id}/preview", ['days' => 31]) // Preview
            ->assertOk()->assertSee('31 Dec 2026')->assertSee('Rp 26.000'); // 25,000 + 1,000
        $this->actingAs($this->admin)->post("/admin/extensions/{$this->rental->rental_id}", ['days' => 31, 'payment_proof' => $this->proof()]) // Save
            ->assertRedirect('/admin/extensions'); // Back to the list

        $extension = Extension::first(); // The saved extension
        $this->assertEquals(26000, $extension->extra_cost); // Amount paid
        $this->assertSame('2026-11-30', $extension->old_end_date->toDateString()); // History: old end date
        $this->assertSame('2026-12-31', $this->rental->fresh()->end_date->toDateString()); // Rental moved to 31 Dec
        Storage::disk('public')->assertExists($extension->payment_proof); // Proof saved
    } // End of the test

    public function test_admin_extensions_split_into_real_month_lengths(): void // Scenario: 92 and 63 days from 30 Nov
    { // Start of the test
        $this->actingAs($this->admin)->post("/admin/extensions/{$this->rental->rental_id}/preview", ['days' => 92]) // Nov 30 + Dec 31 + Jan 31
            ->assertSee('3 x 25 x Rp 1.000 = Rp 75.000')->assertSee('Rp 75.000'); // 3 months, no extra days
        $this->actingAs($this->admin)->post("/admin/extensions/{$this->rental->rental_id}/preview", ['days' => 63]) // Nov 30 + Dec 31 + 2 days
            ->assertSee('2 x 25 x Rp 1.000 = Rp 50.000')->assertSee('2 x Rp 1.000 = Rp 2.000')->assertSee('Rp 52.000'); // 2 months + 2 days
    } // End of the test

    public function test_every_extension_needs_a_payment_proof(): void // Scenario: proof required for tenant and admin
    { // Start of the test
        $this->actingAs($this->admin)->post("/admin/extensions/{$this->rental->rental_id}", ['days' => 31])->assertSessionHasErrors('payment_proof'); // Admin without proof
        $this->actingAs($this->tenant)->post("/tenant/rentals/{$this->rental->rental_id}/extend", ['rental_type' => 'monthly'])->assertSessionHasErrors('payment_proof'); // Tenant without proof
        $this->assertDatabaseCount('extensions', 0); // Nothing saved
        $this->assertSame('2026-11-30', $this->rental->fresh()->end_date->toDateString()); // End date unchanged
    } // End of the test

    public function test_a_tenant_cannot_use_the_admin_extension_pages(): void // Scenario: role check
    { // Start of the test
        $this->actingAs($this->tenant)->get('/admin/extensions')->assertForbidden(); // Admin only
    } // End of the test
} // End of the class
