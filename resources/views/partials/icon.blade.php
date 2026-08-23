@php
    $icons = [
        'home' => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/>',
        'path' => '<circle cx="6" cy="6" r="2.2"/><circle cx="18" cy="12" r="2.2"/><circle cx="7" cy="18" r="2.2"/><path d="M8 7.2 16.2 11.2M16.4 13.8 8.8 16.8"/>',
        'brief' => '<path d="M8 7V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v1"/><rect x="4" y="7" width="16" height="13" rx="2"/><path d="M4 12h16"/>',
        'award' => '<circle cx="12" cy="9" r="5"/><path d="m8.5 13.2-1.5 8 5-2.2 5 2.2-1.5-8"/>',
        'spark' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.4 2.4M15.6 15.6 18 18M18 6l-2.4 2.4M8.4 15.6 6 18"/>',
        'user' => '<circle cx="12" cy="8" r="3.2"/><path d="M5 19.2c.8-3.2 3.4-5 7-5s6.2 1.8 7 5"/>',
        'grid' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
    ];
    $path = $icons[$name] ?? $icons['spark'];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $path !!}</svg>
