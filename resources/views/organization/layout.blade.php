<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Organization') | PathForge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    @include('partials.pf-assets')
    <link rel="stylesheet" href="{{ asset('css/pathforge.css') }}">
</head>

<body class="pf-body pf-staff">
    @include('partials.atmosphere', ['density' => 'calm'])
    <div class="pf-app">
        <aside class="pf-sidebar">
            <a class="pf-wordmark" href="{{ route('organization.dashboard') }}">Path<span>Forge</span></a>
            <nav class="pf-nav">
                <a href="{{ route('organization.dashboard') }}" class="{{ request()->routeIs('organization.dashboard') ? 'is-active' : '' }}">Dashboard</a>
                <a href="{{ route('organization.opportunities.index') }}" class="{{ request()->routeIs('organization.opportunities.*') ? 'is-active' : '' }}">My Opportunities</a>
                <a href="{{ route('organization.profile.edit') }}" class="{{ request()->routeIs('organization.profile.*') ? 'is-active' : '' }}">Profile</a>
                <a href="{{ route('organization.members.index') }}" class="{{ request()->routeIs('organization.members.*') ? 'is-active' : '' }}">Members</a>
            </nav>
            <div class="pf-sidebar__foot">
                <div class="pf-sidebar__user">
                    <strong>{{ auth()->user()?->currentOrganization()?->name }}</strong>
                    <small>{{ auth()->user()?->email }}</small>
                </div>
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
    @yield('scripts')
</body>

</html>