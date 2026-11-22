<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\Role; // The Role enum
use App\Enums\VerificationStatus; // The VerificationStatus enum
use App\Http\Requests\LoginRequest; // Validated login form
use App\Http\Requests\RegisterRequest; // Validated registration form
use App\Http\Requests\ResubmitRegistrationRequest; // Validated "send again" form
use App\Models\Tenant; // The Tenant model
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Http\Request; // The incoming browser request
use Illuminate\Support\Facades\Auth; // Laravel's login helper
use Illuminate\Support\Facades\Storage; // File storage (to remove the old KTP photo)
use Illuminate\View\View; // Return type for "show a page"

class AuthController extends Controller // Registration, login, logout and the home page after login
{ // Start of the class
    public function showRegisterForm(): View // GET /register
    { // Start of showRegisterForm()
        return $this->page('auth.register'); // Show the registration page
    } // End of showRegisterForm()

    public function register(RegisterRequest $request): RedirectResponse // POST /register (already validated by RegisterRequest)
    { // Start of register()
        $ktpPath = $request->file('ktp_photo')->store('ktp-photos', 'local'); // Save the KTP photo privately in storage/app/private/ktp-photos

        Tenant::create($request->safe()->only(['nik', 'name', 'phone_number', 'email', 'password']) + ['ktp_photo' => $ktpPath]); // Save the tenant; role and Pending status are set by the model

        return redirect()->route('login') // Go to the login page ...
            ->with('success', 'Registration successful. Please log in and wait for admin verification.'); // ... with a green message
    } // End of register()

    public function showResubmitForm(Request $request): View|RedirectResponse // GET /tenant/registration: a rejected tenant corrects their details
    { // Start of showResubmitForm()
        $tenant = Tenant::findOrFail($request->user()->user_id); // The logged-in tenant

        if ($tenant->verification_status !== VerificationStatus::Rejected) { // Only a rejected registration can be sent again ...
            return redirect()->route('tenant.dashboard'); // ... everyone else goes home
        } // End of the status check

        return $this->page('auth.resubmit', [ // Show the form filled with the current details
            'nik' => $tenant->nik, // NIK typed before
            'name' => $tenant->name, // Name typed before
            'phoneNumber' => $tenant->phone_number, // Phone typed before
        ]); // End of page()
    } // End of showResubmitForm()

    public function resubmit(ResubmitRegistrationRequest $request): RedirectResponse // PUT /tenant/registration: save the corrected details and wait for the admin again
    { // Start of resubmit()
        $tenant = Tenant::findOrFail($request->user()->user_id); // The logged-in tenant

        if ($tenant->verification_status !== VerificationStatus::Rejected) { // Only a rejected registration can be sent again ...
            return redirect()->route('tenant.dashboard'); // ... everyone else goes home
        } // End of the status check

        $oldPhoto = $tenant->ktp_photo; // The photo the admin rejected
        $newPhoto = $request->file('ktp_photo')->store('ktp-photos', 'local'); // Save the new KTP photo privately

        $tenant->update($request->safe()->only(['nik', 'name', 'phone_number']) + [ // Corrected details, plus ...
            'ktp_photo' => $newPhoto, // ... the new photo
            'verification_status' => VerificationStatus::Pending, // ... waiting for the admin again
            'verified_at' => null, // ... no decision yet
        ]); // End of update()

        if ($oldPhoto) { // There was an older photo ...
            Storage::disk('local')->delete($oldPhoto); // ... remove it, it is no longer needed
        } // End of the clean-up

        return redirect()->route('tenant.dashboard') // Back home ...
            ->with('success', 'Your registration was sent again. Please wait for the admin to check it.'); // ... with a green message
    } // End of resubmit()

    public function showLoginForm(): View // GET /login
    { // Start of showLoginForm()
        return $this->page('auth.login'); // Show the login page
    } // End of showLoginForm()

    public function login(LoginRequest $request): RedirectResponse // POST /login (already validated by LoginRequest)
    { // Start of login()
        if (! Auth::attempt($request->validated())) { // Look up the email and compare the hashed password; false = wrong
            return back() // Go back to the login page ...
                ->withErrors(['email' => 'The email or password is wrong.']) // ... with this error message ...
                ->onlyInput('email'); // ... and keep the typed email (never the password)
        } // End of the failed-login case

        $request->session()->regenerate(); // New session ID after login (protects against session fixation)

        return redirect()->route('dashboard'); // Go to the home page of this user's role
    } // End of login()

    public function dashboard(Request $request): RedirectResponse // GET /dashboard: send each role to its own home page
    { // Start of dashboard()
        $home = $request->user()->role === Role::Admin ? 'admin.dashboard' : 'tenant.dashboard'; // Pick the home page by role

        return redirect()->route($home); // Go there
    } // End of dashboard()

    public function logout(Request $request): RedirectResponse // POST /logout
    { // Start of logout()
        Auth::logout(); // Forget who is logged in
        $request->session()->invalidate(); // Delete all session data
        $request->session()->regenerateToken(); // New CSRF token so old forms can't be reused

        return redirect()->route('login'); // Back to the login page
    } // End of logout()
} // End of the class
