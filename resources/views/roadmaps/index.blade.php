@extends('layouts.app')

@section('title', 'Roadmaps')
@section('kicker', 'Curriculum')
@section('heading', 'Learning roadmaps')
@section('lede', 'Select a roadmap to save it to your profile, then open it to complete steps.')

@section('content')
    @forelse ($paths as $path)
        <article class="pf-card path" style="margin-bottom:14px;">
            @if ((int) $selectedPathId === (int) $path->id)
                <div class="selected">Your selected roadmap</div>
            @endif
            <h2>{{ $path->path_name }}</h2>
            <p class="muted">{{ $path->description }}</p>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('roadmaps.show', $path) }}">View steps</a>
                @if ((int) $selectedPathId !== (int) $path->id)
                    <form method="POST" action="{{ route('roadmaps.select', $path) }}">
                        @csrf
                        <button class="btn" type="submit">Select this roadmap</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <p>No roadmaps are available yet.</p>
    @endforelse
@endsection
