@php
    $achievement = $item['achievement'];
    $state = $item['unlocked'] ? 'unlocked' : 'locked';
@endphp

<article
    class="achievement-badge achievement-badge--{{ $achievement->rarity }} achievement-badge--{{ $state }}"
    data-achievement-slug="{{ $achievement->slug }}"
    data-rarity="{{ $achievement->rarity }}"
    data-unlocked="{{ $item['unlocked'] ? 'true' : 'false' }}"
    data-icon="{{ $achievement->icon }}"
    data-progress="{{ $item['progress_percent'] }}"
>
    <div class="achievement-badge__shape" data-badge-shape="hex">
        <x-achievement-icon :name="$achievement->icon" />
    </div>
    <div>
        <p class="achievement-badge__name">{{ $achievement->name }}</p>
        <p class="achievement-badge__meta">Rarity: {{ ucfirst($achievement->rarity) }}</p>
        <p class="achievement-badge__description">{{ $achievement->description }}</p>
        @if ($item['unlocked'])
            <p class="achievement-badge__date">Unlocked {{ $item['unlocked_at']?->format('M j, Y') }}</p>
        @else
            <p class="achievement-badge__progress">
                Progress: {{ $item['current'] }} / {{ $item['target'] }}
            </p>
        @endif
    </div>
</article>
