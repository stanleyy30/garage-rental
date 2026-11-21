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
use Tests\TestCase; // Laravel's base test class

class ReportTest extends TestCase // Tests for the four rental reports
{ // Start of the class
    use RefreshDatabase; // Rebuild the tables before each test

    private Admin $admin; // The admin

    protected function setUp(): void // Runs before each test: two tenants, three rentals, one extension
    { // Start of setUp()
        parent::setUp(); // Laravel's own preparation
        Carbon::setTestNow('2026-11-25 10:00:00'); // "Today" is 25 Nov 2026
        $this->admin = Admin::create(['name' => 'Administrator', 'email' => 'admin@garage.test', 'password' => 'admin12345']); // The admin
        Garage::create(['garage_id' => 'C001', 'type' => 'car', 'daily_price' => 1000, 'status' => 'available']); // Car garage
        Garage::create(['garage_id' => 'S001', 'type' => 'bicycle', 'daily_price' => 100, 'status' => 'available']); // Bicycle garage

        $budi = $this->vehicleOf('Budi', 'budi@mail.test', '3578010101010001'); // Budi's car
        $sari = $this->vehicleOf('Sari', 'sari@mail.test', '3578010101010002'); // Sari's bicycle

        $first = $this->rental($budi, 'C001', '2026-10-05', 5000); // October, Rp 5,000
        $this->rental($budi, 'C001', '2026-11-03', 7000); // November, Rp 7,000
        $this->rental($sari, 'S001', '2026-11-03', 300); // November, Rp 300
        Extension::create(['rental_id' => $first->rental_id, 'old_end_date' => '2026-10-10', 'new_end_date' => '2026-10-12', 'extra_days' => 2, 'extra_cost' => 2000, 'payment_proof' => 'y.jpg']); // Extension paid today (November), Rp 2,000
    } // End of setUp()

    protected function tearDown(): void // Runs after each test
    { // Start of tearDown()
        Carbon::setTestNow(); // Back to the real date
        parent::tearDown(); // Laravel's own clean-up
    } // End of tearDown()

    private function vehicleOf(string $name, string $email, string $nik): Vehicle // A tenant with one vehicle
    { // Start of vehicleOf()
        $tenant = Tenant::create(['nik' => $nik, 'name' => $name, 'phone_number' => '081234567890', 'email' => $email, 'password' => 'secret123']); // Tenant

        return Vehicle::create(['user_id' => $tenant->user_id, 'type' => 'car', 'plate_number' => 'L ' . substr($nik, -4) . ' AB', 'brand_model' => 'Test']); // Vehicle, plate within the 12-character column
    } // End of vehicleOf()

    private function rental(Vehicle $vehicle, string $garageId, string $start, float $total): Rental // A rental that started on $start
    { // Start of rental()
        return Rental::create(['vehicle_id' => $vehicle->vehicle_id, 'garage_id' => $garageId, 'rental_type' => 'daily', 'days' => 5, 'start_date' => $start, 'end_date' => Carbon::parse($start)->addDays(5), 'rental_cost' => $total, 'charging_cost' => 0, 'total_cost' => $total, 'payment_proof' => 'x.jpg']); // Saved rental
    } // End of rental()

    private function report(array $query): array // Opens the report page and returns the rows given to the view
    { // Start of report()
        return $this->actingAs($this->admin)->get('/admin/reports?' . http_build_query($query))->assertOk()->viewData('rows'); // Rows
    } // End of report()

    public function test_the_monthly_report_has_12_months_with_rentals_and_extension_income(): void // Scenario: per month in a year
    { // Start of the test
        $rows = $this->report(['type' => 'monthly', 'year' => 2026]); // 2026 report

        $this->assertCount(12, $rows); // January to December
        $this->assertSame(['October', 1, 0, 'Rp 5.000'], [$rows[9]['label'], $rows[9]['rentals'], $rows[9]['extensions'], $rows[9]['income']]); // October: 1 rental
        $this->assertSame(['November', 2, 1, 'Rp 9.300'], [$rows[10]['label'], $rows[10]['rentals'], $rows[10]['extensions'], $rows[10]['income']]); // 7,000 + 300 + 2,000
    } // End of the test

    public function test_the_daily_report_has_one_row_per_day(): void // Scenario: per day in a month
    { // Start of the test
        $rows = $this->report(['type' => 'daily', 'year' => 2026, 'month' => 11]); // November 2026

        $this->assertCount(30, $rows); // November has 30 days
        $this->assertSame(2, $rows[2]['rentals']); // 3 Nov: two rentals
        $this->assertSame('Rp 2.000', $rows[24]['income']); // 25 Nov: the extension
    } // End of the test

    public function test_the_garage_type_report_groups_by_type(): void // Scenario: per garage type
    { // Start of the test
        $rows = collect($this->report(['type' => 'garage_type']))->keyBy('label'); // Rows by label

        $this->assertSame('Rp 14.000', $rows['Car Garage']['income']); // 5,000 + 7,000 + 2,000
        $this->assertSame('Rp 300', $rows['Bicycle Garage']['income']); // 300
        $this->assertSame(0, $rows['Electric Car Garage']['rentals']); // No electric rentals
    } // End of the test

    public function test_the_tenant_report_groups_by_tenant(): void // Scenario: per tenant
    { // Start of the test
        $rows = collect($this->report(['type' => 'tenant']))->keyBy('label'); // Rows by tenant name

        $this->assertSame([2, 1, 'Rp 14.000'], [$rows['Budi']['rentals'], $rows['Budi']['extensions'], $rows['Budi']['income']]); // Budi
        $this->assertSame([1, 0, 'Rp 300'], [$rows['Sari']['rentals'], $rows['Sari']['extensions'], $rows['Sari']['income']]); // Sari
    } // End of the test
} // End of the class
