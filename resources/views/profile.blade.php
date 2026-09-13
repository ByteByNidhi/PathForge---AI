@extends('layouts.app')

@section('title', 'Profile')
@section('kicker', 'Identity')
@section('heading', 'Profile')
@section('lede', 'Your career identity, skills, and progression.')

@section('content')
    @php
        $initials = collect(explode(' ', (string) $user->name))
            ->filter()
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');
        $xpIntoLevel = method_exists($user, 'xpIntoLevel') ? $user->xpIntoLevel() : (($user->xp ?? 0) % 100);
    @endphp

    <div class="pf-profile">
        <header class="pf-profile__mast">
            <div class="pf-avatar">{{ $initials ?: 'PF' }}</div>
            <div>
                <p class="pf-kicker">User profile</p>
                <h2>{{ $user->name }}</h2>
                <p class="muted">{{ $user->email }}</p>
            </div>
            <div>
                <a class="btn btn-secondary" href="{{ url('/dashboard') }}">Dashboard</a>
            </div>
        </header>

        <div class="pf-profile__grid">
            <section class="pf-card pf-identity">
                <p class="pf-kicker">Profile / identity</p>
                <h2>Identity</h2>
                <dl>
                    <div>
                        <dt>Name</dt>
                        <dd>{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd>{{ $user->email }}</dd>
                    </div>
                </dl>
            </section>

            <section class="pf-card">
                <p class="pf-kicker">Career progress</p>
                <h2>Level {{ $user->level ?? 1 }}</h2>
                <p class="muted">{{ $user->xp ?? 0 }} XP total</p>
                <p class="muted" style="margin-top:16px;">Progress to next level</p>
                <div class="pf-progress pf-progress--spaced" @style(['--pf-fill' => ((int) $xpIntoLevel).'%'])><span></span></div>
                <p class="muted">{{ $xpIntoLevel }} / 100 XP in this level</p>
            </section>

            <section class="pf-card pf-identity">
                <p class="pf-kicker">Career information</p>
                <h2>Path</h2>
                <dl>
                    <div>
                        <dt>Roadmap</dt>
                        <dd>{{ $user->learningPath?->path_name ?? 'None selected' }}</dd>
                    </div>
                    <div>
                        <dt>Skills recorded</dt>
                        <dd>{{ $skills->count() }}</dd>
                    </div>
                </dl>
            </section>

            <section class="pf-card pf-card--wide">
                <h2>Your skills</h2>
                @if ($skills->isEmpty())
                    <p class="muted">You have not added any skills yet. Add skills so Opportunity Hub can calculate your match.</p>
                @else
                    <ul class="skill-list">
                        @foreach ($skills as $skill)
                            <li>
                                <span>{{ $skill->name }}</span>
                                <form method="POST" action="{{ route('profile.skills.destroy', $skill) }}">
                                    @csrf
                                    <button class="btn btn-secondary" type="submit">Remove</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <form method="POST" action="{{ route('profile.skills.store') }}">
                    @csrf
                    <label for="name">Add a skill</label>
                    <div class="pf-row">
                        <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Skill name" required style="flex:1; min-width:200px;">
                        <button class="btn" type="submit">Add skill</button>
                    </div>
                    @error('name')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </form>
            </section>
        </div>
    </div>
@endsection
