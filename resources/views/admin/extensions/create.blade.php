@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Extend Garage Rental') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Extend Garage Rental</h1> {{-- Page heading --}}
<p>Garage <strong>{{ $garage }}</strong> rented by <strong>{{ $tenant }}</strong>, ends on <strong>{{ $endDate }}</strong>.</p> {{-- Which rental --}}

<div class="card shadow-sm"> {{-- Form box --}}
    <div class="card-body"> {{-- Padding --}}
        <form method="POST" action="{{ route('admin.extensions.preview', $rentalId) }}" class="row g-3 align-items-end"> {{-- Sends to the summary step --}}
            @csrf {{-- Anti-forgery token --}}
            <div class="col-md-4"><x-form-input name="new_end_date" label="New end date" type="date" :min="$firstNewEndDate" autofocus /></div> {{-- Calendar: days up to the current end date are greyed out --}}
            <div class="col-md-4"><x-form-input name="days" label="Or extra days the tenant asked for" type="number" min="1" max="3650" /></div> {{-- Or any number of days --}}
            <div class="col-md-4 mb-3"><button type="submit" class="btn btn-primary">Calculate cost</button></div> {{-- Go to the summary --}}
        </form> {{-- End of the form --}}
        <p class="form-text mb-1">Choose the new end date, or type the number of extra days. If both are filled in, the date is used.</p> {{-- How to fill the form --}}
        <p class="form-text mb-0">Whole months counted from the current end date are charged at 25 x the daily price; the remaining days are charged daily.</p> {{-- Pricing rule --}}
    </div> {{-- End of padding --}}
</div> {{-- End of the box --}}
@endsection {{-- End of this page's content --}}
