<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Http\Controllers\Concerns\CalculatesRentals; // Shared rules (for isValidNik)
use Closure; // Type of $fail in the NIK check
use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class RegisterRequest extends FormRequest // The rules for the tenant registration form
{ // Start of the class
    use CalculatesRentals; // Gives this class isValidNik()

    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // Anyone (only guests reach the registration page)
    } // End of authorize()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return [ // One entry per field
            'nik' => ['required', 'unique:users,nik', $this->checkNik(...)], // Filled, not registered before, exactly 16 digits
            'ktp_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // Photo of the KTP, jpg/png, at most 2 MB, so the admin can compare the NIK
            'name' => ['required', 'string', 'max:100'], // Full name: filled, text, at most 100 characters
            'phone_number' => ['required', 'digits_between:10,15'], // Phone: filled, only digits, 10 to 15 of them
            'email' => ['required', 'email', 'max:100', 'unique:users,email'], // Email: valid, at most 100 characters, not used before
            'password' => ['required', 'string', 'min:8', 'confirmed'], // At least 8 characters and the same as "password_confirmation"
        ]; // End of the list
    } // End of rules()

    public function messages(): array // Our own error texts for some rules
    { // Start of messages()
        return [ // Rule name => text
            'nik.unique' => 'This NIK is already registered.', // The NIK is already in the users table
            'email.unique' => 'This email is already registered.', // The email is already in the users table
            'ktp_photo.required' => 'Upload a photo of your KTP.', // No file chosen
        ]; // End of the list
    } // End of messages()

    private function checkNik(string $attribute, mixed $value, Closure $fail): void // Custom rule that reuses isValidNik()
    { // Start of checkNik()
        if (! $this->isValidNik((string) $value)) { // Not exactly 16 digits ...
            $fail('The NIK must be exactly 16 digits.'); // ... show this error under the NIK box
        } // End of the check
    } // End of checkNik()
} // End of the class
