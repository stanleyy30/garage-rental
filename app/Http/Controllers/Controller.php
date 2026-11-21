<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use Illuminate\Support\Facades\Auth; // Laravel's login helper
use Illuminate\View\View; // Return type for "show a page"

abstract class Controller // Parent of every controller
{ // Start of the class
    protected function page(string $view, array $data = []): View // Show a page with the data every page needs
    { // Start of page()
        $user = Auth::user(); // The logged-in user, or null for guests

        return view($view, $data + [ // The page's own data, plus ...
            'currentUserName' => $user?->name, // ... the name for the top bar
            'currentUserRole' => $user?->role->label(), // ... the role label for the top bar
            'successMessage' => session('success'), // ... the green message after a redirect, if any
        ]); // End of view()
    } // End of page()

    protected function rupiah(float $amount): string // Format money for the screen: 1250000 -> "Rp 1.250.000"
    { // Start of rupiah()
        return 'Rp ' . number_format($amount, 0, ',', '.'); // Indonesian style: dot for thousands
    } // End of rupiah()

    protected function vehicleLabel(string $brandModel, ?string $plateNumber): string // Vehicle for the screen: "Toyota Avanza (L 1234 AB)"
    { // Start of vehicleLabel()
        return $plateNumber ? "{$brandModel} ({$plateNumber})" : $brandModel; // Plate in brackets; bicycles have none
    } // End of vehicleLabel()

    protected function date(?\DateTimeInterface $date): string // Format a date for the screen: "21 Nov 2026"
    { // Start of date()
        return $date ? $date->format('d M Y') : '-'; // A dash when there is no date
    } // End of date()
} // End of the class
