<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

class StoreRentalRequest extends RentalRequest // The final "Confirm and pay" step: the same rules plus the payment proof
{ // Start of the class
    public function rules(): array // What every field must look like
    { // Start of rules()
        return parent::rules() + [ // All rules of RentalRequest, plus ...
            'payment_proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:1024'], // ... a jpg/png image of at most 1 MB
        ]; // End of the list
    } // End of rules()
} // End of the class
