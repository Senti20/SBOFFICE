@extends('layouts.public')

@section('title', 'Request Submitted')

@section('content')
<section class="hero card">
    <div>
        <div class="eyebrow">REQUEST RECEIVED</div>
        <h1>Your request has been submitted</h1>

        <p class="muted">
            We will notify you via SMS
            @if($request->email)
                and email ({{ $request->email }})
            @endif
            once your certified copy is ready for pickup.
        </p>
    </div>
</section>

<section class="card narrow-card">
    <h2>Request Details</h2>

    <div class="details-list">
        <div class="detail-item">
            <span class="muted">Request Reference</span>
            <strong>{{ $request->request_id }}</strong>
        </div>

        <div class="detail-item">
            <span class="muted">Document</span>
            <strong>{{ $request->document->session_name ?? $request->document_id }}</strong>
        </div>

        <div class="detail-item">
            <span class="muted">Copies</span>
            <strong>{{ $request->copies }}</strong>
        </div>

        <div class="detail-item">
            <span class="muted">Pickup Date</span>
            <strong>{{ \Carbon\Carbon::parse($request->pickup_date)->format('F d, Y') }}</strong>
        </div>

        <div class="detail-item">
            <span class="muted">Status</span>
            <strong>
                <span class="badge badge-warning">{{ $request->status }}</span>
            </strong>
        </div>
    </div>

    <div class="actions-end" style="margin-top: 1rem;">
        <a
            class="btn btn-primary btn-block"
            href="{{ route('public.requests.index') }}"
        >
            View My Requests
        </a>

        <a class="btn btn-secondary btn-block" href="{{ route('home') }}">
            Back to Archive
        </a>
    </div>
</section>
@endsection