@extends('layouts.base') {{-- Guest page: base layout without a top bar --}}

@section('title', 'Login') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<div class="row justify-content-center mt-5"> {{-- Centre the card --}}
    <div class="col-md-5"> {{-- Card width on medium+ screens --}}
        <div class="card shadow-sm"> {{-- White box with a light shadow --}}
            <div class="card-body p-4"> {{-- Inner padding --}}
                <h1 class="h4 mb-4 text-center">Garage Rental Login</h1> {{-- Page heading --}}

                <form method="POST" action="{{ route('login') }}"> {{-- Send the form to POST /login --}}
                    @csrf {{-- Hidden anti-forgery token --}}
                    <x-form-input name="email" label="Email" type="email" required autofocus /> {{-- Email field --}}
                    <x-form-input name="password" label="Password" type="password" required /> {{-- Password field --}}
                    <button type="submit" class="btn btn-primary w-100">Login</button> {{-- Submit button --}}
                </form> {{-- End of the form --}}

                <p class="text-center mt-3 mb-0">Don't have an account? <a href="{{ route('register') }}">Register as a tenant</a></p> {{-- Link to registration --}}
            </div> {{-- End of card-body --}}
        </div> {{-- End of the card --}}
    </div> {{-- End of the column --}}
</div> {{-- End of the row --}}
@endsection {{-- End of this page's content --}}
