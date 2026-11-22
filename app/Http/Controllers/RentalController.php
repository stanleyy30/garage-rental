<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\ExtensionStatus; // The ExtensionStatus enum
use App\Enums\RentalType; // The RentalType enum
use App\Enums\VehicleType; // The VehicleType enum
use App\Http\Controllers\Concerns\CalculatesRentals; // Shared rules
use App\Http\Requests\RentalRequest; // Validated rental form
use App\Http\Requests\StoreRentalRequest; // Validated rental form + payment proof
use App\Models\Garage; // The Garage model
use App\Models\Rental; // The Rental model
use App\Models\Vehicle; // The Vehicle model
use Carbon\Carbon; // Date library (to read the picked start date)
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Http\Request; // The incoming browser request
use Illuminate\Support\Facades\DB; // Database helper (for a transaction)
use Illuminate\Validation\ValidationException; // Error that sends the user back to the form
use Illuminate\View\View; // Return type for "show a page"

class RentalController extends Controller // Rent New Garage and My Rentals (tenant only)
{ // Start of the class
    use CalculatesRentals; // Gives this class every rental rule and formula

    private const DRAFT = 'rental_draft'; // Session key for the details waiting on the confirm page

    private const BRAND_EXAMPLES = ['bicycle' => 'Polygon Monarch', 'motorcycle' => 'Honda Beat', 'car' => 'Toyota Avanza', 'electric_car' => 'Wuling Air ev']; // Grey example per vehicle type

    public function index(Request $request): View // GET /tenant/rentals: the tenant's own rentals
    { // Start of index()
        $rentals = Rental::with(['garage', 'vehicle', 'extensions']) // Load each rental's garage, vehicle and extensions too ...
            ->whereHas('vehicle', fn ($query) => $query->where('user_id', $request->user()->user_id)) // ... only this tenant's vehicles
            ->orderByDesc('start_date') // Newest first
            ->get(); // Run the query

        return $this->page('tenant.rentals.index', [ // Show the list with one ready-made row per rental
            'rentals' => $rentals->map(fn (Rental $rental) => $this->rentalRow($rental)), // Rows
        ]); // End of page()
    } // End of index()

    public function create(Request $request): View // GET /tenant/rentals/create: step 1 (vehicle type) or step 2 (details)
    { // Start of create()
        $vehicleType = VehicleType::tryFrom((string) $request->query('vehicle_type')); // The chosen vehicle type, or null on step 1

        $data = [ // Values every step needs
            'vehicleTypes' => $this->vehicleTypeOptions(), // Dropdown for step 1
            'vehicleType' => $vehicleType?->value, // The chosen type, or null
            'vehicleTypeLabel' => $vehicleType?->label(), // e.g. "Electric Car"
        ]; // End of the shared values

        if ($vehicleType === null) { // Step 1: no type chosen yet ...
            return $this->page('tenant.rentals.create', $data); // ... only the type dropdown
        } // End of step 1

        if ($request->boolean('edit') && $request->session()->has(self::DRAFT)) { // Back from the confirm page with "Change details" ...
            $request->session()->flashInput($request->session()->get(self::DRAFT)); // ... put every value back in the form
        } // End of the edit case

        $this->refreshGarageStatuses(); // Make garage statuses match today's rentals first
        $today = today(); // First day that can be booked
        $startDate = Carbon::parse((string) old('start_date', $today->toDateString())); // Day shown as the start: the old choice or today
        $garages = Garage::with(['rentals' => fn ($query) => $query->whereDate('end_date', '>=', $today)]) // Each garage with its bookings that haven't ended ...
            ->whereIn('type', $this->compatibleGarageTypes($vehicleType)) // ... only garage types this vehicle fits
            ->orderBy('garage_id') // In ID order
            ->get(); // Run the query

        return $this->page('tenant.rentals.create', $data + [ // Step 2: the full form, with ...
            'needsPlate' => $this->needsPlate($vehicleType), // ... the plate field unless it's a bicycle
            'isElectric' => $vehicleType === VehicleType::ElectricCar, // ... battery fields for electric cars
            'brandExample' => self::BRAND_EXAMPLES[$vehicleType->value], // ... a grey example for the brand
            'today' => $today->toDateString(), // ... the first day on the calendar that can be picked
            'startDate' => $startDate->toDateString(), // ... the start day marked on the calendar
            'maxDailyDays' => $this->maxDailyDays($startDate), // ... the daily limit for that month
            'monthlyDays' => $this->monthlyDays($startDate), // ... the length of a monthly rental from that day
            'garageOptions' => $garages->mapWithKeys(fn (Garage $garage) => [$garage->garage_id => $this->garageOptionText($garage)]), // ... the garage dropdown
            'kwhPrices' => json_encode($garages->filter(fn (Garage $garage) => $this->hasCharger($garage->type))->mapWithKeys(fn (Garage $garage) => [$garage->garage_id => $garage->electricity_price_per_kwh])), // ... the charging price of each E garage, for the live calculation
            'garageBookings' => json_encode($garages->mapWithKeys(fn (Garage $garage) => [ // ... and each garage's booked days, so the page can hide busy garages
                $garage->garage_id => $garage->rentals->map(fn (Rental $rental) => [$rental->start_date->toDateString(), $rental->end_date->toDateString()]), // [first day, last day] per booking
            ])), // End of the bookings
        ]); // End of page()
    } // End of create()

