<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\ExtensionStatus; // The ExtensionStatus enum
use App\Enums\RentalType; // The RentalType enum
use App\Http\Controllers\Concerns\HandlesExtensions; // Shared extension steps
use App\Http\Requests\StoreTenantExtensionRequest; // Validated form + proof
use App\Http\Requests\TenantExtensionRequest; // Validated form
use App\Models\Rental; // The Rental model
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Http\Request; // The incoming browser request
use Illuminate\Validation\ValidationException; // Error that sends the user back to the form
use Illuminate\View\View; // Return type for "show a page"

class TenantExtensionController extends Controller // Extend Own Rental: the tenant asks for more days in the same garage, within the booking limits
{ // Start of the class
    use HandlesExtensions; // Shared extension steps and formulas

    public function create(Request $request, Rental $rental): View // GET /tenant/rentals/{rental}/extend
    { // Start of create()
        $this->ensureOwnActiveRental($request, $rental); // Only the tenant's own running rental

        if ($request->boolean('edit') && $request->session()->has($this->draftKey($rental))) { // Back from the confirm page with "Change details" ...
            $request->session()->flashInput($request->session()->get($this->draftKey($rental))); // ... put the choices back in the form
        } // End of the edit case

        return $this->page('tenant.extensions.create', [ // Show the form with ...
            'rentalId' => $rental->rental_id, // ... the rental to extend
            'garage' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')', // ... its garage
            'endDate' => $this->date($rental->end_date), // ... its current end date as text
            'endDateIso' => $rental->end_date->toDateString(), // ... and as YYYY-MM-DD for the calendar
            'maxDailyDays' => $this->maxDailyDays($rental->end_date), // ... the daily limit counted from the end date
            'monthlyDays' => $this->monthlyDays($rental->end_date), // ... and the length of a monthly extension
        ]); // End of page()
    } // End of create()

    public function preview(TenantExtensionRequest $request, Rental $rental): RedirectResponse // POST /tenant/rentals/{rental}/extend/preview: check the choice
    { // Start of preview()
        $this->ensureOwnActiveRental($request, $rental); // Only the tenant's own running rental
        $this->checkedExtension($rental, $request->validated()); // Booking limits + calculation; any error goes back to the form
        $request->session()->put($this->draftKey($rental), $request->validated()); // Keep the choice for the confirm page

        return redirect()->route('tenant.extensions.preview.show', $rental); // Show the cost on its own page
    } // End of preview()

    public function showPreview(Request $request, Rental $rental): View|RedirectResponse // GET /tenant/rentals/{rental}/extend/preview: cost and payment form
    { // Start of showPreview()
        $this->ensureOwnActiveRental($request, $rental); // Only the tenant's own running rental
        $draft = $request->session()->get($this->draftKey($rental)); // The choice from the form

        if ($draft === null) { // Nothing to confirm ...
            return redirect()->route('tenant.extensions.create', $rental); // ... start again
        } // End of the empty check

        try { // Check again: the garage may have been booked in the meantime
            $extension = $this->checkedExtension($rental, $draft); // Limits + calculation
        } catch (ValidationException $error) { // A rule fails now ...
            return redirect()->route('tenant.extensions.create', $rental)->withInput($draft)->withErrors($error->errors()); // ... back to the form with the reason
        } // End of the check

        return $this->page('tenant.extensions.preview', [ // Show the summary and the payment form
            'rentalId' => $rental->rental_id, // The rental being extended
            'summary' => $this->extensionSummary($rental, $extension), // Label/value rows
            'formData' => $draft, // Sent again as hidden fields on confirm
            'backUrl' => route('tenant.extensions.create', ['rental' => $rental, 'edit' => 1]), // "Change details" keeps the choice
        ]); // End of page()
    } // End of showPreview()

    public function store(StoreTenantExtensionRequest $request, Rental $rental): RedirectResponse // POST /tenant/rentals/{rental}/extend
    { // Start of store()
        $this->ensureOwnActiveRental($request, $rental); // Only the tenant's own running rental
        $extension = $this->checkedExtension($rental, $request->validated()); // Check and calculate again

        $this->saveExtension($rental, $extension, $request->file('payment_proof'), ExtensionStatus::Pending); // Save it as a request; the admin must accept it
        $request->session()->forget($this->draftKey($rental)); // The choice is saved, so the draft is no longer needed

        return redirect()->route('tenant.rentals.index') // Back to My Rentals ...
            ->with('success', "Extension of garage {$rental->garage_id} until {$this->date($extension['newEndDate'])} is sent. It counts once the admin accepts it."); // ... with a green message
    } // End of store()

    private function draftKey(Rental $rental): string // Session key for this rental's extension draft
    { // Start of draftKey()
        return 'tenant_extension_draft_' . $rental->rental_id; // One draft per rental
    } // End of draftKey()

    private function ensureOwnActiveRental(Request $request, Rental $rental): void // Stops the request if the rental isn't this tenant's or has ended
    { // Start of ensureOwnActiveRental()
        if ($rental->vehicle->user_id !== $request->user()->user_id) { // Someone else's rental ...
            abort(404); // ... act as if it doesn't exist
        } // End of the owner check

        if ($rental->end_date->lt(today())) { // Already ended ...
            abort(403, 'This rental has ended and cannot be extended.'); // ... can't extend
        } // End of the active check

        if ($rental->extensions()->where('status', ExtensionStatus::Pending)->exists()) { // A request is already waiting ...
            abort(403, 'An extension of this rental is already waiting for the admin.'); // ... one request at a time
        } // End of the pending check
    } // End of ensureOwnActiveRental()

    private function checkedExtension(Rental $rental, array $data): array // Applies the booking limits and the garage's next booking, then calculates
    { // Start of checkedExtension()
        $type = RentalType::from($data['rental_type']); // Daily or monthly
        $days = $type === RentalType::Monthly ? $this->monthlyDays($rental->end_date) : (int) $data['days']; // Monthly = days of the month the extension starts in

        if (! $this->isValidDays($type, $days, $rental->end_date)) { // Over the daily limit ...
            throw ValidationException::withMessages(['days' => "A daily extension from {$this->date($rental->end_date)} can be 1 to {$this->maxDailyDays($rental->end_date)} days. For more, ask the admin."]); // ... back to the form
        } // End of the limit check

        $extension = $this->calculateExtension($rental, $days); // New end date, split and cost
        $conflict = $this->bookingConflict($rental, $extension['newEndDate']); // Another tenant's booking on the extra days?

        if ($conflict !== null) { // The garage is taken on one of those days ...
            throw ValidationException::withMessages([$type === RentalType::Monthly ? 'rental_type' : 'days' => $conflict]); // ... say until when it is free
        } // End of the booking check

        return $extension; // New end date, split and cost
    } // End of checkedExtension()
} // End of the class
