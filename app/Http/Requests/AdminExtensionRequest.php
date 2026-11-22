<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class AdminExtensionRequest extends FormRequest // The admin's extension form: a new end date or a number of extra days
{ // Start of the class
    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The route already allows only the admin
    } // End of authorize()

    public function rules(): array // What the form must contain
    { // Start of rules()
        return [ // One entry per field
            'new_end_date' => ['nullable', 'required_without:days', 'date', 'after:' . $this->currentEndDate(), 'before_or_equal:' . $this->latestEndDate()], // New end date picked on the calendar, after the current one
            'days' => ['nullable', 'required_without:new_end_date', 'integer', 'min:1', 'max:3650'], // Or the number of extra days, 1 up to about 10 years
        ]; // End of the list
    } // End of rules()

    public function messages(): array // Clearer error texts for the end date
    { // Start of messages()
        return [ // One entry per rule
            'new_end_date.after' => 'The new end date must be after the current end date.', // Same or earlier date
            'new_end_date.required_without' => 'Choose a new end date or enter the number of extra days.', // Both empty
            'days.required_without' => 'Or enter the number of extra days here.', // Both empty (the date field already says what to do)
        ]; // End of the list
    } // End of messages()

    private function currentEndDate(): string // The rental's end date before extending, as Y-m-d
    { // Start of currentEndDate()
        return $this->route('rental')->end_date->toDateString(); // The rental from the URL
    } // End of currentEndDate()

    private function latestEndDate(): string // Furthest allowed new end date (3650 days later), as Y-m-d
    { // Start of latestEndDate()
        return $this->route('rental')->end_date->copy()->addDays(3650)->toDateString(); // Same limit as the days field
    } // End of latestEndDate()
} // End of the class
