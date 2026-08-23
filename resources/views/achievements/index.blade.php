@extends('layouts.app')

@section('title', 'Achievements')
@section('kicker', 'Milestones')
@section('heading', 'Achievements')
@section('lede', 'Unlock badges as you complete roadmap steps, gain XP, and add skills.')

@section('content')
    <h2>Unlocked badges</h2>
    @if ($unlocked->isEmpty())
        <p class="empty">You have not unlocked any badges yet.</p>
    @else
        <div class="badge-grid">
            @foreach ($unlocked as $item)
                @include('achievements.badge', ['item' => $item])
            @endforeach
        </div>
    @endif

    <h2>Locked badges</h2>
    @if ($locked->isEmpty())
        <p class="empty">You have unlocked every badge.</p>
    @else
        <div class="badge-grid">
            @foreach ($locked as $item)
                @include('achievements.badge', ['item' => $item])
            @endforeach
        </div>
    @endif
@endsection
