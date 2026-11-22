@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Verify New Registrants') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Verify New Registrants</h1> {{-- Page heading --}}
<p class="text-muted">Open each tenant's KTP photo (you can turn it if it was taken sideways) and check that its NIK matches the NIK they typed, then accept or reject the registration. A rejected tenant can correct the details and send them again.</p> {{-- Short instruction --}}

@error('decision') {{-- An error came back from the controller --}}
    <div class="alert alert-danger">{{ $message }}</div> {{-- Show it in red --}}
@enderror {{-- End of the error --}}

<div class="card shadow-sm mb-4"> {{-- Box for the waiting list --}}
    <div class="card-header fw-bold">Waiting for verification ({{ count($pendingTenants) }})</div> {{-- Box title with the number of rows --}}
    <div class="table-responsive"> {{-- Lets the table scroll sideways on phones --}}
        <table class="table table-striped mb-0 align-middle"> {{-- Striped table --}}
            <thead> {{-- Table header --}}
                <tr><th>KTP photo</th><th>NIK typed</th><th>Name</th><th>Phone</th><th>Email</th><th>Registered</th><th class="text-end">Decision</th></tr> {{-- Column names --}}
            </thead> {{-- End of the header --}}
            <tbody> {{-- Table body --}}
                @forelse ($pendingTenants as $tenant) {{-- One row per waiting tenant; the @empty part shows when there are none --}}
                    <tr> {{-- Start of the row --}}
                        <td> {{-- KTP cell --}}
                            @if ($tenant['ktpUrl']) {{-- A photo was uploaded --}}
                                <button type="button" class="thumb-button" data-viewer-src="{{ $tenant['ktpUrl'] }}" data-viewer-title="KTP of {{ $tenant['name'] }}" data-viewer-caption="NIK typed: {{ $tenant['nik'] }}"><img src="{{ $tenant['ktpUrl'] }}" alt="KTP of {{ $tenant['name'] }}" class="img-thumbnail" style="max-height: 70px"></button> {{-- Small preview; click opens the viewer, where the photo can be turned and compared with the typed NIK --}}
                                <div><button type="button" class="btn btn-link btn-sm p-0" data-viewer-src="{{ $tenant['ktpUrl'] }}" data-viewer-title="KTP of {{ $tenant['name'] }}" data-viewer-caption="NIK typed: {{ $tenant['nik'] }}">Check KTP</button></div> {{-- Same viewer from a text link --}}
                            @else {{-- Older account without a photo --}}
                                <span class="text-muted">No photo</span> {{-- Nothing to show --}}
                            @endif {{-- End of the KTP cell --}}
                        </td> {{-- End of the KTP cell --}}
                        <td class="font-monospace">{{ $tenant['nik'] }}</td> {{-- NIK in a fixed-width font, easy to compare with the KTP --}}
                        <td>{{ $tenant['name'] }}</td> {{-- Name --}}
                        <td>{{ $tenant['phone'] }}</td> {{-- Phone --}}
                        <td>{{ $tenant['email'] }}</td> {{-- Email --}}
                        <td>{{ $tenant['registeredAt'] }}</td> {{-- Registration date --}}
                        <td class="text-end text-nowrap"> {{-- Buttons cell --}}
                            <form method="POST" action="{{ route('admin.verifications.update', $tenant['id']) }}" class="d-inline"> {{-- Sends this tenant's decision --}}
                                @csrf {{-- Anti-forgery token --}}
                                @method('PATCH') {{-- Forms only send GET/POST, so mark this as PATCH --}}
                                <button type="submit" name="decision" value="accept" class="btn btn-success btn-sm" onclick="return confirm('Accept this tenant?')">Accept</button> {{-- Green button with a confirmation --}}
                                <button type="submit" name="decision" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject this tenant?')">Reject</button> {{-- Red button with a confirmation --}}
                            </form> {{-- End of the form --}}
                        </td> {{-- End of the buttons cell --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- Nobody is waiting --}}
                    <tr><td colspan="7" class="text-muted">No new registrants.</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the waiting box --}}

<div class="card shadow-sm"> {{-- Box for decided tenants --}}
    <div class="card-header fw-bold">Already verified ({{ count($processedTenants) }})</div> {{-- Box title --}}
    <div class="table-responsive"> {{-- Scroll wrapper --}}
        <table class="table mb-0 align-middle"> {{-- Plain table --}}
            <thead> {{-- Table header --}}
                <tr><th>NIK</th><th>Name</th><th>Email</th><th>Status</th><th>Verified at</th></tr> {{-- Column names --}}
            </thead> {{-- End of the header --}}
            <tbody> {{-- Table body --}}
                @forelse ($processedTenants as $tenant) {{-- One row per decided tenant --}}
                    <tr> {{-- Start of the row --}}
                        <td class="font-monospace">{{ $tenant['nik'] }}</td> {{-- NIK --}}
                        <td>{{ $tenant['name'] }}</td> {{-- Name --}}
                        <td>{{ $tenant['email'] }}</td> {{-- Email --}}
                        <td><span class="badge bg-{{ $tenant['statusColor'] }}">{{ $tenant['status'] }}</span></td> {{-- Coloured status label --}}
                        <td>{{ $tenant['verifiedAt'] }}</td> {{-- Decision date --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- Nothing decided yet --}}
                    <tr><td colspan="5" class="text-muted">No tenants verified yet.</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the decided box --}}
@endsection {{-- End of this page's content --}}
