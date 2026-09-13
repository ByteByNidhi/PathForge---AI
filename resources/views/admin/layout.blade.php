<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | PathForge Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    @include('partials.pf-assets')
    <link rel="stylesheet" href="{{ asset('css/pathforge.css') }}">
</head>

<body class="pf-body">
    @include('partials.atmosphere', ['density' => 'calm'])
    <div class="pf-app">
        <aside class="pf-sidebar">
            <a class="pf-wordmark" href="{{ route('admin.dashboard') }}">Path<span>Forge</span></a>
            <nav class="pf-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.opportunities.index') }}" class="{{ request()->routeIs('admin.opportunities.*') ? 'is-active' : '' }}">Opportunities</a>
                <a href="{{ route('admin.organizations.index') }}" class="{{ request()->routeIs('admin.organizations.*') ? 'is-active' : '' }}">Organizations</a>
                <a href="{{ route('admin.roadmaps.index') }}" class="{{ request()->routeIs('admin.roadmaps.*') ? 'is-active' : '' }}">Roadmaps</a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">Users</a>
                <a href="{{ route('admin.career-path-requests.index') }}" class="{{ request()->routeIs('admin.career-path-requests.*') ? 'is-active' : '' }}">Career Path Requests</a>
                <a href="{{ route('admin.subscriptions.index') }}" class="{{ request()->routeIs('admin.subscriptions.*') ? 'is-active' : '' }}">Subscriptions</a>
            </nav>
            <div class="pf-sidebar__foot">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="pf-btn pf-logout" type="submit">Logout</button>
                </form>
            </div>
        </aside>
        <div class="pf-main">
            @if (session('success'))
            <div class="pf-flash">{{ session('success') }}</div>
            @endif
            @if (session('error'))
            <div class="pf-flash pf-flash--error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
            <div class="pf-flash pf-flash--error">{{ $errors->first() }}</div>
            @endif
            @yield('content')
        </div>
    </div>
    @include('partials.confirm-dialog')
    <script src="{{ asset('js/pathforge-atmosphere.js') }}"></script>
    @include('partials.pf-scripts')
</body>

</html>