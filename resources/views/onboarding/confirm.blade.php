@extends('layouts.guest')

@section('title', 'Confirm onboarding')

@section('content')
    <main class="pf-confirm-page">
        <div class="pf-dialog">
            <p class="pf-step">Step 3 of 3</p>
            <h1>Confirm your setup</h1>
            <p class="muted">Review your career path and skills, then start your dashboard.</p>
            <dl>
                <dt>Career path</dt>
                <dd>{{ $path->path_name }}</dd>
                <dt>Skills</dt>
                <dd>
                    @if ($isBeginner)
                        Starting from the beginning. You can add skills later from your profile.
                    @elseif ($skills->isEmpty())
                        None selected yet. You can add skills later from your profile.
                    @else
                        <ul>
                            @foreach ($skills as $skill)
                                <li>{{ $skill->name }}</li>
                            @endforeach
                        </ul>
                    @endif
                </dd>
            </dl>
            <div class="pf-modal__actions">
                <a class="btn btn-secondary" href="{{ route('onboarding.skills') }}">Back</a>
                <form method="POST" action="{{ route('onboarding.complete') }}">
                    @csrf
                    <button class="pf-btn" type="submit">Confirm and go to dashboard</button>
                </form>
            </div>
        </div>
    </main>
@endsection
