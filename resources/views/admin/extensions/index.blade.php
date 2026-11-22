@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Extend Garage Rental') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Extend Garage Rental</h1> {{-- Page heading --}}
<p class="text-muted">Garages rented today. Find the renter by name, then extend their garage by the number of days they asked for.</p> {{-- Instruction --}}

<form method="GET" action="{{ route('admin.extensions.index') }}" class="row g-2 mb-3"> {{-- Search by renter name --}}
    <div class="col-md-6"><input type="search" name="tenant" value="{{ $tenantName }}" class="form-control" placeholder="Renter name" aria-label="Renter name"></div> {{-- Name box --}}
    <div class="col-auto"><button type="submit" class="btn btn-outline-primary">Search</button></div> {{-- Run the search --}}
    @if ($tenantName !== '') {{-- A name is being searched --}}
        <div class="col-auto"><a href="{{ route('admin.extensions.index') }}" class="btn btn-link">Show all</a></div> {{-- Clear the search --}}
    @endif {{-- End of the check --}}
</form> {{-- End of the search --}}

<div class="card shadow-sm"> {{-- List box --}}
    <div class="table-responsive"> {{-- Scroll wrapper for phones --}}
        <table class="table table-striped mb-0 align-middle"> {{-- Striped table --}}
            <thead> {{-- Table header --}}
                <tr><th>Garage</th><th>Tenant</th><th>Vehicle</th><th>Ends on</th><th></th></tr> {{-- Column names --}}
            </thead> {{-- End of the header --}}
            <tbody> {{-- Table body --}}
                @forelse ($rentals as $rental) {{-- One row per rental from the controller --}}
                    <tr> {{-- Start of the row --}}
                        <td>{{ $rental['garage'] }}</td> {{-- Garage --}}
                        <td>{{ $rental['tenant'] }}</td> {{-- Tenant --}}
                        <td>{{ $rental['vehicle'] }}</td> {{-- Vehicle --}}
                        <td>{{ $rental['endDate'] }}</td> {{-- Current end date --}}
                        <td class="text-end"><a href="{{ route('admin.extensions.create', $rental['id']) }}" class="btn btn-primary btn-sm">Extend</a></td> {{-- Go to the form --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- Nothing rented today --}}
                    <tr><td colspan="5" class="text-muted">{{ $tenantName === '' ? 'No garage is rented today.' : 'No garage is rented today by a renter named "' . $tenantName . '".' }}</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the box --}}
@endsection {{-- End of this page's content --}}
