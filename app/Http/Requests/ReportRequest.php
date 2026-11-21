<?php // Start of a PHP file

namespace App\Http\Requests; // This file lives in app/Http/Requests

use Illuminate\Foundation\Http\FormRequest; // Laravel's base class for a validated form

class ReportRequest extends FormRequest // The filter form at the top of the report page
{ // Start of the class
    public const TYPES = [ // The four reports: value => label
        'monthly' => 'Per month in a year', // 12 rows
        'daily' => 'Per day in a month', // One row per day
        'garage_type' => 'Per garage type', // One row per type
        'tenant' => 'Per tenant', // One row per tenant
    ]; // End of the list

    public function authorize(): bool // Who may send this form
    { // Start of authorize()
        return true; // The route already allows only the admin
    } // End of authorize()

    public function rules(): array // What every field must look like (all optional; defaults are used)
    { // Start of rules()
        return [ // One entry per field
            'type' => ['nullable', 'in:' . implode(',', array_keys(self::TYPES))], // One of the four reports
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'], // Year for the monthly and daily reports
            'month' => ['nullable', 'integer', 'min:1', 'max:12'], // Month for the daily report
        ]; // End of the list
    } // End of rules()
} // End of the class
