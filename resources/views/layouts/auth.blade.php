<!doctype html>
<html lang="{{ app()->getLocale() }}" class="auth-layout-root">
<head>
    @include('partials.preferences-head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — @yield('brandName', 'Hotel HAR Official')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page @yield('bodyClass')">
    @php($isAccountPage = request()->routeIs('login', 'admin.login', 'register', 'password.request', 'account.password.edit'))
    @php($isAdminPage = request()->routeIs('admin.dashboard', 'admin.payment-reviews.index'))
    @unless (request()->routeIs('reservations.index', 'reservations.create', 'reservations.guest', 'reservations.show', 'rooms.show', 'hotels.reviews.show') || $isAccountPage || $isAdminPage)
        <x-preferences-controls variant="floating" />
    @endunless
    @if (request()->routeIs('reservations.index'))
        <x-navbar />
    @elseif (request()->routeIs('reservations.create', 'reservations.guest', 'reservations.show'))
        <header class="flow-topbar">
            <a class="logo" href="{{ route('home') }}" aria-label="@yield('brandName', 'Hotel HAR Official'), {{ __('Beranda') }}">
                <img class="brand-mark" src="{{ asset('assets/logo.png') }}" alt="">
                @yield('brandName', 'Hotel HAR Official')
            </a>
            <div class="flow-topbar-actions">
                <a class="flow-topbar-action" href="{{ route('reservations.index') }}">{{ __('Kembali ke pesanan saya') }}</a>
                <x-preferences-controls variant="topbar" />
            </div>
        </header>
    @elseif (request()->routeIs('rooms.show'))
        <header class="flow-topbar room-detail-topbar">
            <a class="logo" href="{{ route('home') }}" aria-label="@yield('brandName', 'Hotel HAR Official'), {{ __('Beranda') }}">
                <img class="brand-mark" src="{{ asset('assets/logo.png') }}" alt="">
                @yield('brandName', 'Hotel HAR Official')
            </a>
            <div class="flow-topbar-actions">
                <a class="flow-topbar-action" href="{{ route('home') }}#koleksi">{{ __('Kembali ke pilihan kamar') }}</a>
                <x-preferences-controls variant="topbar" />
            </div>
        </header>
    @else
        @if (request()->routeIs('hotels.reviews.show'))
            <header class="hotel-reviews-topbar">
                <div class="hotel-reviews-topbar-inner">
                    <a class="logo" href="{{ route('home') }}" aria-label="@yield('brandName', 'Hotel HAR Official'), {{ __('Beranda') }}">
                        <img class="brand-mark" src="{{ asset('assets/logo.png') }}" alt="">
                        @yield('brandName', 'Hotel HAR Official')
                    </a>
                    <a class="hotel-reviews-nav-back" href="{{ $reviewBackUrl }}"><span>{{ __('Kembali') }}</span></a>
                    <x-preferences-controls variant="topbar" />
                </div>
            </header>
        @elseif ($isAccountPage || $isAdminPage)
            <header class="auth-account-topbar">
                <a class="logo" href="{{ $isAdminPage ? route('admin.dashboard') : route('home') }}" aria-label="@yield('brandName', 'Hotel HAR Official'), {{ $isAdminPage ? __('Dashboard Admin') : __('Beranda') }}">
                    <img class="brand-mark" src="{{ asset('assets/logo.png') }}" alt="">
                    @yield('brandName', 'Hotel HAR Official')
                </a>
                <x-preferences-controls variant="desktop" />
            </header>
        @else
            <a class="auth-brand logo" href="{{ route('home') }}" aria-label="@yield('brandName', 'Hotel HAR Official'), {{ __('Beranda') }}">
                <img class="brand-mark" src="{{ asset('assets/logo.png') }}" alt="">
                @yield('brandName', 'Hotel HAR Official')
            </a>
        @endif
    @endif

    <main class="auth-main @yield('mainClass')">
        <section class="auth-card card @yield('cardClass')">
            @yield('cardIntro')
            <span class="kicker">@yield('eyebrow')</span>
            <h1 class="title">@yield('heading')</h1>
            <p class="auth-intro">@yield('intro')</p>

            @if (session('status'))
                <div class="auth-success" role="status">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div class="auth-error" role="alert">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="auth-error" role="alert">
                    @if ($errors->has('payment'))
                        <p>{{ __('Pembayaran belum berhasil. Periksa konfigurasi pembayaran berikut.') }}</p>
                    @else
                        <p>{{ __('Periksa kembali data yang Anda masukkan.') }}</p>
                    @endif
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('form')

            @unless (request()->routeIs('reservations.create', 'reservations.guest', 'reservations.show', 'rooms.show'))
                <p class="auth-switch">@yield('switch')</p>
            @endunless
        </section>
    </main>
    @include('partials.help')
</body>
</html>
