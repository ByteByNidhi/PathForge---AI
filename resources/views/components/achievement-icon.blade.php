@props([
    'name',
])

@php
    $icons = [
        'flame' => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
        'compass' => '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
        'mountain' => '<path d="m8 3 4 8 5-5 5 15H2L8 3z"/>',
        'anvil' => '<path d="M6 3h12"/><path d="M6 3v2a4 4 0 0 0 4 4h.5"/><path d="M20 9H8.5"/><path d="M10 13v8"/><path d="M8 21h8"/><path d="M4 9h16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9z"/>',
        'lightning' => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
    ];
    $path = $icons[$name] ?? $icons['star'];
@endphp

<svg {{ $attributes->merge(['class' => 'achievement-icon', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '2', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    {!! $path !!}
</svg>
