<?php // Start of a PHP file

namespace App\Http\Controllers; // This file lives in app/Http/Controllers

use App\Enums\ExtensionStatus; // The ExtensionStatus enum
use App\Enums\GarageStatus; // The GarageStatus enum
use App\Enums\RentalType; // The RentalType enum
use App\Enums\VehicleType; // The VehicleType enum
use App\Http\Controllers\Concerns\CalculatesRentals; // Shared rules
use App\Http\Requests\RentalRequest; // Validated rental form
use App\Http\Requests\StoreRentalRequest; // Validated rental form + payment proof
use App\Models\Garage; // The Garage model
use App\Models\Rental; // The Rental model
use App\Models\Vehicle; // The Vehicle model
use Illuminate\Http\RedirectResponse; // Return type for "go to another page"
use Illuminate\Http\Request; // The incoming browser request
use Illuminate\Support\Facades\DB; // Database helper (for a transaction)
use Illuminate\Validation\ValidationException; // Error that sends the user back to the form
use Illuminate\View\View; // Return type for "show a page"

class RentalController extends Controller // Rent New Garage and My Rentals (tenant only)
{ // Start of the class
    use CalculatesRentals; // Gives this class every rental rule and formula

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
        $today = today(); // Rentals always start today

        $data = [ // Values every step needs
            'vehicleTypes' => $this->vehicleTypeOptions(), // Dropdown for step 1
            'vehicleType' => $vehicleType?->value, // The chosen type, or null
            'vehicleTypeLabel' => $vehicleType?->label(), // e.g. "Electric Car"
        ]; // End of the shared values

        if ($vehicleType === null) { // Step 1: no type chosen yet ...
            return $this->page('tenant.rentals.create', $data); // ... only the type dropdown
        } // End of step 1

        $this->refreshGarageStatuses(); // Make statuses match today's rentals first

        $garages = Garage::whereIn('type', $this->compatibleGarageTypes($vehicleType)) // Garage types this vehicle fits ...
            ->where('status', GarageStatus::Available) // ... that are free today
            ->orderBy('garage_id') // In ID order
            ->get(); // Run the query

