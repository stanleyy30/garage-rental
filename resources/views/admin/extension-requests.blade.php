@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Extension Requests') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Extension Requests</h1> {{-- Page heading --}}
<p class="text-muted">Tenants asked for these extensions in the app. Check their payment proof, then upload the payment evidence and accept, or reject the request.</p> {{-- Instruction --}}

@error('payment_proof') {{-- An error came back from the controller --}}
    <div class="alert alert-danger">{{ $message }}</div> {{-- Show it in red --}}
@enderror {{-- End of the error --}}

<div class="card shadow-sm mb-4"> {{-- Box for waiting requests --}}
    <div class="card-header fw-bold">Waiting for a decision ({{ count($pendingRequests) }})</div> {{-- Box title --}}
    <div class="table-responsive"> {{-- Scroll wrapper for phones --}}
        <table class="table table-striped mb-0 align-middle"> {{-- Striped table --}}
            <thead> {{-- Table header --}}
                <tr><th>Garage</th><th>Tenant</th><th>Vehicle</th><th>End date</th><th>Extra</th><th>Cost</th><th>Tenant's proof</th><th>Decision</th></tr> {{-- Column names --}}
            </thead> {{-- End of the header --}}
            <tbody> {{-- Table body --}}
                @forelse ($pendingRequests as $req) {{-- One row per waiting request from the controller --}}
                    <tr> {{-- Start of the row --}}
                        <td>{{ $req['garage'] }}</td> {{-- Garage --}}
                        <td>{{ $req['tenant'] }}</td> {{-- Tenant --}}
                        <td>{{ $req['vehicle'] }}</td> {{-- Vehicle --}}
                        <td>{{ $req['period'] }}</td> {{-- Old and new end date --}}
                        <td>{{ $req['days'] }}</td> {{-- Extra days --}}
                        <td>{{ $req['cost'] }}</td> {{-- Amount --}}
                        <td><a href="{{ $req['proofUrl'] }}" target="_blank">View proof</a></td> {{-- Opens the tenant's proof in a new tab --}}
                        <td style="min-width: 320px"> {{-- Decision cell --}}
                            <form method="POST" action="{{ route('admin.extension-requests.accept', $req['id']) }}" enctype="multipart/form-data" class="d-flex gap-2 mb-2"> {{-- Accept needs a file, so multipart --}}
                                @csrf {{-- Anti-forgery token --}}
                                <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png" class="form-control form-control-sm" required> {{-- Payment evidence (jpg/png, max 1 MB) --}}
                                <button type="submit" class="btn btn-success btn-sm">Accept</button> {{-- Accept with the evidence --}}
                            </form> {{-- End of the accept form --}}
                            <form method="POST" action="{{ route('admin.extension-requests.reject', $req['id']) }}"> {{-- Reject form --}}
                                @csrf {{-- Anti-forgery token --}}
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject this extension request?')">Reject</button> {{-- Reject with a confirmation --}}
                            </form> {{-- End of the reject form --}}
                        </td> {{-- End of the decision cell --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- Nothing waiting --}}
                    <tr><td colspan="8" class="text-muted">No extension requests.</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the waiting box --}}

<div class="card shadow-sm"> {{-- Box for decided requests --}}
    <div class="card-header fw-bold">Recently decided</div> {{-- Box title --}}
    <div class="table-responsive"> {{-- Scroll wrapper --}}
        <table class="table mb-0 align-middle"> {{-- Plain table --}}
            <thead> {{-- Table header --}}
                <tr><th>Garage</th><th>Tenant</th><th>End date</th><th>Cost</th><th>Status</th><th>Decided at</th></tr> {{-- Column names --}}
            </thead> {{-- End of the header --}}
            <tbody> {{-- Table body --}}
                @forelse ($decidedRequests as $req) {{-- One row per decided request --}}
                    <tr> {{-- Start of the row --}}
                        <td>{{ $req['garage'] }}</td> {{-- Garage --}}
                        <td>{{ $req['tenant'] }}</td> {{-- Tenant --}}
                        <td>{{ $req['period'] }}</td> {{-- Old and new end date --}}
                        <td>{{ $req['cost'] }}</td> {{-- Amount --}}
                        <td><span class="badge bg-{{ $req['statusColor'] }}">{{ $req['status'] }}</span></td> {{-- Accepted / Rejected --}}
                        <td>{{ $req['decidedAt'] }}</td> {{-- Decision time --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- Nothing decided yet --}}
                    <tr><td colspan="6" class="text-muted">No decisions yet.</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the decided box --}}
@endsection {{-- End of this page's content --}}
