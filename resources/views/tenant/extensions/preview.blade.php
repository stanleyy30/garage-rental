@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Confirm Extension') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Confirm Extension</h1> {{-- Page heading --}}

<x-summary-confirm :summary="$summary" :action="route('tenant.extensions.store', $rentalId)"> {{-- Shared summary + payment form --}}
    @foreach ($formData as $field => $value) {{-- Send the choices again; the controller checks them again --}}
        <input type="hidden" name="{{ $field }}" value="{{ $value }}"> {{-- Hidden field --}}
    @endforeach {{-- End of the hidden fields --}}
</x-summary-confirm> {{-- End of the summary --}}
@endsection {{-- End of this page's content --}}
