<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use App\Enums\RentalType; // The RentalType enum
use App\Enums\VehicleType; // The VehicleType enum
use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form
use Illuminate\Validation\Rule; // Helper for enum rules

class RentalRequest extends FormRequest // The rules for the Rent New Garage form (the "Calculate total" step)
{ // Start of the class
    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The route already allows only accepted tenants
    } // End of authorize()

    public function rules(): array // What every field must look like
    { // Start of rules()
        return [ // One entry per field
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)], // Bicycle / motorcycle / car / electric car
            'plate_number' => ['nullable', 'required_unless:vehicle_type,bicycle', 'string', 'max:12'], // Every vehicle except a bicycle needs a plate
            'brand_model' => ['required', 'string', 'max:100'], // e.g. Honda Beat
            'battery_capacity_kwh' => ['nullable', 'required_if:vehicle_type,electric_car', 'numeric', 'min:1', 'max:9999'], // Max kWh, electric cars only
            'rental_type' => ['required', Rule::enum(RentalType::class)], // Daily or monthly
            'start_date' => ['required', 'date', 'date_equals:today'], // A booking always starts today; any other day is refused
            'days' => ['nullable', 'required_if:rental_type,daily', 'integer', 'min:1'], // Number of days (daily only; the upper limit is checked by the controller)
            'garage_id' => ['required', 'string', 'exists:garages,garage_id'], // The chosen garage must exist
            'battery_level_percent' => ['nullable', 'integer', 'min:0', 'max:100'], // Remaining battery (checked by the controller for E garages)
            'kwh_to_full' => ['nullable', 'numeric', 'min:0', 'max:9999'], // kWh until full (checked by the controller for E garages)
        ]; // End of the list
    } // End of rules()

    public function messages(): array // Our own error texts
    { // Start of messages()
        return [ // Rule name => text
            'plate_number.required_unless' => 'A plate number is needed for every vehicle except a bicycle.', // Missing plate
            'battery_capacity_kwh.required_if' => 'Enter the battery size (max kWh) of the electric car.', // Missing battery size
            'days.required_if' => 'Enter the number of days for a daily rental.', // Missing days
            'start_date.date_equals' => 'A booking always starts today.', // Another date was sent
        ]; // End of the list
    } // End of messages()
} // End of the class
