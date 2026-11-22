@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Rent a Garage') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-1">Rent a Garage</h1> {{-- Page heading --}}
<p class="text-muted">You can rent more than one garage at the same time, of the same type or of different types. Each vehicle needs its own garage.</p> {{-- Several garages at once --}}

<div class="card shadow-sm mb-4"> {{-- Step 1 box --}}
    <div class="card-body"> {{-- Padding --}}
        <form method="GET" action="{{ route('tenant.rentals.create') }}" class="row g-2 align-items-end"> {{-- GET form: reloads this page with the chosen type --}}
            <div class="col-md-6"> {{-- Column for the dropdown --}}
                <label for="vehicle_type_pick" class="form-label">1. Vehicle type</label> {{-- Label --}}
                <select id="vehicle_type_pick" name="vehicle_type" class="form-select"> {{-- Vehicle type dropdown --}}
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
    <div class="card-header fw-bold">2. Rental details: {{ $vehicleTypeLabel }}</div> {{-- Box title with the chosen vehicle type --}}
    <div class="card-body"> {{-- Padding --}}
        @if (count($garageOptions) === 0) {{-- The controller found no garage for this vehicle --}}
            <div class="alert alert-warning mb-0">There is no garage for this vehicle yet.</div> {{-- Nothing to rent --}}
        @else {{-- At least one garage --}}
        <form method="POST" action="{{ route('tenant.rentals.preview') }}" class="row g-3" id="rent-form" novalidate data-today="{{ $today }}" data-bookings="{{ $garageBookings }}" data-kwh-prices="{{ $kwhPrices }}"> {{-- Sends the details to the "Calculate total" step; the data- values feed the calendar --}}
            @csrf {{-- Anti-forgery token --}}
            <input type="hidden" name="vehicle_type" value="{{ $vehicleType }}"> {{-- Carries the chosen type --}}

            @if ($needsPlate) {{-- Every vehicle except a bicycle has a plate --}}
                <div class="col-md-4"><x-form-input name="plate_number" label="Plate number" maxlength="15" placeholder="L 1234 AB" hint="Each vehicle has its own plate number." /></div> {{-- Plate --}}
            @endif {{-- End of the plate field --}}
            <div class="col-md-8"><x-form-input name="brand_model" label="Brand and model" maxlength="100" :placeholder="$brandExample" /></div> {{-- Brand and model --}}
            @if ($isElectric) {{-- Electric cars also give their battery size --}}
                <div class="col-md-4"><x-form-input name="battery_capacity_kwh" label="Battery size (max kWh)" type="number" step="0.01" min="1" placeholder="26.7" /></div> {{-- Max kWh --}}
            @endif {{-- End of the battery size field --}}

            <div class="col-md-4"> {{-- Rental type column --}}
                <label for="rental_type" class="form-label">Rental type</label> {{-- Label --}}
                <select id="rental_type" name="rental_type" class="form-select @error('rental_type') is-invalid @enderror"> {{-- Daily / monthly --}}
                    <option value="daily" @selected(old('rental_type', 'daily') === 'daily')>Daily (1 to {{ $maxDailyDays }} days)</option> {{-- Daily; the limit follows the start month --}}
                    <option value="monthly" @selected(old('rental_type') === 'monthly')>Monthly ({{ $monthlyDays }} days)</option> {{-- Monthly; the length follows the start month --}}
                </select> {{-- End of the dropdown --}}
                @error('rental_type') <div class="invalid-feedback">{{ $message }}</div> @enderror {{-- Error text --}}
            </div> {{-- End of the column --}}
            <div class="col-md-4" id="days-box"><x-form-input name="days" label="Number of days" type="number" min="1" :max="$maxDailyDays" :placeholder="'1 to ' . $maxDailyDays" /></div> {{-- Number of days, daily only --}}

            <div class="col-12"> {{-- Calendar column --}}
                <label class="form-label">Start date: pick a day on the calendar</label> {{-- Label --}}
                <input type="hidden" id="start_date" name="start_date" value="{{ old('start_date', $startDate) }}"> {{-- The picked day is sent from here --}}
                <div class="rc-box border rounded p-3 bg-white @error('start_date') border-danger @enderror" id="rent-calendar"></div> {{-- The calendar is drawn here --}}
                <div class="form-text fs-6 mt-2" id="period-text"></div> {{-- e.g. "22 Nov 2026 to 21 Dec 2026 (29 days)" --}}
                @error('start_date') <div class="text-danger small">{{ $message }}</div> @enderror {{-- Error text --}}
            </div> {{-- End of the calendar column --}}

            <div class="col-12"> {{-- Garage column --}}
                <label for="garage_id" class="form-label">Garage (garages booked on your dates are greyed out)</label> {{-- Label --}}
                <select id="garage_id" name="garage_id" class="form-select @error('garage_id') is-invalid @enderror"> {{-- Garages from the controller --}}
                    @foreach ($garageOptions as $id => $text) {{-- One option per garage that fits the vehicle --}}
                        <option value="{{ $id }}" @selected(old('garage_id') === $id)>{{ $text }}</option> {{-- e.g. "C001 - Car Garage - Rp 50.000 per day" --}}
                    @endforeach {{-- End of the options --}}
                </select> {{-- End of the dropdown --}}
                @error('garage_id') <div class="invalid-feedback">{{ $message }}</div> @enderror {{-- Error text --}}
            </div> {{-- End of the garage column --}}

            @if ($isElectric) {{-- Charging part for electric cars --}}
                <div class="col-12 d-none" id="charging-box"> {{-- Shown only after an electric car garage (E...) is chosen --}}
                    <div class="rc-box border rounded p-3 bg-white"> {{-- Box around the charging part --}}
                        <div class="fw-semibold mb-2">Battery charging (electric car garage)</div> {{-- Title --}}
                        <p class="form-text mt-0">This garage charges your battery once until it is full. Enter how many kWh are left in the battery; the app works out the percent and the kWh it needs.</p> {{-- What happens --}}
                        <div class="row g-3 align-items-end"> {{-- Field and result side by side --}}
                            <div class="col-md-5"><x-form-input name="remaining_kwh" label="kWh left in the battery" type="number" step="0.01" min="0" placeholder="6.7" /></div> {{-- kWh left --}}
                            <div class="col-md-7"><div class="alert alert-info mb-3 py-2" id="charging-text">Enter the battery size and the kWh left.</div></div> {{-- e.g. "25% left, 20 kWh until full, charging Rp 50.000" --}}
                        </div> {{-- End of the row --}}
                    </div> {{-- End of the box --}}
                </div> {{-- End of the charging part --}}
            @endif {{-- End of the charging part --}}

            <div class="col-12"><button type="submit" class="btn btn-primary">Calculate total</button></div> {{-- Go to the summary --}}
        </form> {{-- End of step 2 --}}
        @endif {{-- End of the garage check --}}
    </div> {{-- End of padding --}}
