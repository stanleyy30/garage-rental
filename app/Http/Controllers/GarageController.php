<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\GarageStatus; // The GarageStatus enum
use App\Enums\GarageType; // The GarageType enum
use App\Http\Controllers\Concerns\CalculatesRentals; // Shared rules
use App\Http\Requests\StoreGaragesRequest; // Validated Create Garages form
use App\Models\Garage; // The Garage model
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Support\Facades\DB; // Database helper (for a transaction)
use Illuminate\View\View; // Return type for "show a page"

class GarageController extends Controller // Create Garages (admin only)
{ // Start of the class
    use CalculatesRentals; // Gives this class the ID and price rules

    public function index(): View // GET /admin/garages: the form and the list of garages
    { // Start of index()
        $this->refreshGarageStatuses(); // Make statuses match today's rentals first

        $garages = Garage::orderBy('type')->orderBy('garage_id')->get(); // All garages, grouped by type

        return $this->page('admin.garages', [ // Show the page with ...
            'garageTypes' => $this->garageTypeOptions(), // ... the type dropdown
            'remaining' => self::MAX_TOTAL_GARAGES - $garages->count(), // ... how many more garages may be created
            'garages' => $garages->map(fn (Garage $garage) => [ // ... and one row per garage
                'id' => $garage->garage_id, // e.g. C010
                'type' => $garage->type->label(), // e.g. Car Garage
                'dailyPrice' => $this->rupiah($garage->daily_price), // Price per day
                'monthlyPrice' => $this->rupiah(self::MONTHLY_MULTIPLIER * $garage->daily_price), // 25 x daily price
                'kwhPrice' => $garage->electricity_price_per_kwh ? $this->rupiah($garage->electricity_price_per_kwh) : '-', // Only for E garages
                'status' => $garage->status->label(), // Available / Used
                'statusColor' => $garage->status === GarageStatus::Available ? 'success' : 'secondary', // Green or grey badge
            ]), // End of the rows
        ]); // End of page()
    } // End of index()

    public function store(StoreGaragesRequest $request): RedirectResponse // POST /admin/garages: create a batch of garages
    { // Start of store()
        $type = GarageType::from($request->validated('type')); // The chosen garage type
        $quantity = (int) $request->validated('quantity'); // How many to create
        $remaining = self::MAX_TOTAL_GARAGES - Garage::count(); // Free places under the 999 limit

        if ($quantity > $remaining) { // Too many ...
            return back()->withInput()->withErrors(['quantity' => "Only {$remaining} more garages can be created (maximum 999 in total)."]); // ... refuse
        } // End of the limit check

        $lastNumber = Garage::where('type', $type) // Garages of this type ...
            ->pluck('garage_id') // ... only their IDs ('C001', 'C002', ...)
            ->map(fn (string $id) => (int) substr($id, 1)) // ... without the letter (1, 2, ...)
            ->max() ?? 0; // The highest number, or 0 if there are none yet

        $pricePerKwh = $type === GarageType::ElectricCar ? $request->validated('electricity_price_per_kwh') : null; // kWh price only for E garages

        DB::transaction(function () use ($type, $quantity, $lastNumber, $request, $pricePerKwh) { // All garages are saved, or none if something fails
            for ($i = 1; $i <= $quantity; $i++) { // Once per garage
                Garage::create([ // Insert one row
                    'garage_id' => $this->generateGarageId($type, $lastNumber + $i), // Next ID, e.g. C011
                    'type' => $type, // Garage type
                    'daily_price' => $request->validated('daily_price'), // Price per day
                    'electricity_price_per_kwh' => $pricePerKwh, // kWh price or empty
                    'status' => GarageStatus::Available, // New garages are free
                ]); // End of create()
            } // End of the loop
        }); // End of the transaction

        $firstId = $this->generateGarageId($type, $lastNumber + 1); // First new ID
        $lastId = $this->generateGarageId($type, $lastNumber + $quantity); // Last new ID

        return redirect()->route('admin.garages.index') // Back to the list ...
            ->with('success', "{$quantity} garages created: {$firstId} to {$lastId}."); // ... e.g. "3 garages created: C001 to C003."
    } // End of store()

    private function garageTypeOptions(): array // Dropdown options: value => label
    { // Start of garageTypeOptions()
        $options = []; // Empty list to fill
        foreach (GarageType::cases() as $type) { // Every garage type
            $options[$type->value] = $type->label(); // 'car' => 'Car Garage'
        } // End of the loop

        return $options; // The finished list
    } // End of garageTypeOptions()
} // End of the class
