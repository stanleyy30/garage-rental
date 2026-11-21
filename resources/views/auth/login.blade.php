@extends('layouts.app') {{-- Use the shared layout --}}

@section('title', 'Login') {{-- Tab title: "Login - Garage Rental" --}}

@section('content') {{-- Start of this page's content --}}
<div class="row justify-content-center mt-5"> {{-- Centre the card horizontally --}}
    <div class="col-md-5"> {{-- Card width on medium+ screens --}}
        <div class="card shadow-sm"> {{-- White box with a light shadow --}}
            <div class="card-body p-4"> {{-- Inner padding --}}
                <h1 class="h4 mb-4 text-center">Garage Rental Login</h1> {{-- Page heading --}}

                <form method="POST" action="{{ route('login') }}"> {{-- Send the form to POST /login --}}
                    @csrf {{-- Hidden anti-forgery token --}}

                    <div class="mb-3"> {{-- Email field group --}}
                        <label for="email" class="form-label">Email</label> {{-- Label for the email box --}}
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus> {{-- Email box; old() keeps the typed value; red border on error --}}
                        @error('email') {{-- There is an error for "email" --}}
                            <div class="invalid-feedback">{{ $message }}</div> {{-- Show the error text under the box --}}
                        @enderror {{-- End of the email error --}}
                    </div> {{-- End of the email group --}}

                    <div class="mb-4"> {{-- Password field group --}}
                        <label for="password" class="form-label">Password</label> {{-- Label for the password box --}}
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required> {{-- Password box (dots), never refilled --}}
                        @error('password') {{-- There is an error for "password" --}}
                            <div class="invalid-feedback">{{ $message }}</div> {{-- Show the error text --}}
                        @enderror {{-- End of the password error --}}
                    </div> {{-- End of the password group --}}

                    <button type="submit" class="btn btn-primary w-100">Login</button> {{-- Full-width blue submit button --}}
                </form> {{-- End of the form --}}
                
                <p class="text-center mt-3 mb-0">Don't have an account? <a href="{{ route('register') }}">Register as a tenant</a></p> {{-- Link to the registration page --}}                
            </div> {{-- End of card-body --}}
        </div> {{-- End of the card --}}
    </div> {{-- End of the column --}}
</div> {{-- End of the row --}}
@endsection {{-- End of this page's content --}}