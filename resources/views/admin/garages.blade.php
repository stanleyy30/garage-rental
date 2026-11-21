@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Garages') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Garages</h1> {{-- Page heading --}}

<div class="card shadow-sm mb-4"> {{-- Box for the Create Garages form --}}
    <div class="card-header fw-bold">Create garages ({{ $remaining }} of 999 places left)</div> {{-- Box title with the free places --}}
    <div class="card-body"> {{-- Form padding --}}
        <form method="POST" action="{{ route('admin.garages.store') }}" class="row g-3"> {{-- Sends the form to POST /admin/garages --}}
            @csrf {{-- Anti-forgery token --}}
            <div class="col-md-3"> {{-- Column for the type --}}
                <label for="type" class="form-label">Garage type</label> {{-- Label --}}
                <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required> {{-- Dropdown --}}
                    @foreach ($garageTypes as $value => $label) {{-- One option per type from the controller --}}
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option> {{-- Keeps the choice after an error --}}
                    @endforeach {{-- End of the options --}}
                </select> {{-- End of the dropdown --}}
                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror {{-- Error text --}}
            </div> {{-- End of the type column --}}
            <div class="col-md-2"><x-form-input name="quantity" label="Quantity" type="number" min="1" max="999" required /></div> {{-- How many --}}
            <div class="col-md-3"><x-form-input name="daily_price" label="Daily price (Rp)" type="number" min="1" required /></div> {{-- Price per day --}}
            <div class="col-md-4"><x-form-input name="electricity_price_per_kwh" label="Price per kWh (Rp)" type="number" min="1" hint="Only for electric car garages." /></div> {{-- kWh price --}}
            <div class="col-12"><button type="submit" class="btn btn-primary">Create garages</button></div> {{-- Submit button --}}
        </form> {{-- End of the form --}}
    </div> {{-- End of card-body --}}
</div> {{-- End of the form box --}}

<div class="card shadow-sm"> {{-- Box for the garage list --}}
    <div class="card-header fw-bold">All garages ({{ count($garages) }})</div> {{-- Box title --}}
    <div class="table-responsive"> {{-- Scroll wrapper for phones --}}
        <table class="table table-striped mb-0 align-middle"> {{-- Striped table --}}
            <thead> {{-- Table header --}}
                <tr><th>ID</th><th>Type</th><th>Daily price</th><th>Monthly price</th><th>Price per kWh</th><th>Status</th></tr> {{-- Column names --}}
            </thead> {{-- End of the header --}}
            <tbody> {{-- Table body --}}
                @forelse ($garages as $garage) {{-- One row per garage --}}
                    <tr> {{-- Start of the row --}}
                        <td class="font-monospace">{{ $garage['id'] }}</td> {{-- Garage ID --}}
                        <td>{{ $garage['type'] }}</td> {{-- Type --}}
                        <td>{{ $garage['dailyPrice'] }}</td> {{-- Daily price --}}
                        <td>{{ $garage['monthlyPrice'] }}</td> {{-- Monthly price --}}
                        <td>{{ $garage['kwhPrice'] }}</td> {{-- kWh price --}}
                        <td><span class="badge bg-{{ $garage['statusColor'] }}">{{ $garage['status'] }}</span></td> {{-- Status badge --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- No garages yet --}}
                    <tr><td colspan="6" class="text-muted">No garages yet.</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the list box --}}
@endsection {{-- End of this page's content --}}
