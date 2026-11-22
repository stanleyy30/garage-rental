@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Extend Rental') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Extend Rental</h1> {{-- Page heading --}}
<p>Garage <strong>{{ $garage }}</strong>, ends on <strong>{{ $endDate }}</strong>. Your vehicle stays in this same garage; only the end date moves.</p> {{-- Which rental, and that the garage doesn't change --}}
<p class="text-muted">In the app you can extend daily (1 to {{ $maxDailyDays }} days) or monthly ({{ $monthlyDays }} days). For any other length, ask the admin.</p> {{-- The booking limits --}}

<div class="card shadow-sm"> {{-- Form box --}}
    <div class="card-body"> {{-- Padding --}}
        <form method="POST" action="{{ route('tenant.extensions.preview', $rentalId) }}" class="row g-3"> {{-- Sends to the summary step --}}
            @csrf {{-- Anti-forgery token --}}
            <div class="col-md-6"> {{-- Type column --}}
                <label for="rental_type" class="form-label">Extension type</label> {{-- Label --}}
                <select id="rental_type" name="rental_type" class="form-select" required> {{-- Daily / monthly --}}
                    <option value="daily" @selected(old('rental_type') === 'daily')>Daily (1 to {{ $maxDailyDays }} days)</option> {{-- Daily --}}
                    <option value="monthly" @selected(old('rental_type') === 'monthly')>Monthly ({{ $monthlyDays }} days)</option> {{-- Monthly --}}
                </select> {{-- End of the dropdown --}}
            </div> {{-- End of the column --}}
            <div class="col-md-6"><x-form-input name="days" label="Days (daily only)" type="number" min="1" :max="$maxDailyDays" /></div> {{-- Number of days --}}
            <div class="col-12"><button type="submit" class="btn btn-primary">Calculate cost</button></div> {{-- Go to the summary --}}
        </form> {{-- End of the form --}}
    </div> {{-- End of padding --}}
</div> {{-- End of the box --}}
@endsection {{-- End of this page's content --}}
