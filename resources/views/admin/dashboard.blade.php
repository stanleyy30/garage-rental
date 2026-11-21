@extends('layouts.app') {{-- Use the shared layout --}}

@section('title', 'Admin Dashboard') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Admin Dashboard</h1> {{-- Page heading --}}
<p>Welcome, {{ auth()->user()->getName() }}.</p> {{-- Greet the logged-in admin by name --}}
@endsection {{-- End of this page's content --}}