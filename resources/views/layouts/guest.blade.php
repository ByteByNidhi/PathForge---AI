<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PathForge') | PathForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/pathforge.css') }}">
    @yield('head')
</head>
<body class="pf-body @yield('body_class')">
    @include('partials.atmosphere', ['density' => $density ?? 'calm'])
    @yield('content')
    <script src="{{ asset('js/pathforge-atmosphere.js') }}"></script>
    @yield('scripts')
</body>
</html>
