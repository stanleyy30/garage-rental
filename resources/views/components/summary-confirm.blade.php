@props(['summary', 'action']) {{-- Rows to show and where the confirm form is sent --}}

<div class="card shadow-sm"> {{-- Summary box --}}
    <div class="card-body"> {{-- Padding --}}
        <table class="table mb-4"> {{-- Summary table --}}
            @foreach ($summary as $row) {{-- One row per value from the controller --}}
                <tr><th class="w-25">{{ $row['label'] }}</th><td>{{ $row['value'] }}</td></tr> {{-- Label and value --}}
            @endforeach {{-- End of the rows --}}
        </table> {{-- End of the summary --}}

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" novalidate> {{-- multipart: needed to upload a file; the server marks a missing file in red --}}
            @csrf {{-- Anti-forgery token --}}
            {{ $slot }} {{-- Hidden fields given by the page --}}
            <x-form-input name="payment_proof" label="Payment proof (jpg or png, max 1 MB)" type="file" accept=".jpg,.jpeg,.png" /> {{-- Image upload --}}
            <button type="submit" class="btn btn-success">Confirm and pay</button> {{-- Save --}}
        </form> {{-- End of the form --}}
    </div> {{-- End of padding --}}
</div> {{-- End of the box --}}