    public function preview(RentalRequest $request): RedirectResponse // POST /tenant/rentals/preview: check everything, then show the total
    { // Start of preview()
        $this->calculateRental($request->validated(), $request->user()->user_id); // All checks; any error goes back to the form
        $request->session()->put(self::DRAFT, $request->validated()); // Keep the details for the confirm page

        return redirect()->route('tenant.rentals.preview.show'); // Show the total on its own page, so an error there can come back to it
    } // End of preview()

    public function showPreview(Request $request): View|RedirectResponse // GET /tenant/rentals/preview: the total and the payment form
    { // Start of showPreview()
        $draft = $request->session()->get(self::DRAFT); // Details from the form

        if ($draft === null) { // Nothing to confirm ...
            return redirect()->route('tenant.rentals.create'); // ... start again
        } // End of the empty check

        try { // Check again: a garage may have been booked in the meantime
            $rental = $this->calculateRental($draft, $request->user()->user_id); // All checks and formulas
        } catch (ValidationException $error) { // A rule fails now ...
            return redirect()->route('tenant.rentals.create', ['vehicle_type' => $draft['vehicle_type']]) // ... back to the form ...
                ->withInput($draft)->withErrors($error->errors()); // ... with the values and the reason
        } // End of the check

        return $this->page('tenant.rentals.preview', [ // Show the summary and the payment form
            'summary' => $this->summaryRows($rental), // Label/value rows
            'formData' => $draft, // Every value again, sent back as hidden fields on confirm
            'backUrl' => route('tenant.rentals.create', ['vehicle_type' => $draft['vehicle_type'], 'edit' => 1]), // "Change details" keeps what was typed
        ]); // End of page()
    } // End of showPreview()

    public function store(StoreRentalRequest $request): RedirectResponse // POST /tenant/rentals: save the rental
    { // Start of store()
        $userId = $request->user()->user_id; // The logged-in tenant
        $rental = $this->calculateRental($request->validated(), $userId); // Check and calculate again (never trust the hidden fields)
        $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public'); // Save the image in storage/app/public/payment-proofs

        DB::transaction(function () use ($userId, $rental, $proofPath) { // Vehicle and rental are saved together
            $vehicle = $this->vehicleFor($userId, $rental); // The tenant's vehicle with this plate, or a new one

            Rental::create([ // Save the rental
                'vehicle_id' => $vehicle->vehicle_id, // Which vehicle
                'garage_id' => $rental['garage']->garage_id, // Which garage
                'rental_type' => $rental['rentalType'], // Daily or monthly
                'days' => $rental['days'], // Number of days
                'start_date' => $rental['startDate'], // Day picked on the calendar
                'end_date' => $rental['endDate'], // Start + days
                'rental_cost' => $rental['rentalCost'], // Garage cost
                'battery_level_percent' => $rental['batteryPercent'], // Remaining battery (E garages only)
                'kwh_to_full' => $rental['kwhToFull'], // kWh until full (E garages only)
                'charging_cost' => $rental['chargingCost'], // Charging cost
                'total_cost' => $rental['totalCost'], // Total paid
                'payment_proof' => $proofPath, // Where the image was saved
            ]); // End of the rental
        }); // End of the transaction

        $this->refreshGarageStatuses(); // The garage shows as used once the rental covers today
        $request->session()->forget(self::DRAFT); // The details are saved, so the draft is no longer needed

        return redirect()->route('tenant.rentals.index') // Go to My Rentals ...
            ->with('success', "Garage {$rental['garage']->garage_id} is booked from {$this->date($rental['startDate'])} to {$this->date($rental['endDate'])}."); // ... with a green message
    } // End of store()

