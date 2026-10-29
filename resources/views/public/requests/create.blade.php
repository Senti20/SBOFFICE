@extends('layouts.public')

@section('title', 'Request a Certified Copy')

@section('content')
<div class="admin-toolbar">
    <div>
        <div class="eyebrow">PUBLIC SERVICE</div>
        <h1>Request a Certified Copy</h1>
        <p class="muted">Submit your request to the SB Secretary. Keep the tracking code shown after submission.</p>
    </div>
    <a class="btn btn-light" href="{{ route('home') }}">Back</a>
</div>

<section class="card">
    <form method="POST" action="{{ route('public.requests.store') }}" enctype="multipart/form-data" class="stack-form">
        @csrf

        <label for="requester_name">Full Name</label>
        <input id="requester_name" name="requester_name" value="{{ old('requester_name') }}" required maxlength="255" placeholder="e.g. Juan Dela Cruz">

        <label for="contact_number">Contact Number <span class="muted">(e.g., 09123456789)</span></label>
        <input id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required pattern="^(09|\+639)\d{9}$" maxlength="13" placeholder="09123456789">

        <label for="email">Email Address <span class="muted">(optional — for email notifications)</span></label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" placeholder="e.g. juan@gmail.com">

        <label for="document_id">Document</label>
        <select id="document_id" name="document_id" required>
            <option value="">— Select a document —</option>
            @foreach ($documents as $doc)
                <option value="{{ $doc->document_id }}" @selected((isset($document) && $document->document_id === $doc->document_id) || old('document_id') === $doc->document_id)>
                    {{ $doc->session_name }} ({{ $doc->type }})
                </option>
            @endforeach
        </select>

        <div class="two-col">
            <div>
                <label for="copies">Number of Copies</label>
                <input id="copies" name="copies" type="number" min="1" value="{{ old('copies', 1) }}" required>
            </div>
            <div>
                <label for="pickup_date">Preferred Pickup Date</label>
                <input id="pickup_date" name="pickup_date" type="date" value="{{ old('pickup_date') }}" required>
            </div>
        </div>

        <label for="reason">Reason / Purpose</label>
        <input id="reason" name="reason" value="{{ old('reason') }}" required maxlength="255" placeholder="e.g. Legal reference, educational purposes">

        <label for="valid_id">Valid ID <span class="muted">(JPEG, PNG, JPG - max 5MB)</span></label>
        <input id="valid_id" name="valid_id" type="file" accept="image/jpeg,image/png,image/jpg" required>

        <label for="selfie">Selfie <span class="muted">(upload a photo or take one with your camera - JPEG, PNG, JPG - max 5MB)</span></label>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <button type="button" id="selfie-start" class="btn btn-light" style="display: none; flex-shrink: 0; align-items: center; justify-content: center;" aria-label="Take a selfie with the camera" title="Take a selfie with the camera">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
            </button>
            <input id="selfie" name="selfie" type="file" accept="image/jpeg,image/png,image/jpg" required style="flex: 1; min-width: 0;">
        </div>

        <div id="selfie-camera" style="display: none;">
            <video id="selfie-video" autoplay playsinline muted style="width: 100%; max-width: 360px; border-radius: 8px; background: #000; transform: scaleX(-1);"></video>
            <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                <button type="button" id="selfie-capture" class="btn btn-primary">Capture</button>
                <button type="button" id="selfie-cancel" class="btn btn-light">Cancel</button>
            </div>
        </div>

        <div id="selfie-preview" style="display: none;">
            <img id="selfie-preview-img" alt="Selfie preview" style="width: 100%; max-width: 240px; border-radius: 8px;">
        </div>

        <p id="selfie-msg" class="muted" style="margin: 0;"></p>

        <button class="btn btn-primary" type="submit">Submit Request</button>
    </form>
</section>

<script>
(function () {
    var input = document.getElementById('selfie');
    var startBtn = document.getElementById('selfie-start');
    var camBox = document.getElementById('selfie-camera');
    var video = document.getElementById('selfie-video');
    var captureBtn = document.getElementById('selfie-capture');
    var cancelBtn = document.getElementById('selfie-cancel');
    var previewBox = document.getElementById('selfie-preview');
    var previewImg = document.getElementById('selfie-preview-img');
    var msg = document.getElementById('selfie-msg');
    var stream = null;
    var previewUrl = null;

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        video.srcObject = null;
        camBox.style.display = 'none';
    }

    function showPreview(file) {
        if (previewUrl) { URL.revokeObjectURL(previewUrl); }
        previewUrl = URL.createObjectURL(file);
        previewImg.src = previewUrl;
        previewBox.style.display = 'block';
    }

    // Show the camera button only when the browser and device can support it.
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.DataTransfer) {
        startBtn.style.display = 'inline-flex';
    } else {
        msg.textContent = 'Camera is not available on this device. Please upload a photo instead.';
    }

    // Preview a photo chosen with the upload field.
    input.addEventListener('change', function () {
        if (input.files && input.files[0]) {
            showPreview(input.files[0]);
            msg.textContent = '';
        } else {
            previewBox.style.display = 'none';
        }
    });

    startBtn.addEventListener('click', function () {
        msg.textContent = '';
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then(function (s) {
                stream = s;
                video.srcObject = s;
                camBox.style.display = 'block';
            })
            .catch(function () {
                msg.textContent = 'Could not access the camera. Please allow camera access, or upload a photo instead.';
            });
    });

    captureBtn.addEventListener('click', function () {
        if (!video.videoWidth) {
            msg.textContent = 'The camera is not ready yet. Please try again.';
            return;
        }

        var canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(function (blob) {
            if (!blob) {
                msg.textContent = 'Could not capture the photo. Please try again or upload a photo instead.';
                return;
            }

            var file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;

            showPreview(file);
            stopCamera();
        }, 'image/jpeg', 0.9);
    });

    cancelBtn.addEventListener('click', stopCamera);
    window.addEventListener('beforeunload', stopCamera);
    input.form.addEventListener('submit', stopCamera);
})();
</script>
@endsection