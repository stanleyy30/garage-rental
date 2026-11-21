<?php // Start of a PHP file

namespace App\Http\Middleware; // This file lives in app/Http/Middleware

use Closure; // Type of $next: the next step of the request
use Illuminate\Http\Request; // The incoming browser request
use Symfony\Component\HttpFoundation\Response; // The type of answer sent back to the browser

class EnsureRole // Middleware: a check that runs before a page is opened
{ // Start of the class
    public function handle(Request $request, Closure $next, string $role): Response // $role comes from the route, e.g. 'role:admin'
    { // Start of handle()
        if ($request->user()->role->value !== $role) { // The logged-in user's role is not the one this page needs ...
            abort(403, 'You are not allowed to open this page.'); // ... so stop with "403 Forbidden"
        } // End of the role check

        return $next($request); // Role is correct: continue to the page
    } // End of handle()
} // End of the class
