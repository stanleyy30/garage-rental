<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Enums\RentalType; // The RentalType enum
use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form
use Illuminate\Validation\Rule; // Helper for enum rules

class TenantExtensionRequest extends FormRequest // The tenant's extension form (booking limits)
{ // Start of the class
    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The controller checks that the rental belongs to this tenant
    } // End of authorize()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return [ // One entry per field
            'rental_type' => ['required', Rule::enum(RentalType::class)], // Daily or monthly
            'days' => ['nullable', 'required_if:rental_type,daily', 'integer', 'min:1'], // Daily only; the upper limit is checked by the controller
        ]; // End of the list
    } // End of rules()
} // End of the class
