<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Http\Requests\Concerns\ValidatesTenantDetails; // Shared NIK, KTP, name and phone rules
use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class ResubmitRegistrationRequest extends FormRequest // A rejected tenant corrects their details and sends the registration again
{ // Start of the class
    use ValidatesTenantDetails; // Gives this class tenantDetailRules() and tenantDetailMessages()

    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The route already allows only tenants; the controller checks the status
    } // End of authorize()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return $this->tenantDetailRules($this->user()->user_id); // Same rules as registration; the tenant may keep their own NIK
    } // End of rules()

    public function messages(): array // Our own error texts
    { // Start of messages()
        return $this->tenantDetailMessages(); // Same texts as registration
    } // End of messages()
} // End of the class
