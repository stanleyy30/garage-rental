<?php // Start of a PHP file

namespace Tests\Feature; // This file lives in tests/Feature

use App\Models\Admin; // The Admin model
use App\Models\Tenant; // The Tenant model
use Illuminate\Foundation\Testing\RefreshDatabase; // Gives every test a fresh, empty database
use Illuminate\Http\UploadedFile; // Fake uploaded files
use Illuminate\Support\Facades\Storage; // File storage (faked in tests)
use Tests\TestCase; // Laravel's base test class

class AuthTest extends TestCase // Tests for registration, login, logout and role pages
{ // Start of the class
    use RefreshDatabase; // Rebuild the tables before each test

    protected function setUp(): void // Runs before each test
    { // Start of setUp()
        parent::setUp(); // Laravel's own preparation
        Storage::fake('local'); // KTP photos go to a fake private disk
    } // End of setUp()

    private function validRegistration(array $changes = []): array // A correct registration form, with optional changes
    { // Start of validRegistration()
        return array_merge([ // Default values ...
            'nik' => '3578010101010001', // 16 digits
            'name' => 'Budi Santoso', // Full name
            'phone_number' => '081234567890', // 12 digits
            'email' => 'budi@mail.test', // Email
            'password' => 'secret123', // 9 characters
            'password_confirmation' => 'secret123', // Same password
            'ktp_photo' => UploadedFile::fake()->image('ktp.jpg', 860, 540)->size(300), // A 300 KB KTP photo
        ], $changes); // ... replaced by any changes given
    } // End of validRegistration()

    public function test_a_visitor_can_register_and_starts_as_pending(): void // Scenario: successful registration
    { // Start of the test
        $response = $this->post('/register', $this->validRegistration()); // Send the form

        $response->assertRedirect('/login'); // Goes to the login page
        $this->assertDatabaseHas('users', ['email' => 'budi@mail.test', 'role' => 'tenant', 'verification_status' => 'pending']); // Saved as a pending tenant
        Storage::disk('local')->assertExists(Tenant::first()->ktp_photo); // The KTP photo was saved privately
    } // End of the test

    public function test_registration_needs_a_ktp_photo_image(): void // Scenario: KTP upload rules
    { // Start of the test
        $this->post('/register', $this->validRegistration(['ktp_photo' => null]))->assertSessionHasErrors('ktp_photo'); // No photo
        $this->post('/register', $this->validRegistration(['ktp_photo' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf')]))->assertSessionHasErrors('ktp_photo'); // Not an image
        $this->post('/register', $this->validRegistration(['ktp_photo' => UploadedFile::fake()->image('ktp.jpg')->size(3000)]))->assertSessionHasErrors('ktp_photo'); // Bigger than 2 MB
        $this->assertDatabaseCount('users', 0); // Nothing was saved
    } // End of the test

    public function test_registration_rejects_a_nik_that_is_not_16_digits(): void // Scenario: wrong NIK
    { // Start of the test
        $this->post('/register', $this->validRegistration(['nik' => '12345']))->assertSessionHasErrors('nik'); // Too short
        $this->post('/register', $this->validRegistration(['nik' => '12345678901234AB']))->assertSessionHasErrors('nik'); // Has letters
        $this->assertDatabaseCount('users', 0); // Nothing was saved
    } // End of the test

    public function test_registration_rejects_a_duplicate_nik_and_email(): void // Scenario: already registered
    { // Start of the test
        $this->post('/register', $this->validRegistration()); // First registration works
        $this->post('/register', $this->validRegistration(['phone_number' => '081111111111'])) // Same NIK and email again
            ->assertSessionHasErrors(['nik', 'email']); // Both are rejected
    } // End of the test

    public function test_the_admin_can_log_in_and_sees_the_admin_dashboard(): void // Scenario: admin login
    { // Start of the test
        Admin::create(['name' => 'Administrator', 'email' => 'admin@garage.test', 'password' => 'admin12345']); // The seeded admin

        $this->post('/login', ['email' => 'admin@garage.test', 'password' => 'admin12345'])->assertRedirect('/dashboard'); // Login works
        $this->get('/dashboard')->assertRedirect('/admin'); // Sent to the admin home page
        $this->get('/admin')->assertOk()->assertSee('Admin Dashboard'); // The page opens
    } // End of the test

    public function test_a_wrong_password_is_rejected(): void // Scenario: wrong password
    { // Start of the test
        Admin::create(['name' => 'Administrator', 'email' => 'admin@garage.test', 'password' => 'admin12345']); // The seeded admin

        $this->post('/login', ['email' => 'admin@garage.test', 'password' => 'wrong'])->assertSessionHasErrors('email'); // Error under the email box
        $this->assertGuest(); // Nobody is logged in
    } // End of the test

    public function test_each_role_is_blocked_from_the_other_roles_pages(): void // Scenario: role middleware
    { // Start of the test
        $tenant = Tenant::create($this->validRegistration()); // A tenant
        $admin = Admin::create(['name' => 'Administrator', 'email' => 'admin@garage.test', 'password' => 'admin12345']); // The admin

        $this->actingAs($tenant)->get('/admin')->assertForbidden(); // Tenant cannot open admin pages
        $this->actingAs($admin)->get('/tenant')->assertForbidden(); // Admin cannot open tenant pages
    } // End of the test

    public function test_guests_are_sent_to_the_login_page(): void // Scenario: not logged in
    { // Start of the test
        $this->get('/admin')->assertRedirect('/login'); // Admin page needs a login
        $this->get('/tenant')->assertRedirect('/login'); // Tenant page needs a login
    } // End of the test

    public function test_logout_ends_the_session(): void // Scenario: logout
    { // Start of the test
        $tenant = Tenant::create($this->validRegistration()); // A tenant

        $this->actingAs($tenant)->post('/logout')->assertRedirect('/login'); // Logout goes to the login page
        $this->assertGuest(); // Nobody is logged in any more
    } // End of the test
} // End of the class
