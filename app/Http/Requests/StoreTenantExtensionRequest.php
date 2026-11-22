<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Http\Requests\Concerns\RequiresPaymentProof; // Shared upload rule

class StoreTenantExtensionRequest extends TenantExtensionRequest // Tenant's "Confirm and pay": same rules plus the proof
{ // Start of the class
    use RequiresPaymentProof; // Gives this class paymentProofRule()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return parent::rules() + $this->paymentProofRule(); // Extension rules plus the image rule
    } // End of rules()

    public function messages(): array // Our own error texts
    { // Start of messages()
        return $this->paymentProofMessages(); // Texts for the form plus the payment proof
    } // End of messages()
} // End of the class
