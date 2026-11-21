<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class AdminExtensionRequest extends FormRequest // The admin's extension form: any number of days the tenant asked for
{ // Start of the class
    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The route already allows only the admin
    } // End of authorize()

    public function rules(): array // What the form must contain
    { // Start of rules()
        return [ // One entry per field
            'days' => ['required', 'integer', 'min:1', 'max:3650'], // 1 day up to about 10 years
        ]; // End of the list
    } // End of rules()
} // End of the class
