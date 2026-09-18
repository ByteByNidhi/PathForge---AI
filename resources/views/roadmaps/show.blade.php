@extends('layouts.app')

@section('title', $path->path_name)
@section('kicker', 'Roadmap')
@section('heading', $path->path_name)
@section('lede', $path->description)

@section('content')
    <div class="pf-roadmap-track">
    @if ($isSelected)
        <p class="done">This is your selected roadmap.</p>
    @else
        <form method="POST" action="{{ route('roadmaps.select', $path) }}" style="margin-bottom: 16px;">
            @csrf
            <button class="btn" type="submit">Select this roadmap</button>
        </form>
    @endif

    @forelse ($steps as $step)
        @php
            $progress = $progressByStepId[$step->id] ?? null;
            $completed = $progress && $progress->status === 'completed';
        @endphp
        <article class="pf-card step">
            <h2>Step {{ $step->step_no }}: {{ $step->title }}</h2>
            @if ($step->description)
                <p>{{ $step->description }}</p>
            @endif
            <p class="meta muted">XP reward: {{ $step->xp_reward }}</p>
            @if ($step->skills->isNotEmpty())
                <div class="chips">
                    @foreach ($step->skills as $skill)
                        <span class="chip">{{ $skill->name }}</span>
                    @endforeach
                </div>
            @endif
            @if ($completed)
                <p class="done">Completed</p>
            @elseif ($isSelected && $completableStepIds->contains($step->id))
                <form method="POST" action="{{ route('roadmaps.complete', [$path, $step]) }}">
                    @csrf
                    <button class="btn" type="submit">Mark complete</button>
                </form>
            @elseif ($isSelected)
                <p class="muted">Locked</p>
            @else
                <p class="muted">Select this roadmap to complete its steps.</p>
            @endif
        </article>
    @empty
        <p>This roadmap has no steps yet.</p>
    @endforelse
    </div>
@endsection
