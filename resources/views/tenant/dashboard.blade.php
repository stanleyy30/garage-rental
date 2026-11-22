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
@if ($canResubmit) {{-- A rejected tenant may correct the details --}}
    <a href="{{ route('tenant.registration.edit') }}" class="btn btn-primary">Correct and send again</a> {{-- Open the form --}}
@endif {{-- End of the resubmit button --}}

@if (count($updates) > 0) {{-- The admin decided something about an extension lately --}}
    <h2 class="h5 mt-4">Extension updates</h2> {{-- Section title --}}
    <div class="list-group shadow-sm"> {{-- One line per update --}}
        @foreach ($updates as $update) {{-- Each update from the controller --}}
            <div class="list-group-item d-flex justify-content-between align-items-center"> {{-- One update --}}
                <span><span class="badge bg-{{ $update['color'] }} me-2">{{ $update['garage'] }}</span>{{ $update['text'] }}</span> {{-- Garage and what happened --}}
                <small class="text-muted">{{ $update['when'] }}</small> {{-- When --}}
            </div> {{-- End of the update --}}
        @endforeach {{-- End of the updates --}}
    </div> {{-- End of the list --}}
@endif {{-- End of the updates --}}
@endsection {{-- End of this page's content --}}
