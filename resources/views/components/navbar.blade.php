@props(['userName', 'userRole']) {{-- Values this component receives --}}

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4"> {{-- Dark top bar --}}
    <div class="container"> {{-- Centre the bar's content --}}
        <a class="navbar-brand" href="{{ route('dashboard') }}">{{ config('app.name') }}</a> {{-- App name, links to the home page --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainMenu">{{-- Menu button on small screens --}}
            <span class="navbar-toggler-icon"></span> {{-- The three-line icon --}}
        </button> {{-- End of the menu button --}}
        <div class="collapse navbar-collapse" id="mainMenu"> {{-- Menu that folds on small screens --}}
            <ul class="navbar-nav me-auto">{{ $slot }}</ul> {{-- The links given by the admin or tenant layout --}}
            <div class="d-flex align-items-center gap-3"> {{-- Right side: name + logout --}}
                <span class="text-white">{{ $userName }} ({{ $userRole }})</span> {{-- Who is logged in and their role --}}
                <form method="POST" action="{{ route('logout') }}"> {{-- Logout is a POST form, not a link --}}
                    @csrf {{-- Hidden token that proves the form came from this site --}}
                    <button type="submit" class="btn btn-outline-light btn-sm">Logout</button> {{-- Logout button --}}
                </form> {{-- End of the logout form --}}
            </div> {{-- End of the right side --}}
        </div> {{-- End of the folding menu --}}
    </div> {{-- End of the container --}}
</nav> {{-- End of the top bar --}}
