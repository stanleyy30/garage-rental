@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Confirm Rental') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Confirm Rental</h1> {{-- Page heading --}}

<x-summary-confirm :summary="$summary" :action="route('tenant.rentals.store')"> {{-- Shared summary + payment form --}}
    @foreach ($formData as $field => $value) {{-- Send every detail again; the controller checks it all again --}}
        <input type="hidden" name="{{ $field }}" value="{{ $value }}"> {{-- Hidden field --}}
    @endforeach {{-- End of the hidden fields --}}
</x-summary-confirm> {{-- End of the summary --}}

<a href="{{ $backUrl }}" class="btn btn-link mt-2">Change details</a> {{-- Back to the form --}}
@endsection {{-- End of this page's content --}}
