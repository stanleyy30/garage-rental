<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Http\Requests\Concerns\RequiresPaymentProof; // Shared upload rule

class StoreRentalRequest extends RentalRequest // The final "Confirm and pay" step: the same rules plus the payment proof
{ // Start of the class
    use RequiresPaymentProof; // Gives this class paymentProofRule()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return parent::rules() + $this->paymentProofRule(); // All rental rules plus the image rule
    } // End of rules()

    public function messages(): array // Our own error texts
    { // Start of messages()
        return parent::messages() + $this->paymentProofMessages(); // Texts for the form plus the payment proof
    } // End of messages()
} // End of the class
