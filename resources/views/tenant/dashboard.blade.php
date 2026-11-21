@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Tenant Dashboard') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Tenant Dashboard</h1> {{-- Page heading --}}
<p>Welcome, {{ $currentUserName }}.</p> {{-- Greeting with the tenant's name --}}

<div class="alert alert-{{ $statusColor }}">{{ $statusMessage }}</div> {{-- Status box; colour and text come from the controller --}}
@endsection {{-- End of this page's content --}}
