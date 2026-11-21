<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\RentalType; // The RentalType enum
use App\Http\Controllers\Concerns\HandlesExtensions; // Shared extension steps
use App\Http\Requests\StoreTenantExtensionRequest; // Validated form + proof
use App\Http\Requests\TenantExtensionRequest; // Validated form
use App\Models\Rental; // The Rental model
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Http\Request; // The incoming browser request
use Illuminate\Validation\ValidationException; // Error that sends the user back to the form
use Illuminate\View\View; // Return type for "show a page"

class TenantExtensionController extends Controller // Extend Own Rental: the tenant extends in the app with booking limits
{ // Start of the class
    use HandlesExtensions; // Shared extension steps and formulas

    public function create(Request $request, Rental $rental): View // GET /tenant/rentals/{rental}/extend
    { // Start of create()
        $this->ensureOwnActiveRental($request, $rental); // Only the tenant's own running rental

        return $this->page('tenant.extensions.create', [ // Show the form with ...
            'rentalId' => $rental->rental_id, // ... the rental to extend
            'garage' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')', // ... its garage
            'endDate' => $this->date($rental->end_date), // ... its current end date
            'maxDailyDays' => $this->maxDailyDays($rental->end_date), // ... the daily limit counted from the end date
            'monthlyDays' => $this->monthlyDays($rental->end_date), // ... and the length of a monthly extension
        ]); // End of page()
    } // End of create()

    public function preview(TenantExtensionRequest $request, Rental $rental): View // POST /tenant/rentals/{rental}/extend/preview
    { // Start of preview()
        $this->ensureOwnActiveRental($request, $rental); // Only the tenant's own running rental
        $extension = $this->checkedExtension($rental, $request->validated()); // Booking limits + calculation

        return $this->page('tenant.extensions.preview', [ // Show the summary and the payment form
            'rentalId' => $rental->rental_id, // The rental being extended
            'summary' => $this->extensionSummary($rental, $extension), // Label/value rows
            'formData' => $request->validated(), // Sent again as hidden fields on confirm
        ]); // End of page()
    } // End of preview()

    public function store(StoreTenantExtensionRequest $request, Rental $rental): RedirectResponse // POST /tenant/rentals/{rental}/extend
    { // Start of store()
        $this->ensureOwnActiveRental($request, $rental); // Only the tenant's own running rental
        $extension = $this->checkedExtension($rental, $request->validated()); // Check and calculate again

        $this->saveExtension($rental, $extension, $request->file('payment_proof')); // Save it

        return redirect()->route('tenant.rentals.index') // Back to My Rentals ...
            ->with('success', "Garage {$rental->garage_id} is extended until {$this->date($extension['newEndDate'])}."); // ... with a green message
    } // End of store()

    private function ensureOwnActiveRental(Request $request, Rental $rental): void // Stops the request if the rental isn't this tenant's or has ended
    { // Start of ensureOwnActiveRental()
        if ($rental->vehicle->user_id !== $request->user()->user_id) { // Someone else's rental ...
            abort(404); // ... act as if it doesn't exist
        } // End of the owner check
        if ($rental->end_date->lt(today())) { // Already ended ...
            abort(403, 'This rental has ended and cannot be extended.'); // ... can't extend
        } // End of the active check
    } // End of ensureOwnActiveRental()

    private function checkedExtension(Rental $rental, array $data): array // Applies the booking limits, then calculates
    { // Start of checkedExtension()
        $type = RentalType::from($data['rental_type']); // Daily or monthly
        $days = $type === RentalType::Monthly ? $this->monthlyDays($rental->end_date) : (int) $data['days']; // Monthly = days of the month the extension starts in

        if (! $this->isValidDays($type, $days, $rental->end_date)) { // Over the daily limit ...
            throw ValidationException::withMessages(['days' => "A daily extension from {$this->date($rental->end_date)} can be 1 to {$this->maxDailyDays($rental->end_date)} days. For more, ask the admin."]); // ... back to the form
        } // End of the limit check

        return $this->calculateExtension($rental, $days); // New end date, split and cost
    } // End of checkedExtension()
} // End of the class
