<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\Role; // The Role enum
use App\Models\Tenant; // The Tenant model (used by registration)
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Http\Request; // The incoming browser request
use Illuminate\Support\Facades\Auth; // Laravel's login helper
use Illuminate\View\View; // Return type for "show a page"

class AuthController extends Controller // Handles registration, login, logout and the home page after login
{ // Start of the class
    public function showRegisterForm(): View // GET /register: show the registration page
    { // Start of showRegisterForm()
        return view('auth.register'); // Show resources/views/auth/register.blade.php
    } // End of showRegisterForm()

    public function register(Request $request): RedirectResponse // POST /register: save a new tenant
    { // Start of register()
        $data = $request->validate([ // Check every field; on failure go back with error messages
            'nik' => ['required', 'unique:users,nik', function (string $attribute, mixed $value, \Closure $fail) { // NIK: filled, not used before, and ...
                if (! Tenant::validateNik((string) $value)) { // ... it must pass the class diagram's validateNik()
                    $fail('The NIK must be exactly 16 digits.'); // Error text shown under the NIK box
                } // End of the NIK check
            }], // End of the NIK rules
            'name' => ['required', 'string', 'max:100'], // Full name: filled, text, at most 100 characters
            'phone_number' => ['required', 'digits_between:10,15'], // Phone: filled, only digits, 10 to 15 of them
            'email' => ['required', 'email', 'max:100', 'unique:users,email'], // Email: filled, valid, at most 100 characters, not used before
            'password' => ['required', 'string', 'min:8', 'confirmed'], // Password: at least 8 characters and the same as "password_confirmation"
        ], [ // Second list: our own error texts for some rules
            'nik.unique' => 'This NIK is already registered.', // Shown when the NIK is already in the users table
            'email.unique' => 'This email is already registered.', // Shown when the email is already in the users table
        ]); // End of validate()

        Tenant::register($data); // Save the tenant (status starts as 'pending')

        return redirect()->route('login') // Go to the login page ...
            ->with('success', 'Registration successful. Please log in and wait for admin verification.'); // ... with a green message
    } // End of register()

    public function showLoginForm(): View // GET /login: show the login page
    { // Start of showLoginForm()
        return view('auth.login'); // Show resources/views/auth/login.blade.php
    } // End of showLoginForm()

    public function login(Request $request): RedirectResponse // POST /login: check email + password
    { // Start of login()
        $credentials = $request->validate([ // Check the form; on failure Laravel goes back with error messages
            'email' => ['required', 'email'], // Email must be filled and look like an email
            'password' => ['required'], // Password must be filled
        ]); // End of validate()

        if (! Auth::attempt($credentials)) { // Look up the email and compare the hashed password; false = wrong
            return back() // Go back to the login page ...
                ->withErrors(['email' => 'The email or password is wrong.']) // ... with this error message ...
                ->onlyInput('email'); // ... and keep the typed email (never the password)
        } // End of the failed-login case

        $request->session()->regenerate(); // New session ID after login (protects against session fixation)

        return redirect()->route('dashboard'); // Go to the home page of this user's role
    } // End of login()

    public function dashboard(Request $request): RedirectResponse // GET /dashboard: send each role to its own home page
    { // Start of dashboard()
        if ($request->user()->getRole() === Role::Admin) { // Logged-in user is an admin ...
            return redirect()->route('admin.dashboard'); // ... go to the admin home page
        } // End of the admin case

        return redirect()->route('tenant.dashboard'); // Otherwise it is a tenant: go to the tenant home page
    } // End of dashboard()

    public function logout(Request $request): RedirectResponse // POST /logout: log the user out
    { // Start of logout()
        Auth::logout(); // Forget who is logged in
        $request->session()->invalidate(); // Delete all session data
        $request->session()->regenerateToken(); // New CSRF token so old forms can't be reused

        return redirect()->route('login'); // Back to the login page
    } // End of logout()
} // End of the class