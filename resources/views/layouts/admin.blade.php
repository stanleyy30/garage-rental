@extends('layouts.base') {{-- Admin pages use the base page --}}

@section('navbar') {{-- The admin's top bar --}}
    <x-navbar :user-name="$currentUserName" :user-role="$currentUserRole"> {{-- Shared top bar with the logged-in name --}}
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a></li> {{-- Admin home --}}
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.verifications.index') }}">Verify Registrants</a></li> {{-- Verify New Registrant --}}
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.garages.index') }}">Garages</a></li> {{-- Create Garages --}}
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.extensions.index') }}">Extend Rentals</a></li> {{-- Extend Garage Rental --}}
    </x-navbar> {{-- End of the top bar --}}
@endsection {{-- End of the top bar section --}}
