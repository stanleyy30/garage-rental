@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Admin Dashboard') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Admin Dashboard</h1> {{-- Page heading --}}
<p>Welcome, {{ $currentUserName }}.</p> {{-- Greeting with the admin's name --}}

<div class="row g-3"> {{-- Row of number cards --}}
    @foreach ($stats as $stat) {{-- One card per number from the controller --}}
        <div class="col-6 col-md-3"> {{-- Two cards per row on phones, four on wider screens --}}
            <div class="card shadow-sm text-center"> {{-- White card --}}
                <div class="card-body"> {{-- Card padding --}}
                    <div class="display-6">{{ $stat['value'] }}</div> {{-- The number, large --}}
                    <div class="text-muted">{{ $stat['label'] }}</div> {{-- What it counts --}}
                </div> {{-- End of card-body --}}
            </div> {{-- End of the card --}}
        </div> {{-- End of the column --}}
    @endforeach {{-- End of the cards --}}
</div> {{-- End of the row --}}
@endsection {{-- End of this page's content --}}
