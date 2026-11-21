@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Rent a Garage') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Rent a Garage</h1> {{-- Page heading --}}

<div class="card shadow-sm mb-4"> {{-- Step 1 box --}}
    <div class="card-body"> {{-- Padding --}}
        <form method="GET" action="{{ route('tenant.rentals.create') }}" class="row g-2 align-items-end"> {{-- GET form: reloads this page with the chosen type --}}
            <div class="col-md-6"> {{-- Column for the dropdown --}}
                <label for="vehicle_type_pick" class="form-label">1. Vehicle type</label> {{-- Label --}}
                <select id="vehicle_type_pick" name="vehicle_type" class="form-select" required> {{-- Vehicle type dropdown --}}
                    @foreach ($vehicleTypes as $value => $label) {{-- One option per type from the controller --}}
                        <option value="{{ $value }}" @selected($vehicleType === $value)>{{ $label }}</option> {{-- Keeps the current choice selected --}}
                    @endforeach {{-- End of the options --}}
                </select> {{-- End of the dropdown --}}
            </div> {{-- End of the column --}}
            <div class="col-md-3"><button type="submit" class="btn btn-outline-primary w-100">Show garages</button></div> {{-- Reload with this type --}}
        </form> {{-- End of step 1 --}}
    </div> {{-- End of padding --}}
</div> {{-- End of step 1 box --}}

@if ($vehicleType) {{-- Step 2 appears after a vehicle type was chosen --}}
<div class="card shadow-sm"> {{-- Step 2 box --}}
    <div class="card-header fw-bold">2. Rental details for a {{ $vehicleTypeLabel }}</div> {{-- Box title --}}
    <div class="card-body"> {{-- Padding --}}
        @if (count($garageOptions) === 0) {{-- The controller found no free garage for this vehicle --}}
            <div class="alert alert-warning mb-0">No garage is available for this vehicle today.</div> {{-- Nothing to rent --}}
        @else {{-- At least one free garage --}}
        <form method="POST" action="{{ route('tenant.rentals.preview') }}" class="row g-3"> {{-- Sends the details to the "Calculate total" step --}}
            @csrf {{-- Anti-forgery token --}}
            <input type="hidden" name="vehicle_type" value="{{ $vehicleType }}"> {{-- Carries the chosen type --}}

            @if ($needsPlate) {{-- Every vehicle except a bicycle has a plate --}}
                <div class="col-md-4"><x-form-input name="plate_number" label="Plate number" maxlength="12" required /></div> {{-- Plate --}}
            @endif {{-- End of the plate field --}}
            <div class="col-md-8"><x-form-input name="brand_model" label="Brand and model" maxlength="100" required /></div> {{-- Brand and model --}}
            @if ($isElectric) {{-- Electric cars also give their battery size --}}
                <div class="col-md-4"><x-form-input name="battery_capacity_kwh" label="Battery size (max kWh)" type="number" step="0.01" min="1" required /></div> {{-- Max kWh --}}
            @endif {{-- End of the battery size field --}}

            <div class="col-md-4"> {{-- Rental type column --}}
                <label for="rental_type" class="form-label">Rental type</label> {{-- Label --}}
                <select id="rental_type" name="rental_type" class="form-select" required> {{-- Daily / monthly --}}
                    <option value="daily" @selected(old('rental_type') === 'daily')>Daily (1 to {{ $maxDailyDays }} days)</option> {{-- Daily, with this month's limit --}}
                    <option value="monthly" @selected(old('rental_type') === 'monthly')>Monthly ({{ $monthlyDays }} days)</option> {{-- Monthly, with this month's length --}}
                </select> {{-- End of the dropdown --}}
            </div> {{-- End of the column --}}
            <div class="col-md-4"><x-form-input name="days" label="Days (daily only)" type="number" min="1" :max="$maxDailyDays" /></div> {{-- Number of days --}}
            <div class="col-md-4"><label class="form-label">Start date</label><input type="text" class="form-control" value="{{ $startDate }}" readonly></div> {{-- Always today, read-only --}}

            <div class="col-12"> {{-- Garage column --}}
                <label for="garage_id" class="form-label">Garage (available today)</label> {{-- Label --}}
                <select id="garage_id" name="garage_id" class="form-select @error('garage_id') is-invalid @enderror" required> {{-- Garages from the controller --}}
                    @foreach ($garageOptions as $id => $text) {{-- One option per free, fitting garage --}}
                        <option value="{{ $id }}" @selected(old('garage_id') === $id)>{{ $text }}</option> {{-- e.g. "C001 - Car Garage - Rp 50.000 per day" --}}
                    @endforeach {{-- End of the options --}}
                </select> {{-- End of the dropdown --}}
                @error('garage_id') <div class="invalid-feedback">{{ $message }}</div> @enderror {{-- Error text --}}
            </div> {{-- End of the garage column --}}

            @if ($isElectric) {{-- Charging fields for electric cars --}}
                <div class="col-12"><div class="form-text">Fill these in only if you chose an electric car garage (E…), where the battery is charged once.</div></div> {{-- Help text --}}
                <div class="col-md-6"><x-form-input name="battery_level_percent" label="Remaining battery (%)" type="number" min="0" max="99" /></div> {{-- Battery % --}}
                <div class="col-md-6"><x-form-input name="kwh_to_full" label="kWh until full" type="number" step="0.01" min="0" /></div> {{-- kWh to full --}}
            @endif {{-- End of the charging fields --}}

            <div class="col-12"><button type="submit" class="btn btn-primary">Calculate total</button></div> {{-- Go to the summary --}}
        </form> {{-- End of step 2 --}}
        @endif {{-- End of the garage check --}}
    </div> {{-- End of padding --}}
</div> {{-- End of step 2 box --}}
@endif {{-- End of step 2 --}}
@endsection {{-- End of this page's content --}}
