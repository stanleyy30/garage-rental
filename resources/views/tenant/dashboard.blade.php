@extends('layouts.app') {{-- Use the shared layout --}}

@section('title', 'Tenant Dashboard') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Tenant Dashboard</h1> {{-- Page heading --}}
<p>Welcome, {{ auth()->user()->getName() }}.</p> {{-- Greet the logged-in tenant by name --}}

@php($status = auth()->user()->verification_status) {{-- Store the tenant's status in $status --}}

@if ($status === \App\Enums\VerificationStatus::Accepted) {{-- Admin accepted this tenant --}}
    <div class="alert alert-success">Your account is verified. You can rent a garage.</div> {{-- Green box --}}
@elseif ($status === \App\Enums\VerificationStatus::Rejected) {{-- Admin rejected this tenant --}}
    <div class="alert alert-danger">Your registration was rejected. You can't rent a garage.</div> {{-- Red box --}}
@else {{-- Still pending --}}
    <div class="alert alert-warning">Your registration is waiting for admin verification.</div> {{-- Yellow box --}}
@endif {{-- End of the status check --}}
@endsection {{-- End of this page's content --}}