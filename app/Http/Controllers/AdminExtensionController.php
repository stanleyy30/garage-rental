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
use Illuminate\Validation\ValidationException; // Error that sends the user back to the form
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

    public function create(Request $request, Rental $rental): View // GET /admin/extensions/{rental}/create
    { // Start of create()
        $this->ensureActive($rental); // Only running rentals

        if ($request->boolean('edit') && $request->session()->has($this->draftKey($rental))) { // Back from the confirm page with "Change details" ...
            $request->session()->flashInput($request->session()->get($this->draftKey($rental))); // ... put the values back in the form
        } // End of the edit case

        return $this->page('admin.extensions.create', [ // Show the form with ...
            'rentalId' => $rental->rental_id, // ... the rental
            'garage' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')', // ... its garage
            'tenant' => $rental->vehicle->tenant->name, // ... the tenant
            'endDate' => $this->date($rental->end_date), // ... the current end date as text
            'endDateIso' => $rental->end_date->toDateString(), // ... and as YYYY-MM-DD for the calendar
            'firstNewEndDate' => $rental->end_date->copy()->addDay()->toDateString(), // ... and the earliest new end date
        ]); // End of page()
    } // End of create()

    public function preview(AdminExtensionRequest $request, Rental $rental): RedirectResponse // POST /admin/extensions/{rental}/preview: check the new end date
    { // Start of preview()
        $this->ensureActive($rental); // Only running rentals
        $this->checkedExtension($rental, $this->extraDays($request, $rental)); // Calculation and the garage's next booking; any error goes back to the form
        $request->session()->put($this->draftKey($rental), $request->validated()); // Keep the values for the confirm page

        return redirect()->route('admin.extensions.preview.show', $rental); // Show the cost on its own page
    } // End of preview()

    public function showPreview(Request $request, Rental $rental): View|RedirectResponse // GET /admin/extensions/{rental}/preview: split, cost and payment form
    { // Start of showPreview()
        $this->ensureActive($rental); // Only running rentals
        $draft = $request->session()->get($this->draftKey($rental)); // Values from the form

        if ($draft === null) { // Nothing to confirm ...
            return redirect()->route('admin.extensions.create', $rental); // ... start again
        } // End of the empty check

        $days = ! empty($draft['new_end_date']) ? (int) $rental->end_date->diffInDays(Carbon::parse($draft['new_end_date'])) : (int) $draft['days']; // Extra days from the date, or the days typed in

        try { // Check again: the garage may have been booked in the meantime
            $extension = $this->checkedExtension($rental, $days); // Calculation and booking check
        } catch (ValidationException $error) { // A rule fails now ...
            return redirect()->route('admin.extensions.create', $rental)->withInput($draft)->withErrors($error->errors()); // ... back to the form with the reason
        } // End of the check

        return $this->page('admin.extensions.preview', [ // Show the summary and the payment form
            'rentalId' => $rental->rental_id, // The rental being extended
            'summary' => $this->extensionSummary($rental, $extension), // Label/value rows
            'days' => $extension['days'], // Sent again as a hidden field
            'backUrl' => route('admin.extensions.create', ['rental' => $rental, 'edit' => 1]), // "Change details" keeps the values
        ]); // End of page()
    } // End of showPreview()

    public function store(StoreAdminExtensionRequest $request, Rental $rental): RedirectResponse // POST /admin/extensions/{rental}
    { // Start of store()
        $this->ensureActive($rental); // Only running rentals
        $extension = $this->checkedExtension($rental, $this->extraDays($request, $rental)); // Calculate and check again

        $this->saveExtension($rental, $extension, $request->file('payment_proof'), ExtensionStatus::Accepted); // Save it; the admin took the payment in person, so it counts at once
        $request->session()->forget($this->draftKey($rental)); // Saved, so the draft is no longer needed

        return redirect()->route('admin.extensions.index') // Back to the list ...
            ->with('success', "Garage {$rental->garage_id} is extended until {$this->date($extension['newEndDate'])} for {$this->rupiah($extension['cost'])}. The tenant sees it on their dashboard."); // ... with a green message
    } // End of store()

    private function draftKey(Rental $rental): string // Session key for this rental's extension draft
    { // Start of draftKey()
        return 'admin_extension_draft_' . $rental->rental_id; // One draft per rental
    } // End of draftKey()

    private function checkedExtension(Rental $rental, int $days): array // Calculates, then makes sure no other booking uses the garage on the extra days
    { // Start of checkedExtension()
        $extension = $this->calculateExtension($rental, $days); // Any number of days, split into months + days
        $conflict = $this->bookingConflict($rental, $extension['newEndDate']); // Another tenant's booking on the extra days?

        if ($conflict !== null) { // The garage is taken on one of those days ...
            throw ValidationException::withMessages(['new_end_date' => $conflict, 'days' => $conflict]); // ... say until when it is free
        } // End of the booking check

        return $extension; // New end date, split and cost
    } // End of checkedExtension()

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
