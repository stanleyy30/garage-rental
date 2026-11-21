@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Confirm Extension') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Confirm Extension</h1> {{-- Page heading --}}

<x-summary-confirm :summary="$summary" :action="route('admin.extensions.store', $rentalId)"> {{-- Shared summary + payment form --}}
    <input type="hidden" name="days" value="{{ $days }}"> {{-- Send the days again; the controller calculates again --}}
</x-summary-confirm> {{-- End of the summary --}}
@endsection {{-- End of this page's content --}}
