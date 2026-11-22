@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Extend Garage Rental') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Extend Garage Rental</h1> {{-- Page heading --}}
<p>Garage <strong>{{ $garage }}</strong> rented by <strong>{{ $tenant }}</strong>, ends on <strong>{{ $endDate }}</strong>.</p> {{-- Which rental --}}

<div class="card shadow-sm"> {{-- Form box --}}
    <div class="card-body"> {{-- Padding --}}
        <form method="POST" action="{{ route('admin.extensions.preview', $rentalId) }}" class="row g-3 align-items-start" id="admin-extend-form" novalidate data-from="{{ $endDateIso }}"> {{-- Sends to the summary step; data-from feeds the calendar --}}
            @csrf {{-- Anti-forgery token --}}
            <div class="col-md-4"><x-form-input name="new_end_date" label="New end date" type="date" :min="$firstNewEndDate" autofocus /></div> {{-- Calendar field: days up to the current end date are greyed out --}}
            <div class="col-md-4"><x-form-input name="days" label="Or extra days the tenant asked for" type="number" min="1" max="3650" placeholder="31" /></div> {{-- Or any number of days --}}
            <div class="col-md-4 pt-md-4"><button type="submit" class="btn btn-primary mt-md-2">Calculate cost</button></div> {{-- Go to the summary --}}
            <div class="col-12"> {{-- Calendar column --}}
                <div class="border rounded p-3 bg-white" id="admin-extend-calendar"></div> {{-- Click a day to make it the new end date --}}
                <div class="form-text fs-6 mt-2" id="period-text"></div> {{-- e.g. "From 30 Nov 2026 to 31 Dec 2026 (31 extra days)" --}}
            </div> {{-- End of the calendar column --}}
        </form> {{-- End of the form --}}
        <p class="form-text mb-1">Pick the new end date on the calendar or type the number of extra days; the other one follows.</p> {{-- How to fill the form --}}
        <p class="form-text mb-0">Whole months counted from the current end date are charged at 25 x the daily price; the remaining days are charged daily.</p> {{-- Pricing rule --}}
    </div> {{-- End of padding --}}
</div> {{-- End of the box --}}
@endsection {{-- End of this page's content --}}

@push('scripts') {{-- Script for this page only --}}
<script>
    (function () { // Keep the names inside this block
        const G = window.GarageRental; // Date helpers and the calendar
        const form = document.getElementById('admin-extend-form'); // The extension form
        const from = G.parseDate(form.dataset.from); // Current end date
        const dateInput = document.getElementById('new_end_date'); // New end date field
        const daysInput = document.getElementById('days'); // Extra days field
        const periodText = document.getElementById('period-text'); // Line under the calendar
        const calendar = new G.RangeCalendar(document.getElementById('admin-extend-calendar'), { // Draw the calendar ...
            min: G.addDays(from, 1), // ... the first day after the current end date is the earliest new end date
            startLabel: 'Current end date', // ... legend for the first marked day
            endLabel: 'New end date', // ... legend for the last marked day
            betweenLabel: 'Extra days', // ... legend for the days in between
            onPick: (day) => { dateInput.value = G.isoDate(day); fromDate(); }, // ... clicking a day makes it the new end date
        }); // End of the calendar
        calendar.showMonthOf(from); // Open on the month of the current end date

        function draw(end) { // Marks the period and writes the line under the calendar
            const days = end ? G.daysBetween(from, end) : 0; // Extra days
            calendar.setRange(from, end && days > 0 ? end : null); // Mark it
            periodText.textContent = end && days > 0 ? 'From ' + G.longDate(from) + ' to ' + G.longDate(end) + ' (' + days + ' extra days)' : 'Pick the new end date or type the number of extra days.'; // Line under the calendar
        } // End of draw()

        function fromDate() { // The date was changed: work out the days
            const end = G.parseDate(dateInput.value); // Picked date
            daysInput.value = end && G.daysBetween(from, end) > 0 ? G.daysBetween(from, end) : ''; // Days between the two dates
            draw(end); // Show it
        } // End of fromDate()

        function fromDays() { // The days were changed: work out the date
            const days = parseInt(daysInput.value, 10); // Days typed in
            const end = days > 0 ? G.addDays(from, days) : null; // Current end date + days
            dateInput.value = end ? G.isoDate(end) : ''; // Fill in the date
            if (end) { // A date is known
                calendar.showRange(from, end); // Show it, with the current end date too when both fit
            } // End of the month move
            draw(end); // Show it
        } // End of fromDays()

        dateInput.addEventListener('change', fromDate); // Date picked in the field
        daysInput.addEventListener('input', fromDays); // Days typed or arrows used
        if (dateInput.value) { fromDate(); } else { fromDays(); } // Draw once with whatever came back from the server
    })(); // Run it
</script>
@endpush {{-- End of the script --}}