    private function calculateRental(array $data, int $userId): array // Checks every rule and calculates every amount; throws an error back to the form if a rule fails
    { // Start of calculateRental()
        $startDate = Carbon::parse($data['start_date'])->startOfDay(); // Day picked on the calendar
        $vehicleType = VehicleType::from($data['vehicle_type']); // Vehicle type
        $rentalType = RentalType::from($data['rental_type']); // Daily or monthly
        $garage = Garage::findOrFail($data['garage_id']); // Chosen garage
        $isElectric = $vehicleType === VehicleType::ElectricCar; // Electric car?
        $days = $rentalType === RentalType::Monthly ? $this->monthlyDays($startDate) : (int) $data['days']; // Monthly = days of the start month
        $endDate = $this->endDate($startDate, $days); // Start + days

        $errors = []; // Collected error messages
        if (! in_array($garage->type, $this->compatibleGarageTypes($vehicleType), true)) { // Wrong garage type for this vehicle
            $errors['garage_id'] = "A {$vehicleType->label()} can't use a {$garage->type->label()}."; // Error under the garage field
        } elseif ($busy = $this->rentalsOverlapping($startDate, $endDate)->where('garage_id', $garage->garage_id)->first()) { // Garage already booked on one of these days
            $errors['garage_id'] = "Garage {$garage->garage_id} is booked from {$this->date($busy->start_date)} to {$this->date($busy->end_date)}. Choose another garage or other dates."; // Error under the garage field
        } // End of the garage checks
        if (! $this->isValidDays($rentalType, $days, $startDate)) { // Too many days for a daily rental in this month
            $errors['days'] = "A daily rental starting {$this->date($startDate)} can be 1 to {$this->maxDailyDays($startDate)} days."; // Error under the days field
        } // End of the days check

        $plate = null; // Bicycles have no plate
        if ($this->needsPlate($vehicleType)) { // Every other vehicle has one
            $plate = $this->normalizePlate($data['plate_number'] ?? null); // Written the standard way, or null if it isn't a plate
            $plateError = $plate === null ? 'Enter a plate number like L 1234 AB.' : $this->plateProblem($plate, $vehicleType, $userId, $startDate, $endDate); // What is wrong with it, if anything
            if ($plateError !== null) { // Something is wrong ...
                $errors['plate_number'] = $plateError; // ... show it under the plate field
            } // End of the plate error
        } // End of the plate checks

        $chargesHere = $this->hasCharger($garage->type); // Charging happens only in an E garage
        $capacity = $isElectric ? (float) $data['battery_capacity_kwh'] : null; // Battery size (max kWh), electric cars only
        $remainingKwh = $chargesHere && isset($data['remaining_kwh']) ? (float) $data['remaining_kwh'] : null; // kWh left in the battery, only when charging
        if (! $this->isValidBattery($garage->type, $remainingKwh, $capacity)) { // Battery numbers wrong
            $errors['remaining_kwh'] = 'Enter the kWh left in the battery: from 0 up to less than the battery size' . ($capacity ? " ({$capacity} kWh)" : '') . '.'; // Error under the kWh field
        } // End of the battery check

        if ($errors !== []) { // Any rule failed ...
            throw ValidationException::withMessages($errors); // ... go back to the form and show the errors
        } // End of the error check

        $batteryPercent = $chargesHere ? $this->batteryPercent($remainingKwh, $capacity) : null; // How full the battery is, worked out from the kWh left
        $kwhToFull = $chargesHere ? $this->kwhToFull($remainingKwh, $capacity) : null; // kWh needed to fill it
        $rentalCost = $this->rentalCost($rentalType, $garage->daily_price, $days); // Garage cost
        $chargingCost = $this->chargingCost($garage->type, $kwhToFull, $garage->electricity_price_per_kwh); // Charging cost

        return [ // Everything the preview and the save need
            'vehicleType' => $vehicleType, // Vehicle type
            'plateNumber' => $plate, // Plate
            'brandModel' => $data['brand_model'], // Brand and model
            'batteryCapacity' => $capacity, // Max kWh
            'rentalType' => $rentalType, // Daily or monthly
            'days' => $days, // Number of days
            'garage' => $garage, // Garage
            'startDate' => $startDate, // Start date
            'endDate' => $endDate, // End date
            'remainingKwh' => $remainingKwh, // kWh left in the battery
            'batteryPercent' => $batteryPercent, // Remaining battery in percent
            'kwhToFull' => $kwhToFull, // kWh until full
            'rentalCost' => $rentalCost, // Garage cost
            'chargingCost' => $chargingCost, // Charging cost
            'totalCost' => $rentalCost + $chargingCost, // Total
        ]; // End of the result
    } // End of calculateRental()

