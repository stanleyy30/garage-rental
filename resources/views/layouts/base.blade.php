<!DOCTYPE html> {{-- Tells the browser this is an HTML5 page --}}
<html lang="en"> {{-- Page language: English --}}
<head> {{-- Settings that are not shown on the page --}}
    <meta charset="utf-8"> {{-- Text encoding, so all characters show correctly --}}
    <meta name="viewport" content="width=device-width, initial-scale=1"> {{-- Fit the page to phone screens too --}}
    <title>@yield('title') - {{ config('app.name') }}</title> {{-- Tab title: page title + app name from .env --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> {{-- Bootstrap 5 styles from a CDN --}}
    <link href="{{ asset('css/garage-rental.css') }}" rel="stylesheet"> {{-- Our own styles: booking calendar and image viewer --}}
</head> {{-- End of head --}}
<body class="bg-light"> {{-- Light grey page background --}}
    @yield('navbar') {{-- The admin or tenant layout puts its top bar here; guest pages have none --}}

    <main class="container pb-5"> {{-- Centred main area --}}
        @if ($successMessage) {{-- The controller passed a green message --}}
            <div class="alert alert-success">{{ $successMessage }}</div> {{-- Show it --}}
        @endif {{-- End of the message --}}

        @yield('content') {{-- Each page puts its own content here --}}
    </main> {{-- End of the main area --}}

    <x-image-viewer /> {{-- Pop-up that shows an image and can turn it (KTP photos, payment proofs) --}}

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> {{-- Bootstrap's JavaScript (pop-ups, menu toggle on phones) --}}
    <script src="{{ asset('js/garage-rental.js') }}"></script> {{-- Our helpers: booking calendar and image viewer --}}
    @stack('scripts') {{-- Page scripts added with @push('scripts') --}}
</body> {{-- End of body --}}
</html> {{-- End of the page --}}
