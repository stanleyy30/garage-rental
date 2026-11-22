@extends('layouts.base') {{-- Guest page: base layout without a top bar --}}

@section('title', 'Register') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<div class="row justify-content-center mt-5"> {{-- Centre the card --}}
    <div class="col-md-6"> {{-- Card width on medium+ screens --}}
        <div class="card shadow-sm"> {{-- White box with a light shadow --}}
            <div class="card-body p-4"> {{-- Inner padding --}}
                <h1 class="h4 mb-4 text-center">Register as a Tenant</h1> {{-- Page heading --}}

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" novalidate> {{-- Send the form to POST /register; multipart is needed for the KTP photo; novalidate lets the server show every error in red --}}
                    @csrf {{-- Hidden anti-forgery token --}}
                    <x-form-input name="nik" label="NIK (16 digits)" maxlength="16" inputmode="numeric" placeholder="3578010101900001" autofocus /> {{-- NIK field with a grey example --}}
                    <x-form-input name="ktp_photo" label="KTP photo (jpg or png, max 2 MB)" type="file" accept=".jpg,.jpeg,.png" hint="The admin checks that the NIK on your KTP matches the NIK above." /> {{-- KTP upload --}}
                    <x-form-input name="name" label="Full Name" maxlength="100" placeholder="Budi Santoso" autocomplete="name" /> {{-- Name field with a grey example --}}
                    <x-form-input name="phone_number" label="Phone Number" maxlength="15" inputmode="numeric" placeholder="081234567890" autocomplete="tel" /> {{-- Phone field with a grey example --}}
                    <x-form-input name="email" label="Email" type="email" maxlength="100" placeholder="budi@example.com" autocomplete="email" /> {{-- Email field, also the username --}}
                    <x-form-input name="password" label="Password" type="password" placeholder="At least 8 characters" autocomplete="new-password" /> {{-- Password field --}}
                    <x-form-input name="password_confirmation" label="Confirm Password" type="password" placeholder="Type the password again" autocomplete="new-password" /> {{-- Must match the password --}}
                    <button type="submit" class="btn btn-primary w-100">Register</button> {{-- Submit button --}}
                </form> {{-- End of the form --}}

                <p class="text-center mt-3 mb-0">Already have an account? <a href="{{ route('login') }}">Log in</a></p> {{-- Link back to login --}}
            </div> {{-- End of card-body --}}
        </div> {{-- End of the card --}}
    </div> {{-- End of the column --}}
</div> {{-- End of the row --}}
@endsection {{-- End of this page's content --}}
