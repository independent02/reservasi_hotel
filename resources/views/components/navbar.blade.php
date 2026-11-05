@php($isHomePage = request()->routeIs('home'))

<header class="header landing-header" data-navbar>
    <div class="header-inner">
        <a class="logo" href="{{ route('home') }}" aria-label="Hotel HAR Official, Beranda">
            <img class="brand-mark" src="{{ asset('assets/logo.png') }}" alt="">
            <span>Hotel HAR Official</span>
        </a>

        <nav id="landing-navigation" data-navigation aria-label="{{ __('Navigasi utama') }}">
            <a
                @class(['is-current' => $isHomePage])
                href="{{ $isHomePage ? '#beranda' : route('home').'#beranda' }}"
                @if ($isHomePage) aria-current="location" @endif
            >{{ __('Beranda') }}</a>
            <a href="{{ $isHomePage ? '#koleksi' : route('home').'#koleksi' }}">{{ __('Temukan Hotel') }}</a>
            <a href="{{ $isHomePage ? '#cara' : route('home').'#cara' }}">{{ __('Cara Pesan') }}</a>

            @auth
                <a
                    @class(['mobile-nav-action', 'is-current' => request()->routeIs('reservations.index')])
                    href="{{ route('reservations.index') }}"
                    @if (request()->routeIs('reservations.index')) aria-current="page" @endif
                >{{ __('Pesanan saya') }}</a>
                <span class="mobile-nav-user">{{ Auth::user()->name }}</span>
                <form class="mobile-nav-action" action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit">{{ __('Keluar') }}</button>
                </form>
            @else
                <a class="mobile-nav-action" href="{{ route('login') }}">{{ __('Masuk') }}</a>
            @endauth

            <x-preferences-controls variant="mobile" />
        </nav>

        <div class="header-actions">
            @auth
                <a
                    @class(['button', 'soft', 'header-bookings', 'is-current' => request()->routeIs('reservations.index')])
                    href="{{ route('reservations.index') }}"
                    @if (request()->routeIs('reservations.index')) aria-current="page" @endif
                >{{ __('Pesanan saya') }}</a>
                <span class="signed-in" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</span>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="button soft header-logout" type="submit">{{ __('Keluar') }}</button>
                </form>
            @else
                <a class="button ghost header-login" href="{{ route('login') }}">{{ __('Masuk') }}</a>
            @endauth

            <x-preferences-controls variant="desktop" />

            <button class="button menu-button" data-menu-toggle type="button" aria-label="{{ __('Buka navigasi') }}" aria-expanded="false" aria-controls="landing-navigation">
                <x-icon name="menu" />
            </button>
        </div>
    </div>
</header>
