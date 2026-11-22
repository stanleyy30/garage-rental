@extends('layouts.admin') {{-- Admin page --}}

@section('title', 'Extension Requests') {{-- Tab title --}}

@section('content') {{-- Start of this page's content --}}
<h1 class="h3 mb-3">Extension Requests</h1> {{-- Page heading --}}
<p class="text-muted">Tenants asked for these extensions in the app. Check their payment proof, then accept the request with your payment evidence, or reject it. The tenant sees your decision on their dashboard.</p> {{-- Instruction --}}

@if ($errors->any() && ! old('extension_id')) {{-- An error that doesn't belong to one request's pop-up --}}
    <div class="alert alert-danger">{{ $errors->first() }}</div> {{-- Show it in red --}}
@endif {{-- End of the error --}}

<div class="card shadow-sm mb-4"> {{-- Box for waiting requests --}}
    <div class="card-header fw-bold">Waiting for a decision ({{ count($pendingRequests) }})</div> {{-- Box title --}}
    <div class="table-responsive"> {{-- Scroll wrapper for phones --}}
        <table class="table table-striped mb-0 align-middle"> {{-- Striped table --}}
            <thead> {{-- Table header --}}
                <tr><th>Garage</th><th>Tenant</th><th>Vehicle</th><th>End date</th><th>Extra</th><th>Cost</th><th class="text-end">Decision</th></tr> {{-- Column names --}}
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
                        <td class="text-end text-nowrap"> {{-- Decision buttons, all the same size --}}
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-viewer-src="{{ $req['proofUrl'] }}" data-viewer-title="Payment proof from {{ $req['tenant'] }}" data-viewer-caption="Amount to pay: {{ $req['cost'] }}">View proof</button> {{-- Opens the viewer, where the proof can be turned --}}
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#accept-{{ $req['id'] }}">Accept</button> {{-- Opens the accept pop-up --}}
                            <form method="POST" action="{{ route('admin.extension-requests.reject', $req['id']) }}" class="d-inline"> {{-- Reject form --}}
                                @csrf {{-- Anti-forgery token --}}
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject this extension request? The tenant will see it was rejected.')">Reject</button> {{-- Reject with a confirmation --}}
                            </form> {{-- End of the reject form --}}
                        </td> {{-- End of the decision cell --}}
                    </tr> {{-- End of the row --}}
                @empty {{-- Nothing waiting --}}
                    <tr><td colspan="7" class="text-muted">No extension requests.</td></tr> {{-- Empty message --}}
                @endforelse {{-- End of the rows --}}
            </tbody> {{-- End of the body --}}
        </table> {{-- End of the table --}}
    </div> {{-- End of the scroll wrapper --}}
</div> {{-- End of the waiting box --}}

@foreach ($pendingRequests as $req) {{-- One accept pop-up per waiting request --}}
    <div class="modal fade" id="accept-{{ $req['id'] }}" tabindex="-1" aria-labelledby="accept-title-{{ $req['id'] }}" aria-hidden="true" @if ((int) old('extension_id') === $req['id']) data-open-on-load @endif> {{-- Opens again by itself when it came back with an error --}}
        <div class="modal-dialog modal-dialog-centered"> {{-- Box in the middle of the screen --}}
            <form method="POST" action="{{ route('admin.extension-requests.accept', $req['id']) }}" enctype="multipart/form-data" class="modal-content" novalidate> {{-- Accept needs a file, so multipart; the server marks a missing file in red --}}
                @csrf {{-- Anti-forgery token --}}
                <input type="hidden" name="extension_id" value="{{ $req['id'] }}"> {{-- Tells the page which pop-up to reopen after an error --}}
                <div class="modal-header"> {{-- Top bar --}}
                    <h2 class="modal-title h5" id="accept-title-{{ $req['id'] }}">Accept extension of {{ $req['garage'] }}</h2> {{-- Title --}}
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> {{-- Close button --}}
                </div> {{-- End of the top bar --}}
                <div class="modal-body"> {{-- Middle part --}}
                    <table class="table table-sm mb-3"> {{-- Short summary --}}
                        <tr><th class="w-50">Tenant</th><td>{{ $req['tenant'] }}</td></tr> {{-- Tenant --}}
                        <tr><th>End date</th><td>{{ $req['period'] }}</td></tr> {{-- Old and new end date --}}
                        <tr><th>Extra days</th><td>{{ $req['days'] }}</td></tr> {{-- Extra days --}}
                        <tr><th>Amount</th><td>{{ $req['cost'] }}</td></tr> {{-- Amount --}}
                    </table> {{-- End of the summary --}}
                    <x-form-input name="payment_proof" label="Your payment evidence (jpg or png, max 1 MB)" type="file" accept=".jpg,.jpeg,.png" /> {{-- The admin's evidence --}}
                </div> {{-- End of the middle part --}}
                <div class="modal-footer"> {{-- Bottom bar --}}
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button> {{-- Close without saving --}}
                    <button type="submit" class="btn btn-success">Accept extension</button> {{-- Accept with the evidence --}}
                </div> {{-- End of the bottom bar --}}
            </form> {{-- End of the pop-up form --}}
        </div> {{-- End of the dialog --}}
    </div> {{-- End of the pop-up --}}
@endforeach {{-- End of the pop-ups --}}

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
