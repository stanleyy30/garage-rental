<?php // Start of a PHP file

use App\Http\Controllers\AuthController; // The controller for login, logout and dashboard
use Illuminate\Support\Facades\Route; // Laravel's route helper

Route::redirect('/', '/dashboard'); // Opening the site goes to the dashboard (guests are sent on to /login)

Route::middleware('guest')->group(function () { // Pages for visitors who are NOT logged in
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login'); // Show the login page
    Route::post('/login', [AuthController::class, 'login']); // Handle the login form
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register'); // Show the registration page
    Route::post('/register', [AuthController::class, 'register']); // Handle the registration form
}); // End of the guest group

Route::middleware('auth')->group(function () { // Pages for logged-in users only (guests go to /login)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout'); // Log out (POST, so a link can't log you out by accident)
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard'); // Send each role to its own home page

    Route::middleware('role:admin')->group(function () { // Admin-only pages
        Route::view('/admin', 'admin.dashboard')->name('admin.dashboard'); // Admin home page
    }); // End of the admin group

    Route::middleware('role:tenant')->group(function () { // Tenant-only pages
        Route::view('/tenant', 'tenant.dashboard')->name('tenant.dashboard'); // Tenant home page
    }); // End of the tenant group
}); // End of the auth group