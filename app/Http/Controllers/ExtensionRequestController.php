<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\ExtensionStatus; // The ExtensionStatus enum
use App\Http\Controllers\Concerns\HandlesExtensions; // Shared extension steps (the booking check)
use App\Http\Requests\AcceptExtensionRequest; // Validated Accept form with the payment evidence
use App\Models\Extension; // The Extension model
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Support\Facades\DB; // Database helper (for a transaction)
use Illuminate\Support\Facades\Storage; // File storage (payment proofs)
use Illuminate\View\View; // Return type for "show a page"
use Symfony\Component\HttpFoundation\StreamedResponse; // Return type for "send a file"

class ExtensionRequestController extends Controller // Extension requests from tenants, accepted or rejected by the admin
{ // Start of the class
    use HandlesExtensions; // Gives this class bookingConflict()

    public function index(): View // GET /admin/extension-requests
    { // Start of index()
        $pending = Extension::with(['rental.garage', 'rental.vehicle.tenant']) // Load rental, garage, vehicle and tenant too ...
            ->where('status', ExtensionStatus::Pending) // ... only requests that wait
            ->orderBy('created_at') // ... oldest first
            ->get(); // Run the query

        $decided = Extension::with(['rental.garage', 'rental.vehicle.tenant']) // Same data ...
            ->whereNotNull('decided_at') // ... for requests already decided
            ->orderByDesc('decided_at') // ... newest decision first
            ->limit(10) // ... the last ten
            ->get(); // Run the query

        return $this->page('admin.extension-requests', [ // Show the page with ready-made rows
            'pendingRequests' => $pending->map(fn (Extension $e) => $this->row($e)), // Waiting requests
            'decidedRequests' => $decided->map(fn (Extension $e) => $this->row($e) + [ // Decided requests, plus ...
                'status' => $e->status->label(), // ... Accepted / Rejected
                'statusColor' => $e->status === ExtensionStatus::Accepted ? 'success' : 'danger', // ... green or red
                'decidedAt' => $e->decided_at->format('d M Y H:i'), // ... when it was decided
            ]), // End of the decided rows
        ]); // End of page()
    } // End of index()

    public function proof(Extension $extension): StreamedResponse // GET /admin/extension-requests/{extension}/proof: the tenant's payment proof
    { // Start of proof()
        if (! Storage::disk('public')->exists($extension->payment_proof)) { // File missing ...
            abort(404); // ... nothing to show
        } // End of the check

        return Storage::disk('public')->response($extension->payment_proof); // Send the image
    } // End of proof()

    public function accept(AcceptExtensionRequest $request, Extension $extension): RedirectResponse // POST /admin/extension-requests/{extension}/accept
    { // Start of accept()
        $rental = $extension->rental; // The rental this request extends
        if ($extension->status !== ExtensionStatus::Pending) { // Already decided ...
            return back()->withErrors(['payment_proof' => 'This request has already been decided.']); // ... do nothing
        } // End of the decided check

        $conflict = $this->bookingConflict($rental, $extension->new_end_date); // Another tenant booked the garage on the extra days meanwhile?
        if ($conflict !== null) { // Taken ...
            return back()->withInput()->withErrors(['payment_proof' => $conflict . ' Reject this request and ask the tenant for a shorter one.']); // ... can't accept
        } // End of the booking check
        if (! $rental->end_date->equalTo($extension->old_end_date)) { // The rental's end date changed since the request ...
            return back()->withInput()->withErrors(['payment_proof' => 'The rental was changed after this request. Reject it and ask the tenant to request again.']); // ... the price would be wrong
        } // End of the changed check

        $evidencePath = $request->file('payment_proof')->store('payment-proofs', 'public'); // Save the admin's payment evidence

        DB::transaction(function () use ($extension, $rental, $evidencePath) { // Both rows change together
            $extension->update([ // Mark the request as accepted
                'status' => ExtensionStatus::Accepted, // Accepted
                'admin_payment_proof' => $evidencePath, // The admin's payment evidence
                'decided_at' => now(), // When it was accepted
            ]); // End of update()
            $rental->update(['end_date' => $extension->new_end_date]); // Now the rental ends later
        }); // End of the transaction

        return redirect()->route('admin.extension-requests.index') // Back to the list ...
            ->with('success', "Extension of garage {$rental->garage_id} until {$this->date($extension->new_end_date)} is accepted."); // ... with a green message
    } // End of accept()

    public function reject(Extension $extension): RedirectResponse // POST /admin/extension-requests/{extension}/reject
    { // Start of reject()
        if ($extension->status !== ExtensionStatus::Pending) { // Already decided ...
            return back()->withErrors(['payment_proof' => 'This request has already been decided.']); // ... do nothing
        } // End of the check

        $extension->update(['status' => ExtensionStatus::Rejected, 'decided_at' => now()]); // Rejected; the rental's end date stays the same

        return redirect()->route('admin.extension-requests.index') // Back to the list ...
            ->with('success', "Extension request for garage {$extension->rental->garage_id} is rejected."); // ... with a green message
    } // End of reject()

    private function row(Extension $extension): array // One ready-made table row
    { // Start of row()
        $rental = $extension->rental; // The rental being extended

        return [ // Values for the view
            'id' => $extension->extension_id, // For the buttons and the proof link
            'garage' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')', // Garage
            'tenant' => $rental->vehicle->tenant->name, // Tenant
            'vehicle' => $this->vehicleLabel($rental->vehicle->brand_model, $rental->vehicle->plate_number), // Vehicle
            'period' => $this->date($extension->old_end_date) . ' to ' . $this->date($extension->new_end_date), // Old and new end date
            'days' => $extension->extra_days . ' days', // Extra days
            'cost' => $this->rupiah($extension->extra_cost), // Amount to pay
            'proofUrl' => route('admin.extension-requests.proof', $extension), // The tenant's payment proof
        ]; // End of the row
    } // End of row()
} // End of the class
