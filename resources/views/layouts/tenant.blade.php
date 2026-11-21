@extends('layouts.base') {{-- Tenant pages use the base page --}}

@section('navbar') {{-- The tenant's top bar --}}
    <x-navbar :user-name="$currentUserName" :user-role="$currentUserRole"> {{-- Shared top bar with the logged-in name --}}
        <li class="nav-item"><a class="nav-link" href="{{ route('tenant.dashboard') }}">Dashboard</a></li> {{-- Tenant home --}}
    </x-navbar> {{-- End of the top bar --}}
@endsection {{-- End of the top bar section --}}
