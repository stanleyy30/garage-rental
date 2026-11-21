@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Extend Garage Rental') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Extend Garage Rental</h1> {{-- Page heading --}}
<p class="text-muted">Garages rented today. Choose one to extend by the number of days the tenant asked for.</p> {{-- Instruction --}}

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
                    <tr><td colspan="5" class="text-muted">No garage is rented today.</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the box --}}
@endsection {{-- End of this page's content --}}
