@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Confirm Rental') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Confirm Rental</h1> {{-- Page heading --}}

<div class="card shadow-sm"> {{-- Summary box --}}
    <div class="card-body"> {{-- Padding --}}
        <table class="table mb-4"> {{-- Summary table --}}
            @foreach ($summary as $row) {{-- One row per value from the controller --}}
                <tr><th class="w-25">{{ $row['label'] }}</th><td>{{ $row['value'] }}</td></tr> {{-- Label and value --}}
            @endforeach {{-- End of the rows --}}
        </table> {{-- End of the summary --}}

        <form method="POST" action="{{ route('tenant.rentals.store') }}" enctype="multipart/form-data"> {{-- multipart: needed to upload a file --}}
            @csrf {{-- Anti-forgery token --}}
            @foreach ($formData as $field => $value) {{-- Send every detail again; the controller checks it all again --}}
                <input type="hidden" name="{{ $field }}" value="{{ $value }}"> {{-- Hidden field --}}
            @endforeach {{-- End of the hidden fields --}}
            <x-form-input name="payment_proof" label="Payment proof (jpg or png, max 1 MB)" type="file" accept=".jpg,.jpeg,.png" required /> {{-- Image upload --}}
            <button type="submit" class="btn btn-success">Confirm and pay</button> {{-- Save the rental --}}
            <a href="{{ $backUrl }}" class="btn btn-link">Change details</a> {{-- Back to the form --}}
        </form> {{-- End of the form --}}
    </div> {{-- End of padding --}}
</div> {{-- End of the summary box --}}
@endsection {{-- End of this page's content --}}
