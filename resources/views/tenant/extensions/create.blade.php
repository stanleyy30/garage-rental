@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Extend Rental') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Extend Rental</h1> {{-- Page heading --}}
<p>Garage <strong>{{ $garage }}</strong>, ends on <strong>{{ $endDate }}</strong>. Your vehicle stays in this same garage; only the end date moves.</p> {{-- Which rental, and that the garage doesn't change --}}
<p class="text-muted">In the app you can extend daily (1 to {{ $maxDailyDays }} days) or monthly ({{ $monthlyDays }} days), counted from your current end date. For any other length, ask the admin.</p> {{-- The booking limits --}}

<div class="card shadow-sm"> {{-- Form box --}}
    <div class="card-body"> {{-- Padding --}}
        <form method="POST" action="{{ route('tenant.extensions.preview', $rentalId) }}" class="row g-3" id="extend-form" novalidate data-from="{{ $endDateIso }}"> {{-- Sends to the summary step; data-from feeds the calendar --}}
            @csrf {{-- Anti-forgery token --}}
            <div class="col-md-6"> {{-- Type column --}}
                <label for="rental_type" class="form-label">Extension type</label> {{-- Label --}}
                <select id="rental_type" name="rental_type" class="form-select @error('rental_type') is-invalid @enderror"> {{-- Daily / monthly --}}
                    <option value="daily" @selected(old('rental_type', 'daily') === 'daily')>Daily (1 to {{ $maxDailyDays }} days)</option> {{-- Daily --}}
                    <option value="monthly" @selected(old('rental_type') === 'monthly')>Monthly ({{ $monthlyDays }} days)</option> {{-- Monthly --}}
                </select> {{-- End of the dropdown --}}
                @error('rental_type') <div class="invalid-feedback">{{ $message }}</div> @enderror {{-- Error text --}}
            </div> {{-- End of the column --}}
            <div class="col-md-6" id="days-box"><x-form-input name="days" label="Number of extra days" type="number" min="1" :max="$maxDailyDays" :placeholder="'1 to ' . $maxDailyDays" /></div> {{-- Number of days, daily only --}}
            <div class="col-12"> {{-- Calendar column --}}
                <div class="rc-box border rounded p-3 bg-white" id="extend-calendar"></div> {{-- Calendar with the current and the new end date --}}
                <div class="form-text fs-6 mt-2" id="period-text"></div> {{-- e.g. "From 30 Nov 2026 to 30 Dec 2026 (30 days)" --}}
            </div> {{-- End of the calendar column --}}
            <div class="col-12"><button type="submit" class="btn btn-primary">Calculate cost</button></div> {{-- Go to the summary --}}
        </form> {{-- End of the form --}}
    </div> {{-- End of padding --}}
</div> {{-- End of the box --}}
@endsection {{-- End of this page's content --}}

@push('scripts') {{-- Script for this page only --}}
<script>
    (function () { // Keep the names inside this block
        const G = window.GarageRental; // Date helpers and the calendar
        const form = document.getElementById('extend-form'); // The extension form
        const from = G.parseDate(form.dataset.from); // Current end date
        const typeSelect = document.getElementById('rental_type'); // Daily / monthly
        const daysBox = document.getElementById('days-box'); // Box around the days field
        const daysInput = document.getElementById('days'); // Number of days
        const periodText = document.getElementById('period-text'); // Line under the calendar
        const calendar = new G.RangeCalendar(document.getElementById('extend-calendar'), { min: from, startLabel: 'Current end date', endLabel: 'New end date', betweenLabel: 'Extra days' }); // Calendar that only shows, nothing to click

        function update() { // Redraws after any change
            const isMonthly = typeSelect.value === 'monthly'; // Monthly chosen?
            daysBox.classList.toggle('d-none', isMonthly); // Days are only asked for a daily extension
            daysInput.disabled = isMonthly; // A hidden field is not sent
            const days = isMonthly ? G.daysInMonth(from) : parseInt(daysInput.value, 10); // Number of extra days
            const end = days > 0 ? G.addDays(from, days) : null; // New end date, if known
            calendar.setRange(from, end); // Mark it on the calendar
            calendar.showMonthOf(end || from); // Show the month of the new end date
            periodText.textContent = end ? 'From ' + G.longDate(from) + ' to ' + G.longDate(end) + ' (' + days + ' extra days)' : 'Enter the number of extra days to see the new end date.'; // Line under the calendar
        } // End of update()

        typeSelect.addEventListener('change', update); // Daily/monthly changed
        daysInput.addEventListener('input', update); // Days typed or arrows used
        update(); // Draw once when the page opens
    })(); // Run it
</script>
@endpush {{-- End of the script --}}
