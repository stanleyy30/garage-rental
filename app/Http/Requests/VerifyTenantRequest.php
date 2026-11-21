<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class VerifyTenantRequest extends FormRequest // The rules for the Accept / Reject buttons
{ // Start of the class
    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The route already allows only the admin (role:admin)
    } // End of authorize()

    public function rules(): array // What the form must contain
    { // Start of rules()
        return [ // One entry per field
            'decision' => ['required', 'in:accept,reject'], // Only "accept" or "reject" are allowed
        ]; // End of the list
    } // End of rules()
} // End of the class