</div> {{-- End of step 2 box --}}
@endif {{-- End of step 2 --}}
@endsection {{-- End of this page's content --}}

@push('scripts') {{-- Script for this page only --}}
<script>
    (function () { // Keep the names inside this block
        const form = document.getElementById('rent-form'); // The rental form
        if (!form) { // Step 1 or no garages: nothing to wire up
            return; // Stop here
        } // End of the check
        const G = window.GarageRental; // Date helpers and the calendar
        const startInput = document.getElementById('start_date'); // Hidden start date
        const typeSelect = document.getElementById('rental_type'); // Daily / monthly
        const daysBox = document.getElementById('days-box'); // Box around the days field
        const daysInput = document.getElementById('days'); // Number of days
        const garageSelect = document.getElementById('garage_id'); // Garage dropdown
        const periodText = document.getElementById('period-text'); // Line under the calendar
        const bookings = JSON.parse(form.dataset.bookings); // Booked days per garage
        const allGarages = Array.from(garageSelect.options).map((option) => ({ id: option.value, text: option.text })); // Every garage from the server
        const wanted = garageSelect.value; // The garage chosen before, if any

        const calendar = new G.RangeCalendar(document.getElementById('rent-calendar'), { // Draw the calendar ...
            min: G.parseDate(form.dataset.today), // ... days before today are greyed out
            startLabel: 'Start date', // ... legend for the first day
            endLabel: 'End date', // ... legend for the last day
            onPick: (day) => { startInput.value = G.isoDate(day); update(); }, // ... clicking a day makes it the start date
        }); // End of the calendar
        calendar.showMonthOf(G.parseDate(startInput.value) || calendar.min); // Open on the month of the start date

        function bookingLength(start) { // Number of days of the booking, or null while the days are still empty
            if (typeSelect.value === 'monthly') { // Monthly ...
                return G.daysInMonth(start); // ... as many days as the start month
            } // End of the monthly case
            const days = parseInt(daysInput.value, 10); // Days typed in
            return days > 0 ? days : null; // Only a positive number counts
        } // End of bookingLength()

        function showFreeGarages(firstDay, lastDay) { // Free garages can be picked; booked ones stay in the list, greyed out with their dates
            const chosen = garageSelect.value || wanted; // Keep the current choice when it is still free
            garageSelect.innerHTML = ''; // Empty the dropdown
            let freeCount = 0; // How many garages can be picked
            allGarages.forEach((garage) => { // Every garage that fits the vehicle
                const busy = (bookings[garage.id] || []).find((booked) => booked[0] < lastDay && booked[1] > firstDay); // A booking that uses the garage on these nights (the end date is the day the vehicle leaves)
                const text = busy ? garage.text + ' (booked ' + G.longDate(G.parseDate(busy[0])) + ' to ' + G.longDate(G.parseDate(busy[1])) + ')' : garage.text; // Booked garages show when they are taken
                const option = new Option(text, garage.id, false, !busy && garage.id === chosen); // One option
                option.disabled = Boolean(busy); // Greyed out when booked
                garageSelect.add(option); // Add it
                freeCount += busy ? 0 : 1; // Count the free ones
            }); // End of the garages
            if (freeCount === 0) { // Every garage is busy on these days
                garageSelect.add(new Option('No garage is free on these days. Try other dates.', '', true, true)); // Say so
            } else if (garageSelect.selectedOptions[0]?.disabled) { // The browser kept a greyed-out garage selected
                garageSelect.value = Array.from(garageSelect.options).find((option) => !option.disabled).value; // Pick the first free one instead
            } // End of the empty case
        } // End of showFreeGarages()

        function update() { // Redraws everything after any change
            const start = G.parseDate(startInput.value) || calendar.min; // The start date
            const monthDays = G.daysInMonth(start); // Length of the start month
            const isMonthly = typeSelect.value === 'monthly'; // Monthly chosen?
            typeSelect.options[0].text = 'Daily (1 to ' + (monthDays - 1) + ' days)'; // Daily limit for this month
            typeSelect.options[1].text = 'Monthly (' + monthDays + ' days)'; // Monthly length for this month
            daysInput.max = monthDays - 1; // The arrows stop at the limit
            daysInput.placeholder = '1 to ' + (monthDays - 1); // Grey hint with the limit
            daysBox.classList.toggle('d-none', isMonthly); // Days are only asked for a daily rental
            daysInput.disabled = isMonthly; // A hidden field is not sent
            const length = bookingLength(start); // Number of days, if known
            const end = length ? G.addDays(start, length) : null; // End date, if known
            calendar.setRange(start, end); // Mark the booking on the calendar
            periodText.textContent = end // Line under the calendar
                ? G.longDate(start) + ' to ' + G.longDate(end) + ' (' + length + ' days)' // Whole period
                : 'Starts ' + G.longDate(start) + '. Enter the number of days to see the end date.'; // Days still missing
            showFreeGarages(G.isoDate(start), G.isoDate(end || G.addDays(start, 1))); // Garages free on every night from the start to the end date
            form.dispatchEvent(new Event('garages-updated')); // Let the charging part check the chosen garage again
        } // End of update()

        typeSelect.addEventListener('change', update); // Daily/monthly changed
        daysInput.addEventListener('input', update); // Days typed or arrows used
        const chargingBox = document.getElementById('charging-box'); // Charging part (electric cars only)
        if (chargingBox) { // This is an electric car
            const prices = JSON.parse(form.dataset.kwhPrices); // Charging price per kWh of each E garage
            const capacityInput = document.getElementById('battery_capacity_kwh'); // Battery size
            const remainingInput = document.getElementById('remaining_kwh'); // kWh left
            const chargingText = document.getElementById('charging-text'); // Result line
            const showCharging = () => { // Shows the charging part only for an E garage and works out the numbers
                const price = prices[garageSelect.value]; // Price per kWh, only E garages have one
                chargingBox.classList.toggle('d-none', price === undefined); // Hide it for a C garage
                remainingInput.disabled = price === undefined; // A hidden field is not sent
                const capacity = parseFloat(capacityInput.value); // Battery size
                const remaining = parseFloat(remainingInput.value); // kWh left
                if (!(capacity > 0) || !(remaining >= 0) || remaining >= capacity) { // Numbers missing or not possible
                    chargingText.textContent = 'Enter the battery size and the kWh left (less than the battery size).'; // Say what is needed
                    return; // Nothing to work out yet
                } // End of the check
                const toFull = Math.round((capacity - remaining) * 100) / 100; // kWh until full
                const percent = Math.min(99, Math.floor(remaining / capacity * 100)); // Percent left, rounded down
                chargingText.textContent = percent + '% left, ' + toFull + ' kWh until full, charging Rp ' + Math.round(toFull * price).toLocaleString('id-ID'); // e.g. "25% left, 20 kWh until full, charging Rp 50.000"
            }; // End of showCharging()
            [garageSelect, capacityInput, remainingInput].forEach((field) => field.addEventListener('input', showCharging)); // Recalculate when any of them changes
            garageSelect.addEventListener('change', showCharging); // Dropdowns send "change"
            form.addEventListener('garages-updated', showCharging); // The garage list was rebuilt
            showCharging(); // Once when the page opens
        } // End of the electric car part

        update(); // Draw once when the page opens
    })(); // Run it
</script>
@endpush {{-- End of the script --}}
