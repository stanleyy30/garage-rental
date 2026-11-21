<!DOCTYPE html> {{-- Tells the browser this is an HTML5 page --}}
<html lang="en"> {{-- Page language: English --}}
<head> {{-- Settings that are not shown on the page --}}
    <meta charset="utf-8"> {{-- Text encoding, so all characters show correctly --}}
    <meta name="viewport" content="width=device-width, initial-scale=1"> {{-- Fit the page to phone screens too --}}
    <title>@yield('title', 'Home') - {{ config('app.name') }}</title> {{-- Tab title: page title + app name from .env --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> {{-- Bootstrap 5 styles from a CDN --}}
</head> {{-- End of head --}}
<body class="bg-light"> {{-- Light grey page background --}}
    @auth {{-- Only show the top bar when someone is logged in --}}
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4"> {{-- Dark top bar --}}
            <div class="container"> {{-- Centre the bar's content --}}
                <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a> {{-- App name, links to the home page --}}
                <div class="d-flex align-items-center gap-3"> {{-- Right side: name + logout, side by side --}}
                    <span class="text-white">{{ auth()->user()->getName() }} ({{ auth()->user()->getRole()->value }})</span> {{-- Who is logged in and their role --}}
                    <form method="POST" action="{{ route('logout') }}"> {{-- Logout is a POST form, not a link --}}
                        @csrf {{-- Hidden token that proves the form came from this site --}}
                        <button type="submit" class="btn btn-outline-light btn-sm">Logout</button> {{-- Logout button --}}
                    </form> {{-- End of the logout form --}}
                </div> {{-- End of the right side --}}
            </div> {{-- End of the container --}}
        </nav> {{-- End of the top bar --}}
    @endauth {{-- End of the logged-in check --}}

    <main class="container"> {{-- Centred main area --}}
        @if (session('success')) {{-- A success message was sent from the controller --}}
            <div class="alert alert-success">{{ session('success') }}</div> {{-- Show it in a green box --}}
        @endif {{-- End of the success message --}}

        @yield('content') {{-- Each page puts its own content here --}}
    </main> {{-- End of the main area --}}

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> {{-- Bootstrap's JavaScript (dropdowns, etc.) --}}
</body> {{-- End of body --}}
</html> {{-- End of the page --}}