    private function plateProblem(string $plate, VehicleType $vehicleType, int $userId, Carbon $startDate, Carbon $endDate): ?string // Why this plate can't be booked, or null if it can
    { // Start of plateProblem()
        if (Vehicle::where('plate_number', $plate)->where('user_id', '!=', $userId)->exists()) { // Another tenant's vehicle has this plate ...
            return "Plate {$plate} is registered to another tenant's vehicle."; // ... every vehicle has its own plate
        } // End of the owner check

        $vehicle = Vehicle::where('plate_number', $plate)->where('user_id', $userId)->first(); // This tenant's vehicle with this plate, if rented before
        if ($vehicle !== null && $vehicle->type !== $vehicleType) { // Same tenant, but a different kind of vehicle ...
            return "Plate {$plate} is already registered as your {$vehicle->type->label()}."; // ... one plate, one vehicle
        } // End of the type check

        $booked = $this->rentalsOverlapping($startDate, $endDate) // Rentals on these days ...
            ->whereHas('vehicle', fn ($query) => $query->where('plate_number', $plate)) // ... for this plate
            ->first(); // The first one, if any

        return $booked === null ? null : "The vehicle with plate {$plate} already has a garage from {$this->date($booked->start_date)} to {$this->date($booked->end_date)}."; // One garage per vehicle at a time
    } // End of plateProblem()

    private function vehicleFor(int $userId, array $rental): Vehicle // The tenant's vehicle with this plate (updated), or a new vehicle
    { // Start of vehicleFor()
        $details = [ // Values that may change between rentals
            'brand_model' => $rental['brandModel'], // Brand and model
            'battery_capacity_kwh' => $rental['batteryCapacity'], // Max kWh (electric cars only)
        ]; // End of the details

        $vehicle = $rental['plateNumber'] === null ? null : Vehicle::where('user_id', $userId)->where('plate_number', $rental['plateNumber'])->first(); // Same plate rented before (bicycles have no plate)

        if ($vehicle !== null) { // Rented before ...
            $vehicle->update($details); // ... keep one vehicle per plate, with the latest details
            return $vehicle; // Use it
        } // End of the reuse case

        return Vehicle::create($details + [ // A new vehicle
            'user_id' => $userId, // Owner = logged-in tenant
            'type' => $rental['vehicleType'], // Vehicle type
            'plate_number' => $rental['plateNumber'], // Plate (empty for bicycles)
        ]); // End of create()
    } // End of vehicleFor()

    private function garageOptionText(Garage $garage): string // Text of one garage in the dropdown
    { // Start of garageOptionText()
        $text = $garage->garage_id . ' - ' . $garage->type->label() . ' - ' . $this->rupiah($garage->daily_price) . ' per day'; // e.g. "C001 - Car Garage - Rp 50.000 per day"

        return $this->hasCharger($garage->type) ? $text . ' + charging ' . $this->rupiah($garage->electricity_price_per_kwh) . ' per kWh' : $text; // Charging price for E garages
    } // End of garageOptionText()

