@php
    $authUser = auth()->user();
    $initials = collect(explode(' ', (string) $authUser?->name))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp

<aside class="pf-sidebar">
    <a class="pf-brand" href="{{ url('/dashboard') }}">Path<span>Forge</span></a>

    <nav class="pf-nav" aria-label="Primary">
        <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'is-active' : '' }}">
            <span class="pf-nav-icon">@include('partials.icon', ['name' => 'home'])</span>
            Dashboard
        </a>
        <a href="{{ route('roadmaps.index') }}" class="{{ request()->routeIs('roadmaps.*') ? 'is-active' : '' }}">
            <span class="pf-nav-icon">@include('partials.icon', ['name' => 'path'])</span>
            Roadmaps
        </a>
        <a href="{{ route('opportunities.index') }}" class="{{ request()->routeIs('opportunities.*') ? 'is-active' : '' }}">
            <span class="pf-nav-icon">@include('partials.icon', ['name' => 'brief'])</span>
            Opportunity Hub
        </a>
        <a href="{{ route('achievements.index') }}" class="{{ request()->routeIs('achievements.*') ? 'is-active' : '' }}">
            <span class="pf-nav-icon">@include('partials.icon', ['name' => 'award'])</span>
            Achievements
        </a>
        <a href="{{ url('/ai-studio') }}" class="{{ request()->is('ai-studio') ? 'is-active' : '' }}">
            <span class="pf-nav-icon">@include('partials.icon', ['name' => 'spark'])</span>
            AI Studio
        </a>
        <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') || request()->routeIs('profile.*') ? 'is-active' : '' }}">
            <span class="pf-nav-icon">@include('partials.icon', ['name' => 'user'])</span>
            Profile
        </a>
        @if ($authUser?->isAdmin())
            <a href="{{ url('/admin') }}" class="{{ request()->is('admin*') ? 'is-active' : '' }}">
                <span class="pf-nav-icon">@include('partials.icon', ['name' => 'grid'])</span>
                Admin
            </a>
        @endif
    </nav>

    <div class="pf-sidebar__foot">
        <div class="pf-sidebar__user">
            <div class="pf-avatar">{{ $initials ?: 'PF' }}</div>
            <div>
                <strong>{{ $authUser?->name }}</strong>
                <small>Level {{ $authUser?->level ?? 1 }} · {{ $authUser?->xp ?? 0 }} XP</small>
            </div>
        </div>
        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button class="pf-btn pf-logout" type="submit">Logout</button>
        </form>
    </div>
</aside>
