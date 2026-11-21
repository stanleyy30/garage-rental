<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Http\Requests\Concerns\RequiresPaymentProof; // Shared upload rule

class StoreAdminExtensionRequest extends AdminExtensionRequest // Admin's "Confirm and pay": same rules plus the proof
{ // Start of the class
    use RequiresPaymentProof; // Gives this class paymentProofRule()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return parent::rules() + $this->paymentProofRule(); // Extension rules plus the image rule
    } // End of rules()
} // End of the class
