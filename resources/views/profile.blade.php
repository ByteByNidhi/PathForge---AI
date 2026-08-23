@extends('layouts.app')

@section('title', 'Profile')
@section('kicker', 'Identity')
@section('heading', 'Profile')
@section('lede', 'Your career identity, skills, and progression.')

@section('content')
    <div class="pf-dash">
        <section class="pf-card">
            <dl>
                <dt>Name</dt>
                <dd>{{ $user->name }}</dd>
                <dt>Email</dt>
                <dd>{{ $user->email }}</dd>
                <dt>Level</dt>
                <dd>{{ $user->level ?? 1 }}</dd>
                <dt>XP</dt>
                <dd>{{ $user->xp ?? 0 }}</dd>
                <dt>Roadmap</dt>
                <dd>{{ $user->learningPath?->path_name ?? 'None selected' }}</dd>
            </dl>
        </section>

        <section class="pf-card">
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
                <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Skill name" required>
                @error('name')
                    <p class="error">{{ $message }}</p>
                @enderror
                <button class="btn" type="submit">Add skill</button>
            </form>
        </section>
    </div>
@endsection
