<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Enums\GarageType; // The GarageType enum
use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form
use Illuminate\Validation\Rule; // Helper for enum rules

class StoreGaragesRequest extends FormRequest // The rules for the Create Garages form
{ // Start of the class
    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The route already allows only the admin
    } // End of authorize()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return [ // One entry per field
            'type' => ['required', Rule::enum(GarageType::class)], // Must be one of the garage types
            'quantity' => ['required', 'integer', 'min:1', 'max:999'], // How many garages to create at once
            'daily_price' => ['required', 'numeric', 'min:1', 'max:99999999'], // Price per day in rupiah
            'electricity_price_per_kwh' => ['nullable', 'required_if:type,electric_car', 'numeric', 'min:1', 'max:99999999'], // Needed only for electric car garages
        ]; // End of the list
    } // End of rules()

    public function messages(): array // Our own error texts
    { // Start of messages()
        return [ // Rule name => text
            'electricity_price_per_kwh.required_if' => 'Electric car garages need a price per kWh.', // Missing kWh price
        ]; // End of the list
    } // End of messages()
} // End of the class
