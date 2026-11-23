<?php // Start of a PHP file

use Illuminate\Foundation\Application; // Laravel's application class
use Illuminate\Foundation\Configuration\Exceptions; // Settings for error handling
use Illuminate\Foundation\Configuration\Middleware; // Settings for middleware
use Illuminate\Http\Request; // The incoming browser request

return Application::configure(basePath: dirname(__DIR__)) // Build the app from the project folder
    ->withRouting( // Where the routes are
        web: __DIR__.'/../routes/web.php', // Web pages
        commands: __DIR__.'/../routes/console.php', // Artisan commands
        health: '/up', // A simple "is the app running" page
    ) // End of the routing settings
    ->withMiddleware(function (Middleware $middleware): void { // Middleware settings
        $middleware->alias([ // Give our middleware short names to use in routes
            'role' => \App\Http\Middleware\EnsureRole::class, // 'role:admin' / 'role:tenant' runs EnsureRole
            'verified_tenant' => \App\Http\Middleware\EnsureTenantIsVerified::class, // Only accepted tenants may rent or extend
        ]); // End of the alias list
    }) // End of the middleware settings
    ->withExceptions(function (Exceptions $exceptions): void { // Error handling settings
        $exceptions->shouldRenderJsonWhen( // Answer errors as JSON only ...
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(), // ... for API calls; web pages get normal error pages
        ); // End of the JSON rule
    })->create(); // Create the application
