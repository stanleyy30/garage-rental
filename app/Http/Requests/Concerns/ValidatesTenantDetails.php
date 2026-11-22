<?php // Start of a PHP file

namespace App\Http\Requests\Concerns; // This file lives in app/Http/Requests/Concerns

use App\Http\Controllers\Concerns\CalculatesRentals; // Shared rules (for isValidNik)
use Closure; // Type of $fail in the NIK check
use Illuminate\Validation\Rule; // Helper for the unique rule

trait ValidatesTenantDetails // Rules for the tenant's identity fields, shared by registration and sending it again
{ // Start of the trait
    use CalculatesRentals; // Gives this trait isValidNik()

    protected function tenantDetailRules(?int $ignoreUserId = null): array // NIK, KTP photo, name and phone; $ignoreUserId lets a tenant keep their own NIK
    { // Start of tenantDetailRules()
        return [ // One entry per field
            'nik' => ['required', Rule::unique('users', 'nik')->ignore($ignoreUserId, 'user_id'), $this->checkNik(...)], // Filled, not used by another account, exactly 16 digits
            'ktp_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // Photo of the KTP, jpg/png, at most 2 MB, so the admin can compare the NIK
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL][\pL .\'-]*$/u'], // Letters, spaces, dots, apostrophes and hyphens, starting with a letter
            'phone_number' => ['required', 'regex:/^08[0-9]{8,13}$/'], // Indonesian mobile number: starts with 08, 10 to 15 digits in total
        ]; // End of the list
    } // End of tenantDetailRules()

    protected function tenantDetailMessages(): array // Error texts for the fields above
    { // Start of tenantDetailMessages()
        return [ // Rule name => text
            'nik.required' => 'Enter your 16-digit NIK.', // Empty NIK
            'nik.unique' => 'This NIK is already registered.', // Used by another account
            'ktp_photo.required' => 'Upload a photo of your KTP.', // No file chosen
            'ktp_photo.image' => 'The KTP photo must be a jpg or png image.', // Not an image
            'ktp_photo.mimes' => 'The KTP photo must be a jpg or png image.', // Wrong image type
            'ktp_photo.max' => 'The KTP photo may be at most 2 MB.', // Too big
            'name.required' => 'Enter your full name.', // Empty name
            'name.regex' => 'The name may only contain letters, spaces, dots, apostrophes and hyphens.', // Digits or symbols in the name
            'phone_number.required' => 'Enter your phone number.', // Empty phone
            'phone_number.regex' => 'Enter a phone number of 10 to 15 digits that starts with 08, like 081234567890.', // Wrong phone format
        ]; // End of the list
    } // End of tenantDetailMessages()

    private function checkNik(string $attribute, mixed $value, Closure $fail): void // Custom rule that reuses isValidNik()
    { // Start of checkNik()
        if (! $this->isValidNik((string) $value)) { // Not exactly 16 digits ...
            $fail('The NIK must be exactly 16 digits, numbers only.'); // ... show this error under the NIK box
        } // End of the check
    } // End of checkNik()
} // End of the trait
