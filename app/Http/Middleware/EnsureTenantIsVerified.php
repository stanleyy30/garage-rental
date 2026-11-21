<?php // Start of a PHP file

namespace App\Http\Middleware; // This file lives in app/Http/Middleware

use App\Enums\VerificationStatus; // The VerificationStatus enum
use Closure; // Type of $next: the next step of the request
use Illuminate\Http\Request; // The incoming browser request
use Symfony\Component\HttpFoundation\Response; // The type of answer sent back to the browser

class EnsureTenantIsVerified // Middleware: only tenants accepted by the admin may rent or extend
{ // Start of the class
    public function handle(Request $request, Closure $next): Response // Runs before the page
    { // Start of handle()
        if ($request->user()->verification_status !== VerificationStatus::Accepted) { // Not accepted (pending or rejected) ...
            return redirect()->route('tenant.dashboard'); // ... back to the dashboard, which explains the status
        } // End of the check

        return $next($request); // Accepted: continue to the page
    } // End of handle()
} // End of the class
