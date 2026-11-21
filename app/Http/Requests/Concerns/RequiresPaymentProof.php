<?php // Start of a PHP file

namespace App\Http\Requests\Concerns; // This file lives in app/Http/Requests/Concerns

trait RequiresPaymentProof // The upload rule shared by every "Confirm and pay" form
{ // Start of the trait
    protected function paymentProofRule(): array // Rule for the payment proof field
    { // Start of paymentProofRule()
        return ['payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:1024']]; // A jpg/png image of at most 1 MB (1024 KB)
    } // End of paymentProofRule()
} // End of the trait
