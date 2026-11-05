<svg aria-hidden="true" class="icon" fill="none" height="{{ $size ?? 20 }}" viewBox="0 0 24 24" width="{{ $size ?? 20 }}">
    @switch($name)
        @case('arrow')
            <path d="M5 12h14m-5-5 5 5-5 5" />
            @break
        @case('arrow-up')
            <path d="M12 19V5m-7 7 7-7 7 7" />
            @break
        @case('bank')
            <path d="m3 9 9-6 9 6M5 10v8m4-8v8m6-8v8m4-8v8M3 21h18M2 18h20" />
            @break
        @case('bed')
            <path d="M3 19V6m0 9h18v4M3 11h5a4 4 0 0 1 4 4m0 0V9h5a4 4 0 0 1 4 4v2" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break
        @case('chevron')
            <path d="m9 18 6-6-6-6" />
            @break
        @case('guests')
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20c0-4 2-6 6-6s6 2 6 6M16 5a3 3 0 0 1 0 6M17 14c2.7.4 4 2.4 4 6" />
            @break
        @case('heart')
            <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" />
            @break
        @case('hotel')
            <path d="M4 21V8l8-5 8 5v13M2 21h20M8 21v-5h8v5M8 10h1M15 10h1" />
            @break
        @case('location')
            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" />
            <circle cx="12" cy="10" r="2.5" />
            @break
        @case('key')
            <circle cx="8" cy="15" r="5" />
            <path d="m11.5 11.5 9-9M17 6l2 2m-5 1 2 2" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('parking')
            <rect x="4" y="3" width="16" height="18" rx="3" />
            <path d="M10 17V7h4a3 3 0 0 1 0 6h-4" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-4-4" />
            @break
        @case('snowflake')
            <path d="M12 2v20m8.66-15-17.32 10m17.32 0L3.34 7m5.2-3 3.46 2 3.46-2m-6.92 16 3.46-2 3.46 2m5.66-10-3.46-2V4m-13.84 8 3.46 2v4" />
            @break
        @case('receipt')
            <rect x="5" y="3" width="14" height="18" rx="2" />
            <path d="M8 8h8M8 12h8M8 16h5" />
            @break
        @case('shield')
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Zm-3-10 2 2 4-5" />
            @break
        @case('star')
            <path d="m12 2 3 6 7 .9-5 4.8 1.3 6.8L12 17.3l-6.3 3.2L7 13.7 2 9l7-.9L12 2Z" />
            @break
        @case('ticket')
            <path d="M4 7a2 2 0 0 0 0 4v2a2 2 0 0 0 0 4h16a2 2 0 0 0 0-4v-2a2 2 0 0 0 0-4H4Z" />
            <path d="M13 7v2m0 3v2m0 3v1" />
            @break
        @case('utensils')
            <path d="M7 3v7m-3-7v4a3 3 0 0 0 6 0V3m-3 7v11m10-18v18m0-18c3 2 3 6 0 8" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="4" />
            <path d="M5 21a7 7 0 0 1 14 0" />
            @break
        @case('wifi')
            <path d="M2 8.5a15 15 0 0 1 20 0M5 12a10 10 0 0 1 14 0m-11 3.5a5 5 0 0 1 8 0M12 19h.01" />
            @break
    @endswitch
</svg>
