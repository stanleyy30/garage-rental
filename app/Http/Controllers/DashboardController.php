<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\GarageStatus; // The GarageStatus enum
use App\Enums\VerificationStatus; // The VerificationStatus enum
use App\Http\Controllers\Concerns\CalculatesRentals; // Shared rules
use App\Models\Garage; // The Garage model
use App\Models\Rental; // The Rental model
use App\Models\Tenant; // The Tenant model
use Illuminate\Http\Request; // The incoming browser request
use Illuminate\View\View; // Return type for "show a page"

class DashboardController extends Controller // Home pages for the admin and the tenant
{ // Start of the class
    use CalculatesRentals; // Gives this class refreshGarageStatuses()

    public function admin(): View // GET /admin
    { // Start of admin()
        $this->refreshGarageStatuses(); // Make garage statuses match today's rentals first

        return $this->page('admin.dashboard', [ // Show the admin home page with these numbers
            'stats' => [ // One card per number
                ['label' => 'Registrants waiting', 'value' => Tenant::where('verification_status', VerificationStatus::Pending)->count()], // Pending tenants
                ['label' => 'Garages', 'value' => Garage::count()], // All garages
                ['label' => 'Available garages', 'value' => Garage::where('status', GarageStatus::Available)->count()], // Free garages
                ['label' => 'Rented today', 'value' => Rental::whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->count()], // Active rentals
            ], // End of the cards
        ]); // End of page()
    } // End of admin()

    public function tenant(Request $request): View // GET /tenant
    { // Start of tenant()
        $status = $request->user()->verification_status; // This tenant's registration status

        return $this->page('tenant.dashboard', [ // Show the tenant home page with ...
            'statusColor' => $this->statusColor($status), // ... the alert colour
            'statusMessage' => $this->statusMessage($status), // ... and the message for this status
            'canRent' => $status === VerificationStatus::Accepted, // Only accepted tenants see the rent button
        ]); // End of page()
    } // End of tenant()

    private function statusColor(VerificationStatus $status): string // Bootstrap colour for a status
    { // Start of statusColor()
        return match ($status) { // Pick by status
            VerificationStatus::Pending => 'warning', // Yellow
            VerificationStatus::Accepted => 'success', // Green
            VerificationStatus::Rejected => 'danger', // Red
        }; // End of match
    } // End of statusColor()

    private function statusMessage(VerificationStatus $status): string // Message for a status
    { // Start of statusMessage()
        return match ($status) { // Pick by status
            VerificationStatus::Pending => 'Your registration is waiting for admin verification.', // Still waiting
            VerificationStatus::Accepted => 'Your account is verified. You can rent a garage.', // Accepted
            VerificationStatus::Rejected => "Your registration was rejected. You can't rent a garage.", // Rejected
        }; // End of match
    } // End of statusMessage()
} // End of the class
