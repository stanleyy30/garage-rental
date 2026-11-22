<?php // Start of a PHP file

namespace Tests\Feature; // This file lives in tests/Feature

use App\Models\Admin; // The Admin model
use App\Models\Tenant; // The Tenant model
use Illuminate\Foundation\Testing\RefreshDatabase; // Fresh database for every test
use Illuminate\Http\UploadedFile; // Fake uploaded files
use Illuminate\Support\Facades\Storage; // File storage (faked in tests)
use Tests\TestCase; // Laravel's base test class

class VerificationTest extends TestCase // Tests for Verify New Registrant
{ // Start of the class
    use RefreshDatabase; // Rebuild the tables before each test

    private Admin $admin; // The admin used in every test

    private Tenant $tenant; // A pending tenant used in every test

    protected function setUp(): void // Runs before each test
    { // Start of setUp()
        parent::setUp(); // Laravel's own preparation
        $this->admin = Admin::create(['name' => 'Administrator', 'email' => 'admin@garage.test', 'password' => 'admin12345']); // The admin
        Storage::fake('local'); // KTP photos go to a fake private disk
        $ktp = UploadedFile::fake()->image('ktp.jpg', 860, 540)->store('ktp-photos', 'local'); // A saved KTP photo
        $this->tenant = Tenant::create(['nik' => '3578010101010001', 'ktp_photo' => $ktp, 'name' => 'Budi', 'phone_number' => '081234567890', 'email' => 'budi@mail.test', 'password' => 'secret123']); // A pending tenant with a KTP photo
    } // End of setUp()

    public function test_the_admin_sees_pending_tenants(): void // Scenario: list
    { // Start of the test
        $this->actingAs($this->admin)->get('/admin/verifications') // Open the page as admin
            ->assertOk()->assertSee('3578010101010001')->assertSee('Waiting for verification (1)'); // The tenant is listed
    } // End of the test

    public function test_the_admin_can_see_the_ktp_photo_to_compare_the_nik(): void // Scenario: KTP check
    { // Start of the test
        $url = "/admin/verifications/{$this->tenant->user_id}/ktp"; // The photo's address
        $this->actingAs($this->admin)->get('/admin/verifications')->assertSee($url); // The list shows the photo
        $this->actingAs($this->admin)->get($url)->assertOk()->assertHeader('content-type', 'image/jpeg'); // The admin can open it
    } // End of the test

    public function test_only_the_admin_can_open_a_ktp_photo(): void // Scenario: KTP privacy
    { // Start of the test
        $this->get("/admin/verifications/{$this->tenant->user_id}/ktp")->assertRedirect('/login'); // Guests are sent to login
        $this->actingAs($this->tenant)->get("/admin/verifications/{$this->tenant->user_id}/ktp")->assertForbidden(); // Tenants are refused, even for their own photo
    } // End of the test

    public function test_the_admin_can_accept_a_tenant(): void // Scenario: accept
    { // Start of the test
        $this->actingAs($this->admin)->patch("/admin/verifications/{$this->tenant->user_id}", ['decision' => 'accept']) // Press Accept
            ->assertRedirect('/admin/verifications'); // Back to the list
        $this->assertSame('accepted', $this->tenant->fresh()->verification_status->value); // Saved as accepted
        $this->assertNotNull($this->tenant->fresh()->verified_at); // With the decision time
    } // End of the test

    public function test_the_admin_can_reject_a_tenant(): void // Scenario: reject
    { // Start of the test
        $this->actingAs($this->admin)->patch("/admin/verifications/{$this->tenant->user_id}", ['decision' => 'reject']); // Press Reject
        $this->assertSame('rejected', $this->tenant->fresh()->verification_status->value); // Saved as rejected
    } // End of the test

    public function test_a_tenant_cannot_be_verified_twice(): void // Scenario: already decided
    { // Start of the test
        $this->actingAs($this->admin)->patch("/admin/verifications/{$this->tenant->user_id}", ['decision' => 'reject']); // First decision
        $this->actingAs($this->admin)->patch("/admin/verifications/{$this->tenant->user_id}", ['decision' => 'accept']) // Second try
            ->assertSessionHasErrors('decision'); // Refused
        $this->assertSame('rejected', $this->tenant->fresh()->verification_status->value); // Still rejected
    } // End of the test

    public function test_only_accept_or_reject_is_allowed(): void // Scenario: edited form value
    { // Start of the test
        $this->actingAs($this->admin)->patch("/admin/verifications/{$this->tenant->user_id}", ['decision' => 'maybe']) // Invalid value
            ->assertSessionHasErrors('decision'); // Refused
    } // End of the test

    public function test_the_admin_account_cannot_be_verified(): void // Scenario: Tenant binding only finds tenants
    { // Start of the test
        $this->actingAs($this->admin)->patch("/admin/verifications/{$this->admin->user_id}", ['decision' => 'accept'])->assertNotFound(); // 404
    } // End of the test

    public function test_the_ktp_opens_in_a_viewer_next_to_the_typed_nik(): void // Scenario: turnable KTP viewer
    { // Start of the test
        $this->actingAs($this->admin)->get('/admin/verifications') // The page
            ->assertSee('data-viewer-src', false)->assertSee('NIK typed: 3578010101010001') // The photo opens in the viewer with the typed NIK under it
            ->assertSee('Turn left')->assertSee('Turn right'); // The viewer can turn the photo
    } // End of the test

} // End of the class
