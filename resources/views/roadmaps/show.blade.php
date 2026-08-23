@extends('layouts.app')

@section('title', $path->path_name)
@section('kicker', 'Roadmap')
@section('heading', $path->path_name)
@section('lede', $path->description)

@section('content')
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
        <article class="pf-card step" style="margin-bottom:12px;">
            <h2>Step {{ $step->step_no }}: {{ $step->title }}</h2>
            <p class="meta muted">XP reward: {{ $step->xp_reward }}</p>
            @if ($completed)
                <p class="done">Completed</p>
            @else
                <form method="POST" action="{{ route('roadmaps.complete', [$path, $step]) }}">
                    @csrf
                    <button class="btn" type="submit">Mark complete</button>
                </form>
            @endif
        </article>
    @empty
        <p>This roadmap has no steps yet.</p>
    @endforelse
@endsection
