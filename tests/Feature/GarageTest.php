<?php // Start of a PHP file

namespace Tests\Feature; // This file lives in tests/Feature

use App\Models\Admin; // The Admin model
use App\Models\Garage; // The Garage model
use Illuminate\Foundation\Testing\RefreshDatabase; // Fresh database for every test
use Tests\TestCase; // Laravel's base test class

class GarageTest extends TestCase // Tests for Create Garages
{ // Start of the class
    use RefreshDatabase; // Rebuild the tables before each test

    private Admin $admin; // The admin used in every test

    protected function setUp(): void // Runs before each test
    { // Start of setUp()
        parent::setUp(); // Laravel's own preparation
        $this->admin = Admin::create(['name' => 'Administrator', 'email' => 'admin@garage.test', 'password' => 'admin12345']); // The admin
    } // End of setUp()

    public function test_garages_are_created_in_a_batch_with_running_ids(): void // Scenario: batch IDs
    { // Start of the test
        $this->actingAs($this->admin)->post('/admin/garages', ['type' => 'car', 'quantity' => 3, 'daily_price' => 50000]) // Create C001-C003
            ->assertSessionHas('success', '3 garages created: C001 to C003.'); // Message
        $this->actingAs($this->admin)->post('/admin/garages', ['type' => 'car', 'quantity' => 2, 'daily_price' => 60000]); // Then C004-C005

        $this->assertSame(['C001', 'C002', 'C003', 'C004', 'C005'], Garage::orderBy('garage_id')->pluck('garage_id')->all()); // IDs continue
        $this->assertEquals(60000, Garage::find('C005')->daily_price); // Each batch keeps its own price
    } // End of the test

    public function test_each_type_has_its_own_letter(): void // Scenario: S, C, E prefixes
    { // Start of the test
        $this->actingAs($this->admin)->post('/admin/garages', ['type' => 'bicycle', 'quantity' => 1, 'daily_price' => 5000]); // S001
        $this->actingAs($this->admin)->post('/admin/garages', ['type' => 'electric_car', 'quantity' => 1, 'daily_price' => 70000, 'electricity_price_per_kwh' => 2500]); // E001

        $this->assertNotNull(Garage::find('S001')); // Bicycle garage exists
        $this->assertNotNull(Garage::find('E001')); // Electric car garage exists
    } // End of the test

    public function test_electric_car_garages_need_a_kwh_price(): void // Scenario: missing kWh price
    { // Start of the test
        $this->actingAs($this->admin)->post('/admin/garages', ['type' => 'electric_car', 'quantity' => 1, 'daily_price' => 70000]) // No kWh price
            ->assertSessionHasErrors('electricity_price_per_kwh'); // Refused
        $this->assertDatabaseCount('garages', 0); // Nothing saved
    } // End of the test

    public function test_there_can_never_be_more_than_999_garages(): void // Scenario: 999 cap
    { // Start of the test
        $this->actingAs($this->admin)->post('/admin/garages', ['type' => 'car', 'quantity' => 998, 'daily_price' => 50000]); // 998 garages
        $this->actingAs($this->admin)->post('/admin/garages', ['type' => 'bicycle', 'quantity' => 2, 'daily_price' => 5000]) // 2 more would be 1000
            ->assertSessionHasErrors('quantity'); // Refused
        $this->assertDatabaseCount('garages', 998); // Still 998
    } // End of the test

    public function test_the_admin_sees_the_garage_list(): void // Scenario: list
    { // Start of the test
        $this->actingAs($this->admin)->post('/admin/garages', ['type' => 'car', 'quantity' => 1, 'daily_price' => 50000]); // C001
        $this->actingAs($this->admin)->get('/admin/garages')->assertOk()->assertSee('C001')->assertSee('Rp 1.250.000'); // Monthly price shown
    } // End of the test
} // End of the class
