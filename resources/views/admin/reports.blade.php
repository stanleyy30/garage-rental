@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Reports') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Rental Reports</h1> {{-- Page heading --}}

<form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end mb-4"> {{-- GET form: reloads the page with the chosen report --}}
    <div class="col-md-4"> {{-- Report type column --}}
        <label for="type" class="form-label">Report</label> {{-- Label --}}
        <select id="type" name="type" class="form-select"> {{-- Report dropdown --}}
            @foreach ($types as $value => $label) {{-- One option per report --}}
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option> {{-- Keeps the current report selected --}}
            @endforeach {{-- End of the options --}}
        </select> {{-- End of the dropdown --}}
    </div> {{-- End of the column --}}
    <div class="col-md-2"><label for="year" class="form-label">Year</label><input type="number" id="year" name="year" value="{{ $year }}" class="form-control"></div> {{-- Year (monthly and daily) --}}
    <div class="col-md-3"> {{-- Month column --}}
        <label for="month" class="form-label">Month (daily report)</label> {{-- Label --}}
        <select id="month" name="month" class="form-select"> {{-- Month dropdown --}}
            @foreach ($months as $number => $name) {{-- One option per month --}}
                <option value="{{ $number }}" @selected($month === $number)>{{ $name }}</option> {{-- Keeps the current month selected --}}
            @endforeach {{-- End of the options --}}
        </select> {{-- End of the dropdown --}}
    </div> {{-- End of the column --}}
    <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">Show report</button></div> {{-- Reload with these filters --}}
</form> {{-- End of the filter form --}}

<div class="card shadow-sm"> {{-- Report box --}}
    <div class="card-header fw-bold">{{ $title }}</div> {{-- e.g. "Per month in 2026" --}}
    <div class="table-responsive"> {{-- Scroll wrapper for phones --}}
        <table class="table table-striped mb-0 align-middle"> {{-- Striped table --}}
            <thead> {{-- Table header --}}
                <tr><th></th><th class="text-end">Rentals</th><th class="text-end">Extensions</th><th class="text-end">Income</th></tr> {{-- Column names --}}
            </thead> {{-- End of the header --}}
            <tbody> {{-- Table body --}}
                @forelse ($rows as $row) {{-- One row per month, day, type or tenant from the controller --}}
                    <tr> {{-- Start of the row --}}
                        <td> {{-- Month, day, type or tenant --}}
                            {{ $row['label'] }} {{-- The row name --}}
                            @if (! empty($row['details'])) {{-- The per-tenant report also lists what the tenant rented --}}
                                <ul class="small text-muted mb-0 ps-3"> {{-- Small grey list under the name --}}
                                    @foreach ($row['details'] as $detail) {{-- One line per rental --}}
                                        <li>{{ $detail }}</li> {{-- Garage, vehicle and dates --}}
                                    @endforeach {{-- End of the rentals --}}
                                </ul> {{-- End of the list --}}
                            @endif {{-- End of the details --}}
                        </td> {{-- End of the name cell --}}
                        <td class="text-end">{{ $row['rentals'] }}</td> {{-- Number of rentals --}}
                        <td class="text-end">{{ $row['extensions'] }}</td> {{-- Number of extensions --}}
                        <td class="text-end">{{ $row['income'] }}</td> {{-- Money received --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- No rows (per-tenant report before any rental) --}}
                    <tr><td colspan="4" class="text-muted">No rentals yet.</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
            <tfoot class="fw-bold"> {{-- Total line --}}
                <tr><td>Total</td><td class="text-end">{{ $total['rentals'] }}</td><td class="text-end">{{ $total['extensions'] }}</td><td class="text-end">{{ $total['income'] }}</td></tr> {{-- Sums from the controller --}}
            </tfoot> {{-- End of the total line --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the report box --}}
@endsection {{-- End of this page's content --}}
