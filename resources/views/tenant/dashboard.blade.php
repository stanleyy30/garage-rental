@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Tenant Dashboard') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Tenant Dashboard</h1> {{-- Page heading --}}
<p>Welcome, {{ $currentUserName }}.</p> {{-- Greeting with the tenant's name --}}

<div class="alert alert-{{ $statusColor }}">{{ $statusMessage }}</div> {{-- Status box; colour and text come from the controller --}}

@if ($canRent) {{-- The controller allows renting only for accepted tenants --}}
    <a href="{{ route('tenant.rentals.create') }}" class="btn btn-primary">Rent a garage</a> {{-- Start a rental --}}
    <a href="{{ route('tenant.rentals.index') }}" class="btn btn-outline-secondary">My rentals</a> {{-- See own rentals --}}
@endif {{-- End of the buttons --}}
@endsection {{-- End of this page's content --}}
