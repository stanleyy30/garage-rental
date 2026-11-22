<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Http\Requests\Concerns\ValidatesTenantDetails; // Shared NIK, KTP, name and phone rules
use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class RegisterRequest extends FormRequest // The rules for the tenant registration form
{ // Start of the class
    use ValidatesTenantDetails; // Gives this class tenantDetailRules() and tenantDetailMessages()

    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // Anyone (only guests reach the registration page)
    } // End of authorize()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return $this->tenantDetailRules() + [ // NIK, KTP photo, name and phone, plus ...
            'email' => ['required', 'email:rfc', 'max:100', 'unique:users,email'], // Email: valid, at most 100 characters, not used before
            'password' => ['required', 'string', 'min:8', 'confirmed'], // At least 8 characters and the same as "password_confirmation"
        ]; // End of the list
    } // End of rules()

    public function messages(): array // Our own error texts
    { // Start of messages()
        return $this->tenantDetailMessages() + [ // Texts for the shared fields, plus ...
            'email.required' => 'Enter your email address.', // Empty email
            'email.email' => 'Enter a valid email address, like budi@example.com.', // Not an email
            'email.unique' => 'This email is already registered.', // Used before
            'password.required' => 'Enter a password.', // Empty password
            'password.min' => 'The password must be at least 8 characters.', // Too short
            'password.confirmed' => 'The two passwords do not match.', // Confirmation differs
        ]; // End of the list
    } // End of messages()
} // End of the class
