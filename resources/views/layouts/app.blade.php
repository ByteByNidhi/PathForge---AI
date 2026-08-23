<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | PathForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/pathforge.css') }}">
    @yield('head')
</head>
<body class="pf-body">
    @include('partials.atmosphere', ['density' => 'calm'])
    <div class="pf-app">
        @include('partials.sidebar')
        <div class="pf-main">
            <div class="pf-topbar">
                <div>
                    <p class="pf-kicker">@yield('kicker', 'PathForge')</p>
                    <h1>@yield('heading')</h1>
                    @hasSection('lede')
                        <p class="pf-lede">@yield('lede')</p>
                    @endif
                </div>
                <button class="pf-menu-toggle" type="button" data-pf-menu aria-label="Open navigation">☰</button>
            </div>
            @if (session('success'))
                <div class="pf-flash">{{ session('success') }}</div>
            @endif
            @yield('content')
        </div>
    </div>
    <script src="{{ asset('js/pathforge-atmosphere.js') }}"></script>
    <script src="{{ asset('js/pathforge-ui.js') }}"></script>
    @yield('scripts')
</body>
</html>
