<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Http\Requests\Concerns\RequiresPaymentProof; // Shared upload rule
use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class AcceptExtensionRequest extends FormRequest // The admin's Accept button: needs the payment evidence
{ // Start of the class
    use RequiresPaymentProof; // Gives this class paymentProofRule()

    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The route already allows only the admin
    } // End of authorize()

    public function rules(): array // What the form must contain
    { // Start of rules()
        return $this->paymentProofRule(); // The payment evidence image
    } // End of rules()
} // End of the class
