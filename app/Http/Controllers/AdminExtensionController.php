<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\ExtensionStatus; // The ExtensionStatus enum
use App\Http\Controllers\Concerns\HandlesExtensions; // Shared extension steps
use App\Http\Requests\AdminExtensionRequest; // Validated form
use App\Http\Requests\StoreAdminExtensionRequest; // Validated form + proof
use App\Models\Rental; // The Rental model
use Carbon\Carbon; // Date library (to read the picked date)
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Http\Request; // The incoming browser request
use Illuminate\View\View; // Return type for "show a page"

class AdminExtensionController extends Controller // Extend Garage Rental: the admin enters the days the tenant asked for outside the app
{ // Start of the class
    use HandlesExtensions; // Shared extension steps and formulas

    public function index(Request $request): View // GET /admin/extensions: garages rented today, found by the renter's name
    { // Start of index()
        $tenantName = trim((string) $request->query('tenant')); // Name typed in the search box (empty = show all)

        $rentals = Rental::with(['garage', 'vehicle.tenant']) // Load garage, vehicle and tenant too ...
            ->whereDate('start_date', '<=', today()) // ... rentals that started today or earlier
            ->whereDate('end_date', '>=', today()) // ... and end today or later
            ->when($tenantName !== '', fn ($query) => $query->whereHas('vehicle.tenant', fn ($tenant) => $tenant->where('name', 'like', '%' . $tenantName . '%'))) // ... only this renter's garages when a name is typed
            ->orderBy('garage_id') // In garage order
            ->get() // Run the query
            ->sortBy(fn (Rental $rental) => $rental->vehicle->tenant->name) // Group the rows by renter name
            ->values(); // Number the rows again after sorting

        return $this->page('admin.extensions.index', [ // Show the list with one ready-made row per rental
            'tenantName' => $tenantName, // Keeps the typed name in the search box
            'rentals' => $rentals->map(fn (Rental $rental) => [ // Rows
                'id' => $rental->rental_id, // For the Extend button
                'garage' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')', // Garage
                'tenant' => $rental->vehicle->tenant->name, // Tenant name
                'vehicle' => $this->vehicleLabel($rental->vehicle->brand_model, $rental->vehicle->plate_number), // Vehicle
                'endDate' => $this->date($rental->end_date), // Current end date
            ]), // End of the rows
        ]); // End of page()
    } // End of index()

    public function create(Rental $rental): View // GET /admin/extensions/{rental}/create
    { // Start of create()
        $this->ensureActive($rental); // Only running rentals

        return $this->page('admin.extensions.create', [ // Show the form with ...
            'rentalId' => $rental->rental_id, // ... the rental
            'garage' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')', // ... its garage
            'tenant' => $rental->vehicle->tenant->name, // ... the tenant
            'endDate' => $this->date($rental->end_date), // ... the current end date
            'firstNewEndDate' => $rental->end_date->copy()->addDay()->toDateString(), // ... and the earliest new end date for the calendar
        ]); // End of page()
    } // End of create()

    public function preview(AdminExtensionRequest $request, Rental $rental): View // POST /admin/extensions/{rental}/preview
    { // Start of preview()
        $this->ensureActive($rental); // Only running rentals
        $extension = $this->calculateExtension($rental, $this->extraDays($request, $rental)); // New end date or days, split into months + days

        return $this->page('admin.extensions.preview', [ // Show the summary and the payment form
            'rentalId' => $rental->rental_id, // The rental being extended
            'summary' => $this->extensionSummary($rental, $extension), // Label/value rows
            'days' => $extension['days'], // Sent again as a hidden field
        ]); // End of page()
    } // End of preview()

    public function store(StoreAdminExtensionRequest $request, Rental $rental): RedirectResponse // POST /admin/extensions/{rental}
    { // Start of store()
        $this->ensureActive($rental); // Only running rentals
        $extension = $this->calculateExtension($rental, $this->extraDays($request, $rental)); // Calculate again

        $this->saveExtension($rental, $extension, $request->file('payment_proof'), ExtensionStatus::Accepted); // Save it; the admin took the payment in person, so it counts at once

        return redirect()->route('admin.extensions.index') // Back to the list ...
            ->with('success', "Garage {$rental->garage_id} is extended until {$this->date($extension['newEndDate'])} for {$this->rupiah($extension['cost'])}."); // ... with a green message
    } // End of store()

    private function extraDays(AdminExtensionRequest $request, Rental $rental): int // Extra days from the new end date, or the days typed in
    { // Start of extraDays()
        $newEndDate = $request->validated('new_end_date'); // Date picked on the calendar (may be empty)

        if ($newEndDate) { // The admin changed the end date ...
            return (int) $rental->end_date->diffInDays(Carbon::parse($newEndDate)); // ... so count the days between the two dates
        } // End of the date case

        return (int) $request->validated('days'); // Otherwise use the number of days typed in
    } // End of extraDays()

    private function ensureActive(Rental $rental): void // Stops the request if the rental is not running today
    { // Start of ensureActive()
        if ($rental->start_date->gt(today()) || $rental->end_date->lt(today())) { // Not rented today ...
            abort(403, 'Only garages that are rented today can be extended.'); // ... can't extend
        } // End of the check
    } // End of ensureActive()
} // End of the class
