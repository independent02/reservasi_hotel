@props(['variant' => 'desktop', 'showTheme' => true])

<div class="navbar-preferences navbar-preferences-{{ $variant }}" aria-label="{{ __('Pengaturan tampilan') }}">
    <div class="navbar-locale-switch" role="group" aria-label="{{ __('Pilih bahasa') }}">
        <svg class="navbar-locale-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M3 12h18M12 3a14 14 0 0 1 0 18m0-18a14 14 0 0 0 0 18"></path>
        </svg>
        @foreach (['id' => 'ID', 'en' => 'EN'] as $locale => $label)
            <form action="{{ route('locale.switch', $locale) }}" method="get">
                <button
                    type="submit"
                    aria-label="{{ $locale === 'id' ? __('Bahasa Indonesia') : __('Bahasa Inggris') }}"
                    aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                >
                    {{ $label }}
                </button>
            </form>
        @endforeach
    </div>
    @if ($showTheme)
        <button
            class="navbar-theme-toggle"
            type="button"
            data-theme-toggle
            aria-label="{{ __('Aktifkan mode gelap') }}"
            aria-pressed="false"
        >
            <svg class="navbar-moon-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M20.2 15.4A8.5 8.5 0 0 1 8.6 3.8 8.7 8.7 0 1 0 20.2 15.4Z" />
            </svg>
            <svg class="navbar-sun-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="4" />
                <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
            </svg>
        </button>
    @endif
</div>
