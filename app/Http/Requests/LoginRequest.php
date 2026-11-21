<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class LoginRequest extends FormRequest // The rules for the login form
{ // Start of the class
    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // Anyone (only guests reach the login page)
    } // End of authorize()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return [ // One entry per field
            'email' => ['required', 'email'], // Email must be filled and look like an email
            'password' => ['required'], // Password must be filled
        ]; // End of the list
    } // End of rules()
} // End of the class
