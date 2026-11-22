@extends('layouts.tenant') {{-- Tenant page --}}

@section('title', 'Send Registration Again') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<div class="row justify-content-center"> {{-- Centre the card --}}
    <div class="col-md-7"> {{-- Card width on medium+ screens --}}
        <h1 class="h3 mb-3">Send Registration Again</h1> {{-- Page heading --}}
        <p class="text-muted">The admin could not match your NIK with your KTP photo. Check your details, upload a clear photo of your KTP and send it again.</p> {{-- Why and what to do --}}

        <div class="card shadow-sm"> {{-- Form box --}}
            <div class="card-body p-4"> {{-- Inner padding --}}
                <form method="POST" action="{{ route('tenant.registration.update') }}" enctype="multipart/form-data" novalidate> {{-- multipart for the photo; the server shows every error in red --}}
                    @csrf {{-- Hidden anti-forgery token --}}
                    @method('PUT') {{-- Forms only send GET/POST, so mark this as PUT --}}
                    <x-form-input name="nik" label="NIK (16 digits)" :value="$nik" maxlength="16" inputmode="numeric" placeholder="3578010101900001" /> {{-- NIK, filled with the old value --}}
                    <x-form-input name="ktp_photo" label="New KTP photo (jpg or png, max 2 MB)" type="file" accept=".jpg,.jpeg,.png" hint="Make sure the NIK on the photo is sharp and easy to read." /> {{-- New KTP upload --}}
                    <x-form-input name="name" label="Full Name" :value="$name" maxlength="100" placeholder="Budi Santoso" /> {{-- Name, filled with the old value --}}
                    <x-form-input name="phone_number" label="Phone Number" :value="$phoneNumber" maxlength="15" inputmode="numeric" placeholder="081234567890" /> {{-- Phone, filled with the old value --}}
                    <button type="submit" class="btn btn-primary w-100">Send registration again</button> {{-- Submit button --}}
                </form> {{-- End of the form --}}
            </div> {{-- End of the inner padding --}}
        </div> {{-- End of the form box --}}
    </div> {{-- End of the column --}}
</div> {{-- End of the row --}}
@endsection {{-- End of this page's content --}}