    private function summaryRows(array $rental): array // Label/value rows for the preview page
    { // Start of summaryRows()
        $price = $this->rupiah($rental['garage']->daily_price); // Daily price of the garage
        $rows = [ // Rows shown for every rental
            ['label' => 'Vehicle', 'value' => $this->vehicleLabel($rental['brandModel'], $rental['plateNumber']) . ', ' . $rental['vehicleType']->label()], // e.g. Toyota Avanza (L 1234 AB), Car
            ['label' => 'Garage', 'value' => $rental['garage']->garage_id . ' (' . $rental['garage']->type->label() . ')'], // e.g. C001 (Car Garage)
            ['label' => 'Daily price', 'value' => $price . ' per day'], // Price of one day
            ['label' => 'Rental type', 'value' => $rental['rentalType']->label() . ', ' . $rental['days'] . ' days'], // e.g. Daily, 5 days
            ['label' => 'Start date', 'value' => $this->date($rental['startDate'])], // Day picked
            ['label' => 'End date', 'value' => $this->date($rental['endDate'])], // Start + days
            ['label' => 'Garage cost', 'value' => ($rental['rentalType'] === RentalType::Monthly ? self::MONTHLY_MULTIPLIER : $rental['days']) . ' x ' . $price . ' = ' . $this->rupiah($rental['rentalCost'])], // e.g. 5 x Rp 50.000 = Rp 250.000 (a month is 25 x)
        ]; // End of the common rows

        if ($this->hasCharger($rental['garage']->type)) { // Charging rows only for an E garage
            $rows[] = ['label' => 'Battery', 'value' => $rental['remainingKwh'] . ' of ' . $rental['batteryCapacity'] . ' kWh left (' . $rental['batteryPercent'] . '%), ' . $rental['kwhToFull'] . ' kWh until full']; // Battery details, worked out from the kWh left
            $rows[] = ['label' => 'Charging cost', 'value' => $rental['kwhToFull'] . ' kWh x ' . $this->rupiah($rental['garage']->electricity_price_per_kwh) . ' = ' . $this->rupiah($rental['chargingCost'])]; // Charging cost
        } // End of the charging rows

        $rows[] = ['label' => 'Total to pay', 'value' => $this->rupiah($rental['totalCost'])]; // Total, always last

        return $rows; // The finished rows
    } // End of summaryRows()

    private function rentalRow(Rental $rental): array // One row for the My Rentals table
    { // Start of rentalRow()
        $status = match (true) { // Where the rental is in time
            $rental->start_date->gt(today()) => ['Upcoming', 'info'], // Starts later
            $rental->end_date->gte(today()) => ['Active', 'success'], // Running today
            default => ['Ended', 'secondary'], // Over
        }; // End of match
        $latest = $rental->extensions->sortByDesc('extension_id')->first(); // Newest extension of this rental, if any

        return [ // Ready-made values
            'id' => $rental->rental_id, // For the Extend button
            'garage' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')', // Garage
            'vehicle' => $this->vehicleLabel($rental->vehicle->brand_model, $rental->vehicle->plate_number), // Vehicle
            'type' => $rental->rental_type->label(), // Daily / Monthly
            'period' => $this->date($rental->start_date) . ' - ' . $this->date($rental->end_date), // Start - end
            'total' => $this->rupiah($rental->total_cost), // Amount paid
            'status' => $status[0], // Status text
            'statusColor' => $status[1], // Badge colour
            'canExtend' => $rental->end_date->gte(today()) && $latest?->status !== ExtensionStatus::Pending, // Not ended and no request waiting
            'notice' => $latest ? $this->extensionNotice($latest) : null, // What happened to the latest extension
        ]; // End of the row
    } // End of rentalRow()

    private function vehicleTypeOptions(): array // Dropdown options: value => label
    { // Start of vehicleTypeOptions()
        $options = []; // Empty list to fill
        foreach (VehicleType::cases() as $type) { // Every vehicle type
            $options[$type->value] = $type->label(); // 'car' => 'Car'
        } // End of the loop

        return $options; // The finished list
    } // End of vehicleTypeOptions()
} // End of the class
