<?php // Start of a PHP file

namespace App\Http\Requests\Concerns; // This file lives in app/Http/Requests/Concerns

trait RequiresPaymentProof // The upload rule shared by every "Confirm and pay" form
{ // Start of the trait
    protected function paymentProofRule(): array // Rule for the payment proof field
    { // Start of paymentProofRule()
        return ['payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:1024']]; // A jpg/png image of at most 1 MB (1024 KB)
    } // End of paymentProofRule()

    protected function paymentProofMessages(): array // Error texts for the payment proof field
    { // Start of paymentProofMessages()
        return [ // Rule name => text
            'payment_proof.required' => 'Upload the payment proof image before you confirm.', // No file chosen
            'payment_proof.image' => 'The payment proof must be a jpg or png image.', // Not an image
            'payment_proof.mimes' => 'The payment proof must be a jpg or png image.', // Wrong image type
            'payment_proof.max' => 'The payment proof may be at most 1 MB.', // Too big
        ]; // End of the list
    } // End of paymentProofMessages()
} // End of the trait
