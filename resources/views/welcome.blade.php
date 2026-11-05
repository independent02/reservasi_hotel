<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.preferences-head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ __('Temukan hotel pilihan dengan harga transparan dan proses reservasi yang tenang bersama Hotel HAR Official.') }}">
    <title>{{ __('Hotel HAR Official — Menginap yang terasa tepat') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main>
    <x-navbar />

    <section class="hero" id="beranda">
        <video aria-hidden="true" autoplay loop muted playsinline>
            <source src="{{ asset('assets/hotell.mp4') }}?v={{ filemtime(public_path('assets/hotell.mp4')) }}" type="video/mp4">
        </video>
        <div class="hero-shade"></div>
        <div class="hero-content shell">
            <span class="badge hero-badge">{{ __('Curated stays, effortless moments') }}</span>
            <h1 class="display">{{ __('Waktunya') }} <em>{{ __('benar-benar beristirahat.') }}</em></h1>
            <p>{{ __('Hotel pilihan dengan kenyamanan istimewa, harga transparan, dan proses reservasi yang tenang dari awal hingga tiba.') }}</p>

            <form class="search-card card" action="{{ route('hotels.search') }}" method="get">
                <label class="search-field search-field-location">
                    <span><x-icon name="location" /></span>
                    <div>
                        <small>{{ __('Lokasi hotel') }}</small>
                        <select aria-label="{{ __('Lokasi hotel') }}" name="destination" data-search-destination>
                            <option value="" @selected(old('destination', $filters['destination']) === '')>{{ __('Semua lokasi') }}</option>
                            @foreach ($destinations as $destination)
                                <option value="{{ $destination }}" @selected(old('destination', $filters['destination']) === $destination)>{{ $destination }}</option>
                            @endforeach
                        </select>
                        <small class="search-hint">{{ __('Pilih lokasi, lalu tekan Enter') }}</small>
                    </div>
                </label>
                <label class="search-field">
                    <span><x-icon name="calendar" /></span>
                    <div>
                        <small>{{ __('Check-in') }}</small>
                        <span class="search-date-time">
                            <input aria-label="{{ __('Tanggal check-in') }}" type="date" name="checkin" value="{{ old('checkin', $filters['checkin']) }}" min="{{ now()->toDateString() }}" required>
                            <input aria-label="{{ __('Jam check-in') }}" type="time" name="checkin_time" value="{{ old('checkin_time', $filters['checkin_time'] ?? '14:00') }}" required>
                        </span>
                    </div>
                </label>
                <label class="search-field">
                    <span><x-icon name="calendar" /></span>
                    <div>
                        <small>{{ __('Check-out') }}</small>
                        <span class="search-date-time">
                            <input aria-label="{{ __('Tanggal check-out') }}" type="date" name="checkout" value="{{ old('checkout', $filters['checkout']) }}" min="{{ now()->addDay()->toDateString() }}" required>
                            <input aria-label="{{ __('Jam check-out') }}" type="time" name="checkout_time" value="{{ old('checkout_time', $filters['checkout_time'] ?? '12:00') }}" required>
                        </span>
                    </div>
                </label>
                <label class="search-field search-field-guests">
                    <span><x-icon name="guests" /></span>
                    <div>
                        <small>{{ __('Tamu') }}</small>
                        <span class="select-pair">
                            <select
                                aria-label="{{ __('Jumlah tamu') }}"
                                name="guests"
                                data-search-guests
                                data-max-guests-per-room="{{ $maxGuestsPerRoom }}"
                            >
                                @for ($count = 1; $count <= min(8, $maxGuestsPerRoom); $count++)
                                    <option value="{{ $count }}" @selected((int) $filters['guests'] === $count)>{{ __(':count tamu', ['count' => $count]) }}</option>
                                @endfor
                            </select>
                        </span>
                    </div>
                </label>
                <input type="hidden" name="rooms" value="1">
                <button class="search-submit-hidden" type="submit" tabindex="-1" aria-hidden="true"></button>
            </form>
            @if ($errors->any())
                <p class="search-error" role="alert">{{ $errors->first() }}</p>
            @endif
        </div>
    </section>

    <section class="section-space shell" id="koleksi">
        <div class="section-head">
            <div>
                <span class="kicker">{{ __('Pilihan paling dicintai') }}</span>
                <h2 class="title">{{ __('Kamar untuk setiap cara beristirahat') }}</h2>
            </div>
            <a class="button soft" href="{{ route('hotels.search', array_merge($filters, [
                'destination' => '',
                'checkin' => now()->toDateString(),
                'checkout' => now()->addDays(1)->toDateString(),
                'all_hotels' => 1,
            ])) }}">
                {{ __('Lihat semua hotel') }} <x-icon name="arrow" size="17" />
            </a>
        </div>

        <div class="room-types">
            @forelse ($rooms as $room)
                <article class="type-card">
                    <div class="type-photo">
                        <a
                            href="{{ route('rooms.show', array_merge(['room' => $room], $filters)) }}"
                            aria-label="{{ __('Lihat detail :room di :hotel', ['room' => $room->name, 'hotel' => $room->hotel->name]) }}"
                        >
                            <img alt="{{ __($room->description) }}" src="{{ asset('assets/'.$room->image) }}">
                        </a>
                        <span class="badge">{{ $room->name }}</span>
                        <button aria-label="{{ __('Simpan :room', ['room' => $room->name]) }}" aria-pressed="false" class="heart" data-favorite type="button">
                            <x-icon name="heart" size="18" />
                        </button>
                    </div>
                    <div class="type-info">
                        <div>
                            <h3>
                                <a class="room-title-link" href="{{ route('rooms.show', array_merge(['room' => $room], $filters)) }}">
                                    {{ $room->hotel->name }}
                                </a>
                            </h3>
                            <p>{{ __($room->description) }}</p>
                        </div>
                        <strong>Mulai Rp{{ number_format((float) $room->price_per_night, 0, ',', '.') }}</strong>
                    </div>
                </article>
            @empty
                <p class="empty-state">{{ __('Hotel pilihan akan segera tersedia. Silakan coba kembali nanti.') }}</p>
            @endforelse
        </div>
    </section>

    <section class="feature-standard-section">
        <div class="shell promise feature-standard-layout">
            <div class="promise-image">
                <img alt="{{ __('Kamar hotel terpilih dengan pencahayaan hangat') }}" src="{{ asset('assets/stayvia-standard.jpg') }}">
            </div>
            <div class="promise-copy">
                <span class="kicker">{{ __('Standar Hotel HAR') }}</span>
                <h2 class="title">{{ __('Lebih dari sekadar tempat menginap.') }}</h2>
                <p>{{ __('Setiap hotel kami pilih dengan teliti untuk memastikan kualitas tidur, keramahan, desain, dan detail kecil yang membuat perjalanan terasa istimewa.') }}</p>
                <div class="promise-list">
                    <div>
                        <span class="promise-icon"><x-icon name="check" /></span>
                        <span><strong>{{ __('Harga transparan') }}</strong>{{ __('Tanpa biaya tersembunyi di langkah terakhir') }}</span>
                    </div>
                    <div>
                        <span class="promise-icon"><x-icon name="heart" /></span>
                        <span><strong>{{ __('Terkurasi dengan hati') }}</strong>{{ __('Hanya hotel yang memenuhi standar kenyamanan kami') }}</span>
                    </div>
                    <div>
                        <span class="promise-icon"><x-icon name="shield" /></span>
                        <span><strong>{{ __('Selalu didampingi') }}</strong>{{ __('Bantuan tersedia sebelum, selama, dan setelah menginap') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space steps-section" id="cara">
        <div class="shell">
            <div class="steps-heading">
                <span class="kicker">{{ __('Mudah dari awal hingga tiba') }}</span>
                <h2 class="title">{{ __('Alur reservasi yang jelas') }}</h2>
                <p>{{ __('Dari memilih hotel hingga e-voucher, status pesanan bisa dilihat kembali di akun Anda.') }}</p>
            </div>
            <div class="steps">
                @foreach ($steps as $index => $step)
                    <article class="step-card">
                        <div class="step-card-top">
                            <span class="step-number">{{ $step['number'] }}</span>
                            <span class="step-icon" aria-hidden="true">
                                @switch($index)
                                    @case(0)
                                        <x-icon name="user" size="19" />
                                        @break
                                    @case(1)
                                        <x-icon name="search" size="19" />
                                        @break
                                    @case(2)
                                        <x-icon name="bed" size="19" />
                                        @break
                                    @case(3)
                                        <x-icon name="bank" size="19" />
                                        @break
                                    @case(4)
                                        <x-icon name="ticket" size="19" />
                                        @break
                                    @default
                                        <x-icon name="receipt" size="19" />
                                @endswitch
                            </span>
                        </div>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($hotels)
        <section class="results section-space shell" id="hasil">
            <div class="results-title">
                <div>
                    <span class="kicker">{{ __(':count properties found for :guests guests', ['count' => $hotels->total(), 'guests' => $filters['guests']]) }}</span>
                    <h2 class="title">{{ __('Menginap yang terasa tepat') }}</h2>
                </div>
            </div>
            @forelse ($hotels as $hotel)
                @php
                    $room = $hotel->rooms->sortBy('price_per_night')->first();
                    $guestRange = match ($hotel->slug) {
                        'taman-sari-garden-hotel' => __('1–2 tamu'),
                        'svara-signature-retreat' => __('3–4 tamu'),
                        'aksara-ubud-resort-spa' => __('5–8 tamu'),
                        default => null,
                    };
                @endphp
                <article class="hotel-card card">
                    <img alt="{{ $hotel->name }}" src="{{ asset('assets/'.($room->image ?: $hotel->image)) }}">
                    <div class="hotel-copy">
                        <div class="hotel-top">
                            <div>
                                <span class="location"><x-icon name="location" size="14" /> {{ $hotel->city }}, {{ $hotel->province }}</span>
                                <h3>{{ $hotel->name }}</h3>
                                <p>{{ $room->name }} · {{ __($room->description) }}</p>
                            </div>
                            <div
                                class="hotel-rating"
                                x-data="{ open: false }"
                                @mouseenter="open = true"
                                @mouseleave="open = false"
                                @keydown.escape.window="open = false"
                                @click.outside="open = false"
                            >
                                <button
                                    class="hotel-rating-chip"
                                    type="button"
                                    @click="open = true"
                                    :aria-expanded="open.toString()"
                                    aria-label="Ringkasan ulasan {{ $hotel->name }}"
                                >
                                    <span aria-hidden="true">★</span>
                                    @if ($hotel->reviews_count > 0)
                                        {{ number_format((float) $hotel->reviews_avg_rating, 1) }}
                                        · {{ __(':count reviews', ['count' => $hotel->reviews_count]) }}
                                    @else
                                        {{ __('Belum ada ulasan') }}
                                    @endif
                                </button>
                                <section class="hotel-rating-popover" x-cloak x-show="open" x-transition>
                                    @if ($hotel->reviews_count > 0)
                                        <div class="hotel-rating-summary">
                                            <strong>{{ number_format((float) $hotel->reviews_avg_rating, 1) }}</strong>
                                            <span>/ 5</span>
                                            <div class="hotel-rating-stars" aria-label="Bintang rata-rata">
                                                @for ($star = 0; $star < 5; $star++)
                                                    <span aria-hidden="true">★</span>
                                                @endfor
                                            </div>
                                            <small>{{ __(':count verified guest reviews', ['count' => $hotel->reviews_count]) }}</small>
                                        </div>
                                        <div class="hotel-rating-distribution">
                                            @for ($star = 5; $star >= 1; $star--)
                                                @php
                                                    $starCount = (int) $hotel->{'rating_'.$star.'_count'};
                                                    $starPercent = $hotel->reviews_count > 0
                                                        ? ($starCount / $hotel->reviews_count) * 100
                                                        : 0;
                                                @endphp
                                                <div class="hotel-rating-row">
                                                    <span>{{ $star }} ★</span>
                                                    <span class="hotel-rating-track"><span style="width: {{ $starPercent }}%"></span></span>
                                                    <small>{{ $starCount }}</small>
                                                </div>
                                            @endfor
                                        </div>
                                    @else
                                        <p class="hotel-rating-empty">{{ __('Jadilah tamu pertama yang berbagi pengalaman menginap.') }}</p>
                                    @endif
                                    <a href="{{ route('hotels.reviews.show', $hotel->slug) }}">Lihat semua ulasan <span aria-hidden="true">→</span></a>
                                </section>
                            </div>
                        </div>
                        <div class="amenities">
                            @if ($guestRange)
                                <span>{{ $guestRange }}</span>
                            @endif
                            <span>{{ $filters['checkin'] }} {{ $filters['checkin_time'] }} — {{ $filters['checkout'] }} {{ $filters['checkout_time'] }}</span>
                        </div>
                        <div class="hotel-bottom">
                            <span><x-icon name="check" size="15" /> {{ __('Tersedia untuk tanggal pilihan') }}</span>
                            <div><small>{{ __('per malam') }}</small><strong>Rp{{ number_format((float) $room->price_per_night, 0, ',', '.') }}</strong></div>
                            <a class="button primary" href="{{ route('rooms.show', [
                                'room' => $room,
                                'checkin' => $filters['checkin'],
                                'checkin_time' => $filters['checkin_time'],
                                'checkout' => $filters['checkout'],
                                'checkout_time' => $filters['checkout_time'],
                                'guests' => $filters['guests'],
                                'rooms' => $filters['rooms'],
                            ]) }}">{{ __('Lihat kamar') }}</a>
                        </div>
                    </div>
                </article>
            @empty
                <p class="empty-state">{{ __('Hotel pilihan untuk kapasitas ini akan segera tersedia. Coba ubah jumlah tamu, kamar, destinasi, atau tanggal pencarian.') }}</p>
            @endforelse
            {{ $hotels->links() }}
        </section>
    @endif

    <footer class="back-to-top-footer" id="masuk">
        <div class="shell footer-inner back-top-footer">
            <a class="back-top-brand" href="{{ route('home') }}" aria-label="{{ __('Hotel HAR Official, kembali ke beranda') }}">
                <span class="back-top-brand-mark">
                    <img src="{{ asset('assets/logo.png') }}" alt="">
                </span>
                <span class="back-top-brand-copy">
                    <strong>Hotel HAR Official</strong>
                    <small>PT HAR Hotel Indonesia</small>
                </span>
            </a>
            <button class="back-top-button" type="button" data-back-to-top>
                <span>{{ __('Kembali ke atas') }}</span>
                <span class="back-top-button-icon" aria-hidden="true">
                    <x-icon name="arrow-up" size="17" />
                </span>
            </button>
        </div>
    </footer>
</main>
@include('partials.help')
</body>
</html>
