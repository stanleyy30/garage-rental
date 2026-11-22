<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\VerificationStatus; // The VerificationStatus enum
use App\Http\Requests\VerifyTenantRequest; // Validated Accept / Reject form
use App\Models\Tenant; // The Tenant model
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Support\Facades\Storage; // File storage (where KTP photos are kept)
use Symfony\Component\HttpFoundation\StreamedResponse; // Return type for "send a file"
use Illuminate\View\View; // Return type for "show a page"

class VerificationController extends Controller // Verify New Registrant (admin only)
{ // Start of the class
    public function index(): View // GET /admin/verifications
    { // Start of index()
        $pending = Tenant::where('verification_status', VerificationStatus::Pending) // Tenants still waiting ...
            ->orderBy('created_at') // ... oldest registration first
            ->get(); // Run the query

        $processed = Tenant::where('verification_status', '!=', VerificationStatus::Pending) // Tenants already decided ...
            ->orderByDesc('verified_at') // ... newest decision first
            ->get(); // Run the query

        return $this->page('admin.verifications', [ // Show the page with two ready-made lists
            'pendingTenants' => $pending->map(fn (Tenant $tenant) => [ // One row per waiting tenant
                'id' => $tenant->user_id, // For the Accept / Reject form
                'nik' => $tenant->nik, // NIK to compare with the KTP
                'ktpUrl' => $tenant->ktp_photo ? route('admin.verifications.ktp', $tenant) : null, // Link to the KTP photo, or null if none was uploaded
                'name' => $tenant->name, // Full name
                'phone' => $tenant->phone_number, // Phone
                'email' => $tenant->email, // Email
                'registeredAt' => $tenant->created_at->format('d M Y H:i'), // Registration date and time
            ]), // End of the pending rows
            'processedTenants' => $processed->map(fn (Tenant $tenant) => [ // One row per decided tenant
                'nik' => $tenant->nik, // NIK
                'name' => $tenant->name, // Full name
                'email' => $tenant->email, // Email
                'status' => $tenant->verification_status->label(), // "Accepted" or "Rejected"
                'statusColor' => $tenant->verification_status === VerificationStatus::Accepted ? 'success' : 'danger', // Green or red badge
                'verifiedAt' => $tenant->verified_at?->format('d M Y H:i') ?? '-', // Decision date and time
            ]), // End of the processed rows
        ]); // End of page()
    } // End of index()

    public function ktp(Tenant $tenant): StreamedResponse // GET /admin/verifications/{tenant}/ktp: show the KTP photo (admin only)
    { // Start of ktp()
        if (! $tenant->ktp_photo || ! Storage::disk('local')->exists($tenant->ktp_photo)) { // No photo saved ...
            abort(404); // ... nothing to show
        } // End of the check

        return Storage::disk('local')->response($tenant->ktp_photo); // Send the image from the private folder
    } // End of ktp()

    public function update(VerifyTenantRequest $request, Tenant $tenant): RedirectResponse // PATCH /admin/verifications/{tenant}
    { // Start of update()
        if ($tenant->verification_status !== VerificationStatus::Pending) { // Already decided ...
            return back()->withErrors(['decision' => 'This tenant has already been verified.']); // ... so do nothing
        } // End of the already-decided check

        $newStatus = $request->validated('decision') === 'accept' ? VerificationStatus::Accepted : VerificationStatus::Rejected; // Button -> status

        $tenant->update([ // Save the decision
            'verification_status' => $newStatus, // Accepted or Rejected
            'verified_at' => now(), // When it was decided
        ]); // End of update()

        return redirect()->route('admin.verifications.index') // Back to the list ...
            ->with('success', "{$tenant->name} has been " . strtolower($newStatus->label()) . '.'); // ... e.g. "Budi has been accepted."
    } // End of update()
} // End of the class
