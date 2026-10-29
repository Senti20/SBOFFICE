<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard - SB Portal')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="SB Logo" style="height: 60px; width: auto; display: block;">
            </a>
            <div>
                <a class="brand" href="{{ route('admin.dashboard') }}">SB Portal</a>
                <span class="brand-subtitle">Administrator Portal</span>
            </div>
        </div>
    </div>
</header>

<main class="container page-content">
    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('status'))
        <div class="alert alert-info" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error" role="alert">
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer" style="text-align: center;">
    <div class="container" style="text-align: center;">SB Portal · Administrator Panel</div>
</footer>
</body>
</html>