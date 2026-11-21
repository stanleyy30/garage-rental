@extends('layouts.app') {{-- Use the shared layout --}}

@section('title', 'Register') {{-- Tab title: "Register - Garage Rental" --}}

@section('content') {{-- Start of this page's content --}}
<div class="row justify-content-center mt-5"> {{-- Centre the card horizontally --}}
    <div class="col-md-6"> {{-- Card width on medium+ screens --}}
        <div class="card shadow-sm"> {{-- White box with a light shadow --}}
            <div class="card-body p-4"> {{-- Inner padding --}}
                <h1 class="h4 mb-4 text-center">Register as a Tenant</h1> {{-- Page heading --}}

                <form method="POST" action="{{ route('register') }}"> {{-- Send the form to POST /register --}}
                    @csrf {{-- Hidden anti-forgery token --}}

                    <div class="mb-3"> {{-- NIK field group --}}
                        <label for="nik" class="form-label">NIK (16 digits)</label> {{-- Label for the NIK box --}}
                        <input type="text" id="nik" name="nik" value="{{ old('nik') }}" maxlength="16" inputmode="numeric" class="form-control @error('nik') is-invalid @enderror" required autofocus> {{-- NIK box: max 16 characters, number keyboard on phones --}}
                        @error('nik') {{-- There is an error for "nik" --}}
                            <div class="invalid-feedback">{{ $message }}</div> {{-- Show the error text --}}
                        @enderror {{-- End of the NIK error --}}
                    </div> {{-- End of the NIK group --}}

                    <div class="mb-3"> {{-- Name field group --}}
                        <label for="name" class="form-label">Full Name</label> {{-- Label for the name box --}}
                        <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="100" class="form-control @error('name') is-invalid @enderror" required> {{-- Name box --}}
                        @error('name') {{-- There is an error for "name" --}}
                            <div class="invalid-feedback">{{ $message }}</div> {{-- Show the error text --}}
                        @enderror {{-- End of the name error --}}
                    </div> {{-- End of the name group --}}

                    <div class="mb-3"> {{-- Phone field group --}}
                        <label for="phone_number" class="form-label">Phone Number</label> {{-- Label for the phone box --}}
                        <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" maxlength="15" inputmode="numeric" class="form-control @error('phone_number') is-invalid @enderror" required> {{-- Phone box --}}
                        @error('phone_number') {{-- There is an error for "phone_number" --}}
                            <div class="invalid-feedback">{{ $message }}</div> {{-- Show the error text --}}
                        @enderror {{-- End of the phone error --}}
                    </div> {{-- End of the phone group --}}

                    <div class="mb-3"> {{-- Email field group --}}
                        <label for="email" class="form-label">Email (used to log in)</label> {{-- Label for the email box --}}
                        <input type="email" id="email" name="email" value="{{ old('email') }}" maxlength="100" class="form-control @error('email') is-invalid @enderror" required> {{-- Email box --}}
                        @error('email') {{-- There is an error for "email" --}}
                            <div class="invalid-feedback">{{ $message }}</div> {{-- Show the error text --}}
                        @enderror {{-- End of the email error --}}
                    </div> {{-- End of the email group --}}

                    <div class="mb-3"> {{-- Password field group --}}
                        <label for="password" class="form-label">Password (at least 8 characters)</label> {{-- Label for the password box --}}
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required> {{-- Password box, never refilled --}}
                        @error('password') {{-- There is an error for "password" --}}
                            <div class="invalid-feedback">{{ $message }}</div> {{-- Show the error text --}}
                        @enderror {{-- End of the password error --}}
                    </div> {{-- End of the password group --}}

                    <div class="mb-4"> {{-- Confirm-password field group --}}
                        <label for="password_confirmation" class="form-label">Confirm Password</label> {{-- Label for the confirm box --}}
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required> {{-- Must match "password" (the "confirmed" rule) --}}
                    </div> {{-- End of the confirm group --}}

                    <button type="submit" class="btn btn-primary w-100">Register</button> {{-- Full-width blue submit button --}}
                </form> {{-- End of the form --}}

                <p class="text-center mt-3 mb-0">Already have an account? <a href="{{ route('login') }}">Log in</a></p> {{-- Link back to the login page --}}
            </div> {{-- End of card-body --}}
        </div> {{-- End of the card --}}
    </div> {{-- End of the column --}}
</div> {{-- End of the row --}}
@endsection {{-- End of this page's content --}}