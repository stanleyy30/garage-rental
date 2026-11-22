@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Confirm Extension') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Confirm Extension</h1> {{-- Page heading --}}
<p class="text-muted">After you send it, the admin checks your payment and accepts the extension. Your end date changes once it is accepted.</p> {{-- How the approval works --}}

<x-summary-confirm :summary="$summary" :action="route('tenant.extensions.store', $rentalId)"> {{-- Shared summary + payment form --}}
    @foreach ($formData as $field => $value) {{-- Send the choices again; the controller checks them again --}}
        <input type="hidden" name="{{ $field }}" value="{{ $value }}"> {{-- Hidden field --}}
    @endforeach {{-- End of the hidden fields --}}
</x-summary-confirm> {{-- End of the summary --}}

<a href="{{ $backUrl }}" class="btn btn-link mt-2">Change details</a> {{-- Back to the form, keeping the choice --}}
@endsection {{-- End of this page's content --}}
