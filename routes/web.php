<?php // Start of a PHP file

use App\Http\Controllers\AdminExtensionController; // Extend Garage Rental (admin)
use App\Http\Controllers\AuthController; // Registration, login, logout
use App\Http\Controllers\DashboardController; // Home pages
use App\Http\Controllers\ExtensionRequestController; // Tenants' extension requests (admin decides)
use App\Http\Controllers\GarageController; // Create Garages
use App\Http\Controllers\RentalController; // Rent New Garage and My Rentals
use App\Http\Controllers\ReportController; // Rental reports
use App\Http\Controllers\TenantExtensionController; // Extend Own Rental (tenant)
use App\Http\Controllers\VerificationController; // Verify New Registrant
use Illuminate\Support\Facades\Route; // Laravel's route helper

Route::redirect('/', '/dashboard'); // Opening the site goes to the dashboard (guests are sent on to /login)

Route::middleware('guest')->group(function () { // Pages for visitors who are NOT logged in
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login'); // Login page
    Route::post('/login', [AuthController::class, 'login']); // Handle the login form
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register'); // Registration page
    Route::post('/register', [AuthController::class, 'register']); // Handle the registration form
}); // End of the guest group

Route::middleware('auth')->group(function () { // Pages for logged-in users only (guests go to /login)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout'); // Log out (POST, so a link can't log you out by accident)
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard'); // Send each role to its own home page

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () { // Admin pages: /admin/..., names admin.*
        Route::get('/', [DashboardController::class, 'admin'])->name('dashboard'); // Admin home page
        Route::get('/verifications', [VerificationController::class, 'index'])->name('verifications.index'); // Tenants to verify
        Route::get('/verifications/{tenant}/ktp', [VerificationController::class, 'ktp'])->name('verifications.ktp'); // A tenant's KTP photo
        Route::patch('/verifications/{tenant}', [VerificationController::class, 'update'])->name('verifications.update'); // Accept or reject one tenant
        Route::get('/garages', [GarageController::class, 'index'])->name('garages.index'); // Garage list and form
        Route::post('/garages', [GarageController::class, 'store'])->name('garages.store'); // Create a batch of garages
        Route::get('/extensions', [AdminExtensionController::class, 'index'])->name('extensions.index'); // Garages rented today
        Route::get('/extensions/{rental}/create', [AdminExtensionController::class, 'create'])->name('extensions.create'); // Days form
        Route::post('/extensions/{rental}/preview', [AdminExtensionController::class, 'preview'])->name('extensions.preview'); // Split and cost
        Route::post('/extensions/{rental}', [AdminExtensionController::class, 'store'])->name('extensions.store'); // Upload proof and save
        Route::get('/extension-requests', [ExtensionRequestController::class, 'index'])->name('extension-requests.index'); // Requests to accept or reject
        Route::get('/extension-requests/{extension}/proof', [ExtensionRequestController::class, 'proof'])->name('extension-requests.proof'); // The tenant's payment proof
        Route::post('/extension-requests/{extension}/accept', [ExtensionRequestController::class, 'accept'])->name('extension-requests.accept'); // Accept with payment evidence
        Route::post('/extension-requests/{extension}/reject', [ExtensionRequestController::class, 'reject'])->name('extension-requests.reject'); // Reject
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index'); // The four reports
    }); // End of the admin group

    Route::middleware('role:tenant')->prefix('tenant')->name('tenant.')->group(function () { // Tenant pages: /tenant/..., names tenant.*
        Route::get('/', [DashboardController::class, 'tenant'])->name('dashboard'); // Tenant home page

        Route::middleware('verified_tenant')->group(function () { // Only tenants accepted by the admin
            Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index'); // My Rentals
            Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create'); // Rent form (step 1 and 2)
            Route::post('/rentals/preview', [RentalController::class, 'preview'])->name('rentals.preview'); // Check and show the total
            Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store'); // Confirm, upload proof and save
            Route::get('/rentals/{rental}/extend', [TenantExtensionController::class, 'create'])->name('extensions.create'); // Extension form
            Route::post('/rentals/{rental}/extend/preview', [TenantExtensionController::class, 'preview'])->name('extensions.preview'); // Check limits, show cost
            Route::post('/rentals/{rental}/extend', [TenantExtensionController::class, 'store'])->name('extensions.store'); // Upload proof and save
        }); // End of the verified group
    }); // End of the tenant group
}); // End of the auth group
