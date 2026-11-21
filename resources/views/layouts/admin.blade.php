@extends('layouts.base') {{-- Admin pages use the base page --}}

@section('navbar') {{-- The admin's top bar --}}
    <x-navbar :user-name="$currentUserName" :user-role="$currentUserRole"> {{-- Shared top bar with the logged-in name --}}
        <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Dashboard</a></li> {{-- Admin home --}}
    </x-navbar> {{-- End of the top bar --}}
@endsection {{-- End of the top bar section --}}
