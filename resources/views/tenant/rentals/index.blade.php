@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'My Rentals') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">My Rentals</h1> {{-- Page heading --}}

<div class="card shadow-sm"> {{-- Box for the list --}}
    <div class="table-responsive"> {{-- Scroll wrapper for phones --}}
        <table class="table table-striped mb-0 align-middle"> {{-- Striped table --}}
            <thead> {{-- Table header --}}
                <tr><th>Garage</th><th>Vehicle</th><th>Type</th><th>Period</th><th>Total paid</th><th>Status</th><th></th></tr> {{-- Column names --}}
            </thead> {{-- End of the header --}}
            <tbody> {{-- Table body --}}
                @forelse ($rentals as $rental) {{-- One row per rental from the controller --}}
                    <tr> {{-- Start of the row --}}
                        <td>{{ $rental['garage'] }}</td> {{-- Garage --}}
                        <td>{{ $rental['vehicle'] }}</td> {{-- Vehicle --}}
                        <td>{{ $rental['type'] }}</td> {{-- Daily / Monthly --}}
                        <td>{{ $rental['period'] }}</td> {{-- Start - end --}}
                        <td>{{ $rental['total'] }}</td> {{-- Amount paid --}}
                        <td><span class="badge bg-{{ $rental['statusColor'] }}">{{ $rental['status'] }}</span></td> {{-- Active / Ended --}}
                        <td class="text-end"> {{-- Action cell --}}
                            @if ($rental['waitingExtension']) {{-- A request already waits for the admin --}}
                                <span class="badge bg-warning text-dark">Extension waiting for admin</span> {{-- No second request --}}
                            @elseif ($rental['isActive']) {{-- Only running rentals can be extended --}}
                                <a href="{{ route('tenant.extensions.create', $rental['id']) }}" class="btn btn-outline-primary btn-sm">Extend</a> {{-- Go to the extension form --}}
                            @endif {{-- End of the check --}}
                        </td> {{-- End of the action cell --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- No rentals yet --}}
                    <tr><td colspan="7" class="text-muted">No rentals yet. <a href="{{ route('tenant.rentals.create') }}">Rent a garage</a>.</td></tr> {{-- Empty message with a link --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the box --}}
@endsection {{-- End of this page's content --}}
