<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\GarageType; // The GarageType enum
use App\Http\Requests\ReportRequest; // Validated filter form
use App\Models\Extension; // The Extension model
use App\Models\Rental; // The Rental model
use Carbon\Carbon; // Date library used by Laravel
use Illuminate\Support\Collection; // Laravel's list type
use Illuminate\View\View; // Return type for "show a page"

class ReportController extends Controller // Rental reports (admin only)
{ // Start of the class
    public function index(ReportRequest $request): View // GET /admin/reports?type=...&year=...&month=...
    { // Start of index()
        $type = $request->validated('type') ?? 'monthly'; // Default: per month
        $year = (int) ($request->validated('year') ?? today()->year); // Default: this year
        $month = (int) ($request->validated('month') ?? today()->month); // Default: this month

        [$title, $rows] = match ($type) { // Build the chosen report
            'monthly' => ["Per month in {$year}", $this->monthlyRows($year)], // 12 months
            'daily' => ['Per day in ' . Carbon::create($year, $month)->format('F Y'), $this->dailyRows($year, $month)], // Days of one month
            'garage_type' => ['Per garage type (all time)', $this->garageTypeRows()], // 3 garage types
            'tenant' => ['Per tenant (all time)', $this->tenantRows()], // Every tenant with a rental
        }; // End of match

        return $this->page('admin.reports', [ // Show the page with ...
            'types' => ReportRequest::TYPES, // ... the report dropdown
            'type' => $type, // ... the chosen report
            'year' => $year, // ... the chosen year
            'month' => $month, // ... the chosen month
            'months' => $this->monthOptions(), // ... the month dropdown
            'title' => $title, // ... the table title
            'rows' => $this->formatRows($rows), // ... the rows, with money already formatted
            'total' => $this->formatTotal($rows), // ... and the total line
        ]); // End of page()
    } // End of index()

    private function monthlyRows(int $year): array // One row per month of a year
    { // Start of monthlyRows()
        $buckets = []; // Rows to fill: key => label
        for ($m = 1; $m <= 12; $m++) { // January to December
            $buckets[$m] = Carbon::create($year, $m)->format('F'); // 1 => "January"
        } // End of the loop

        $rentals = Rental::whereYear('start_date', $year)->get(); // Rentals that started in this year
        $extensions = Extension::whereYear('created_at', $year)->get(); // Extensions paid in this year

        return $this->fillRows($buckets, $rentals, $extensions, fn (Rental $r) => $r->start_date->month, fn (Extension $e) => $e->created_at->month); // Group by month
    } // End of monthlyRows()

    private function dailyRows(int $year, int $month): array // One row per day of a month
    { // Start of dailyRows()
        $buckets = []; // Rows to fill: key => label
        $daysInMonth = Carbon::create($year, $month)->daysInMonth; // 28 to 31
        for ($d = 1; $d <= $daysInMonth; $d++) { // Every day of the month
            $buckets[$d] = Carbon::create($year, $month, $d)->format('d M Y'); // 1 => "01 Nov 2026"
        } // End of the loop

        $rentals = Rental::whereYear('start_date', $year)->whereMonth('start_date', $month)->get(); // Rentals that started in this month
        $extensions = Extension::whereYear('created_at', $year)->whereMonth('created_at', $month)->get(); // Extensions paid in this month

        return $this->fillRows($buckets, $rentals, $extensions, fn (Rental $r) => $r->start_date->day, fn (Extension $e) => $e->created_at->day); // Group by day
    } // End of dailyRows()

    private function garageTypeRows(): array // One row per garage type
    { // Start of garageTypeRows()
        $buckets = []; // Rows to fill: key => label
        foreach (GarageType::cases() as $garageType) { // Every garage type
            $buckets[$garageType->value] = $garageType->label(); // 'car' => 'Car Garage'
        } // End of the loop

        $rentals = Rental::with('garage')->get(); // All rentals with their garage
        $extensions = Extension::with('rental.garage')->get(); // All extensions with their rental's garage

        return $this->fillRows($buckets, $rentals, $extensions, fn (Rental $r) => $r->garage->type->value, fn (Extension $e) => $e->rental->garage->type->value); // Group by garage type
    } // End of garageTypeRows()

    private function tenantRows(): array // One row per tenant who has rented
    { // Start of tenantRows()
        $rentals = Rental::with('vehicle.tenant')->get(); // All rentals with their tenant
        $extensions = Extension::with('rental.vehicle.tenant')->get(); // All extensions with their tenant

        $buckets = $rentals // Rows to fill: one per tenant ...
            ->mapWithKeys(fn (Rental $r) => [$r->vehicle->user_id => $r->vehicle->tenant->name]) // ... user_id => name
            ->sort() // ... in name order
            ->all(); // ... as a plain array

        return $this->fillRows($buckets, $rentals, $extensions, fn (Rental $r) => $r->vehicle->user_id, fn (Extension $e) => $e->rental->vehicle->user_id); // Group by tenant
    } // End of tenantRows()

    private function fillRows(array $buckets, Collection $rentals, Collection $extensions, callable $rentalKey, callable $extensionKey): array // Shared counting for all four reports
    { // Start of fillRows()
        $rows = []; // Start every row at zero
        foreach ($buckets as $key => $label) { // One row per bucket
            $rows[$key] = ['label' => $label, 'rentals' => 0, 'extensions' => 0, 'income' => 0.0]; // Empty row
        } // End of the loop

        foreach ($rentals as $rental) { // Every rental ...
            $key = $rentalKey($rental); // ... belongs to one row
            $rows[$key]['rentals']++; // Count it
            $rows[$key]['income'] += $rental->total_cost; // Add what was paid
        } // End of the rentals

        foreach ($extensions as $extension) { // Every extension ...
            $key = $extensionKey($extension); // ... belongs to one row
            if (isset($rows[$key])) { // Skip if its tenant has no row (cannot normally happen)
                $rows[$key]['extensions']++; // Count it
                $rows[$key]['income'] += $extension->extra_cost; // Add what was paid
            } // End of the check
        } // End of the extensions

        return array_values($rows); // Rows in bucket order
    } // End of fillRows()

    private function formatRows(array $rows): array // Turn the money into "Rp ..." text for the view
    { // Start of formatRows()
        return array_map(fn (array $row) => ['income' => $this->rupiah($row['income'])] + $row, $rows); // Replace the number by text
    } // End of formatRows()

    private function formatTotal(array $rows): array // The total line under the table
    { // Start of formatTotal()
        return [ // Sums of every column
            'rentals' => array_sum(array_column($rows, 'rentals')), // All rentals
            'extensions' => array_sum(array_column($rows, 'extensions')), // All extensions
            'income' => $this->rupiah(array_sum(array_column($rows, 'income'))), // All income
        ]; // End of the total
    } // End of formatTotal()

    private function monthOptions(): array // Dropdown options: 1 => "January", ...
    { // Start of monthOptions()
        $options = []; // Empty list to fill
        for ($m = 1; $m <= 12; $m++) { // Every month
            $options[$m] = Carbon::create(2000, $m)->format('F'); // Month name
        } // End of the loop

        return $options; // The finished list
    } // End of monthOptions()
} // End of the class