        return $this->page('tenant.rentals.create', $data + [ // Step 2: the full form, with ...
            'needsPlate' => $this->needsPlate($vehicleType), // ... the plate field unless it's a bicycle
            'isElectric' => $vehicleType === VehicleType::ElectricCar, // ... battery fields for electric cars
            'startDate' => $today->toDateString(), // ... today's date for the date picker (YYYY-MM-DD); it is also the only day allowed
            'maxDailyDays' => $this->maxDailyDays($today), // ... the daily limit this month
            'monthlyDays' => $this->monthlyDays($today), // ... the length of a monthly rental from today
            'garageOptions' => $garages->mapWithKeys(fn (Garage $garage) => [ // ... and the garage dropdown
                $garage->garage_id => $garage->garage_id . ' - ' . $garage->type->label() . ' - ' . $this->rupiah($garage->daily_price) . ' per day' // e.g. "E001 - Electric Car Garage - Rp 70.000 per day"
                    . ($this->hasCharger($garage->type) ? ' + charging ' . $this->rupiah($garage->electricity_price_per_kwh) . ' per kWh' : ''), // Charging price for E garages
            ]), // End of the garage options
        ]); // End of page()
    } // End of create()

    public function preview(RentalRequest $request): View // POST /tenant/rentals/preview: check everything and show the total
    { // Start of preview()
        $rental = $this->calculateRental($request->validated()); // All checks and formulas

        return $this->page('tenant.rentals.preview', [ // Show the summary and the payment form
            'summary' => $this->summaryRows($rental), // Label/value rows
            'formData' => $request->validated(), // Every value again, sent back as hidden fields on confirm
            'backUrl' => route('tenant.rentals.create', ['vehicle_type' => $request->validated('vehicle_type')]), // "Change details" link
        ]); // End of page()
    } // End of preview()

    public function store(StoreRentalRequest $request): RedirectResponse // POST /tenant/rentals: save the rental
    { // Start of store()
        $rental = $this->calculateRental($request->validated()); // Check and calculate again (never trust the hidden fields)
        $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public'); // Save the image in storage/app/public/payment-proofs

        DB::transaction(function () use ($request, $rental, $proofPath) { // Vehicle, rental and garage status are saved together
            $vehicle = Vehicle::create([ // Save the vehicle
                'user_id' => $request->user()->user_id, // Owner = logged-in tenant
                'type' => $rental['vehicleType'], // Vehicle type
                'plate_number' => $rental['plateNumber'], // Plate (empty for bicycles)
                'brand_model' => $rental['brandModel'], // Brand and model
                'battery_capacity_kwh' => $rental['batteryCapacity'], // Max kWh (electric cars only)
            ]); // End of the vehicle

            Rental::create([ // Save the rental
                'vehicle_id' => $vehicle->vehicle_id, // Which vehicle
                'garage_id' => $rental['garage']->garage_id, // Which garage
                'rental_type' => $rental['rentalType'], // Daily or monthly
                'days' => $rental['days'], // Number of days
                'start_date' => $rental['startDate'], // Today
                'end_date' => $rental['endDate'], // Today + days
                'rental_cost' => $rental['rentalCost'], // Garage cost
                'battery_level_percent' => $rental['batteryPercent'], // Remaining battery (E garages only)
                'kwh_to_full' => $rental['kwhToFull'], // kWh until full (E garages only)
                'charging_cost' => $rental['chargingCost'], // Charging cost
                'total_cost' => $rental['totalCost'], // Total paid
                'payment_proof' => $proofPath, // Where the image was saved
            ]); // End of the rental

            $rental['garage']->update(['status' => GarageStatus::Used]); // The garage is now used
        }); // End of the transaction

        return redirect()->route('tenant.rentals.index') // Go to My Rentals ...
            ->with('success', "Garage {$rental['garage']->garage_id} is rented until {$this->date($rental['endDate'])}."); // ... with a green message
    } // End of store()

    private function calculateRental(array $data): array // Checks every rule and calculates every amount; throws an error back to the form if a rule fails
    { // Start of calculateRental()
        $today = today(); // Start date is always today
        $vehicleType = VehicleType::from($data['vehicle_type']); // Vehicle type
        $rentalType = RentalType::from($data['rental_type']); // Daily or monthly
        $garage = Garage::findOrFail($data['garage_id']); // Chosen garage
        $plate = $this->needsPlate($vehicleType) ? strtoupper(trim((string) $data['plate_number'])) : null; // Plate in capitals, none for bicycles
        $isElectric = $vehicleType === VehicleType::ElectricCar; // Electric car?
        $days = $rentalType === RentalType::Monthly ? $this->monthlyDays($today) : (int) $data['days']; // Monthly = days of this month

        $this->refreshGarageStatuses(); // Make statuses match today's rentals

        $errors = []; // Collected error messages
        if (! in_array($garage->type, $this->compatibleGarageTypes($vehicleType), true)) { // Wrong garage type for this vehicle
            $errors['garage_id'] = "A {$vehicleType->label()} can't use a {$garage->type->label()}."; // Error under the garage field
        } elseif ($garage->fresh()->status !== GarageStatus::Available) { // Garage is already rented today
            $errors['garage_id'] = "Garage {$garage->garage_id} is not available today."; // Error under the garage field
        } // End of the garage checks
        if (! $this->isValidDays($rentalType, $days, $today)) { // Too many days for a daily rental this month
            $errors['days'] = "A daily rental starting today can be 1 to {$this->maxDailyDays($today)} days."; // Error under the days field
        } // End of the days check
        if ($plate !== null && $this->plateIsRentedNow($plate)) { // This plate already has a running rental
            $errors['plate_number'] = "The vehicle with plate {$plate} already has an active rental."; // Error under the plate field
        } // End of the plate check

        $chargesHere = $this->hasCharger($garage->type); // Charging happens only in an E garage
        $batteryPercent = $chargesHere ? ($data['battery_level_percent'] ?? null) : null; // Keep the battery % only when charging
        $kwhToFull = $chargesHere ? ($data['kwh_to_full'] ?? null) : null; // Keep kWh to full only when charging
        $capacity = $isElectric ? (float) $data['battery_capacity_kwh'] : null; // Max kWh, electric cars only
        if (! $this->isValidBattery($garage->type, $batteryPercent === null ? null : (int) $batteryPercent, $kwhToFull === null ? null : (float) $kwhToFull, $capacity)) { // Battery numbers wrong
            $errors['kwh_to_full'] = 'For charging, enter the remaining battery (0-99%) and the kWh until full (more than 0, at most the battery size).'; // Error under the kWh field
        } // End of the battery check

        if ($errors !== []) { // Any rule failed ...
            throw ValidationException::withMessages($errors); // ... go back to the form and show the errors
        } // End of the error check

        $rentalCost = $this->rentalCost($rentalType, $garage->daily_price, $days); // Garage cost
        $chargingCost = $this->chargingCost($garage->type, $kwhToFull === null ? null : (float) $kwhToFull, $garage->electricity_price_per_kwh); // Charging cost

        return [ // Everything the preview and the save need
            'vehicleType' => $vehicleType, // Vehicle type
            'plateNumber' => $plate, // Plate
            'brandModel' => $data['brand_model'], // Brand and model
            'batteryCapacity' => $capacity, // Max kWh
            'rentalType' => $rentalType, // Daily or monthly
            'days' => $days, // Number of days
            'garage' => $garage, // Garage
            'startDate' => $today, // Start date
            'endDate' => $this->endDate($today, $days), // End date
            'batteryPercent' => $batteryPercent === null ? null : (int) $batteryPercent, // Remaining battery
            'kwhToFull' => $kwhToFull === null ? null : (float) $kwhToFull, // kWh until full
            'rentalCost' => $rentalCost, // Garage cost
            'chargingCost' => $chargingCost, // Charging cost
            'totalCost' => $rentalCost + $chargingCost, // Total
        ]; // End of the result
    } // End of calculateRental()

    private function plateIsRentedNow(string $plate): bool // Does this plate have a rental that hasn't ended?
    { // Start of plateIsRentedNow()
        return Rental::whereDate('end_date', '>=', today()) // Rentals that haven't ended ...
            ->whereHas('vehicle', fn ($query) => $query->where('plate_number', $plate)) // ... for a vehicle with this plate
            ->exists(); // True if there is one
    } // End of plateIsRentedNow()

    private function summaryRows(array $rental): array // Label/value rows for the preview page
    { // Start of summaryRows()
        $rows = [ // Rows shown for every rental
            ['label' => 'Vehicle', 'value' => $this->vehicleLabel($rental['brandModel'], $rental['plateNumber']) . ', ' . $rental['vehicleType']->label()], // e.g. Toyota Avanza (L 1234 AB), Car
            ['label' => 'Garage', 'value' => $rental['garage']->garage_id . ' (' . $rental['garage']->type->label() . ')'], // e.g. C001 (Car Garage)
            ['label' => 'Rental type', 'value' => $rental['rentalType']->label() . ', ' . $rental['days'] . ' days'], // e.g. Daily, 5 days
            ['label' => 'Start date', 'value' => $this->date($rental['startDate'])], // Today
            ['label' => 'End date', 'value' => $this->date($rental['endDate'])], // Today + days
            ['label' => 'Garage cost', 'value' => $this->rupiah($rental['rentalCost'])], // Garage cost
        ]; // End of the common rows

        if ($this->hasCharger($rental['garage']->type)) { // Charging rows only for an E garage
            $rows[] = ['label' => 'Battery', 'value' => $rental['batteryPercent'] . '% left, ' . $rental['kwhToFull'] . ' kWh until full']; // Battery details
            $rows[] = ['label' => 'Charging cost', 'value' => $this->rupiah($rental['chargingCost'])]; // Charging cost
        } // End of the charging rows

        $rows[] = ['label' => 'Total to pay', 'value' => $this->rupiah($rental['totalCost'])]; // Total, always last

        return $rows; // The finished rows
    } // End of summaryRows()

    private function rentalRow(Rental $rental): array // One row for the My Rentals table
    { // Start of rentalRow()
        $isActive = $rental->end_date->gte(today()); // Not ended yet

        return [ // Ready-made values
            'id' => $rental->rental_id, // For the Extend button
            'garage' => $rental->garage_id . ' (' . $rental->garage->type->label() . ')', // Garage
            'vehicle' => $this->vehicleLabel($rental->vehicle->brand_model, $rental->vehicle->plate_number), // Vehicle
            'type' => $rental->rental_type->label(), // Daily / Monthly
            'period' => $this->date($rental->start_date) . ' - ' . $this->date($rental->end_date), // Start - end
            'total' => $this->rupiah($rental->total_cost), // Amount paid
            'status' => $isActive ? 'Active' : 'Ended', // Status text
            'statusColor' => $isActive ? 'success' : 'secondary', // Green or grey
            'isActive' => $isActive, // Only active rentals can be extended
            'waitingExtension' => $rental->extensions->contains('status', ExtensionStatus::Pending), // An extension request waits for the admin
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
