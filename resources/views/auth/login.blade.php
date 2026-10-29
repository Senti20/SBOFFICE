@extends('layouts.public')

@section('title', 'Admin Login')

@section('content')
<div class="card narrow-card" style="max-width: 400px; margin: 4rem auto;">
    <div style="text-align: center; margin-bottom: 2rem;">
        <div style="margin-bottom: 1rem;">
            <img src="{{ asset('images/logo.png') }}" alt="Municipality of Bontoc Logo" style="height:150px; width: auto; display: inline-block;">
        </div>
        <h1>Admin</h1>
        <p class="muted">Sign in to access the administrator panel.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field" style="margin-bottom: 1.25rem;">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>

        <div class="field" style="margin-bottom: 1.5rem;">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="width: 100%;">Sign In</button>
    </form>
</div>
@endsection