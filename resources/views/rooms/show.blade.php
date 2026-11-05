@extends('layouts.auth')

@section('title', $room->name)
@section('brandName', 'Hotel HAR Official')
@section('eyebrow', '')
@section('heading', '')
@section('intro', '')
@section('bodyClass', 'room-detail-page')
@section('mainClass', 'room-detail-main')
@section('cardClass', 'room-detail-card')

@section('form')
    @php
        $hotel = $room->hotel;
        $displayRating = $hotel->average_rating ?? ((float) $hotel->rating > 0 ? (float) $hotel->rating : null);
        $hasRating = $displayRating !== null;
        $checkinAt = \Illuminate\Support\Carbon::parse($filters['checkin']);
        $checkoutAt = \Illuminate\Support\Carbon::parse($filters['checkout']);
        $hotelAddress = $hotel->address ?: __('44XX+X59, Mintaragen, Tegal Timur, Tegal City, Central Java 52121');
        $mapsUrl = $hotel->maps_url
            ?: ($hotel->latitude !== null && $hotel->longitude !== null
                ? 'https://www.google.com/maps?q='.$hotel->latitude.','.$hotel->longitude
                : 'https://maps.app.goo.gl/cWfH4pCL6gQvnanM6');
        $backUrl = route('hotels.search', array_merge(
            $filters,
            request()->only('destination'),
        ));
        $facilityIcons = [
            'tv' => 'M3 4h18v13H3z M8 21h8 M12 17v4 M8 8l3 3 5-5',
            'ac' => 'M12 3v18 M4 7l16 10 M4 17 20 7 M7 4l5 3 5-3 M7 20l5-3 5 3',
            'fridge' => 'M6 3h12v18H6z M6 11h12 M9 7v2 M9 14v2',
            'desk' => 'M3 7h18v5H3z M5 12v9 M19 12v9 M8 7V4h8v3',
            'bed' => 'M3 18V6 M3 15h18v6 M3 11h18v4 M7 11V8h5v3 M14 11V8h5v3',
            'bath' => 'M4 12h16v4a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z M6 12V6a3 3 0 0 1 6 0v1 M8 20v2 M16 20v2',
            'kettle' => 'M7 8h10l1 12H6L7 8z M9 8V5a3 3 0 0 1 6 0v3 M17 11h2a2 2 0 0 1 0 4h-1 M10 12h4',
            'wifi' => 'M5 9a11 11 0 0 1 14 0 M8 12a6.5 6.5 0 0 1 8 0 M11 15a2 2 0 0 1 2 0 M12 19h.01',
            'park' => 'M5 21V4h8a5 5 0 0 1 0 10H5 M5 10h8',
            'cafe' => 'M4 8h14v8a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z M18 10h1a3 3 0 0 1 0 6h-1 M7 3v2 M11 3v2 M15 3v2',
            'cloche' => 'M3 17h18 M5 17a7 7 0 0 1 14 0 M12 10V7 M10 7h4 M4 20h16',
        ];
    @endphp
    <link rel="stylesheet" href="{{ asset('css/room-detail.css') }}">

    <div class="rd-page" data-back-url="{{ $backUrl }}">
        <article class="rd-hero rd-glass" data-room-tilt>
            <figure class="rd-photo">
                <img
                    src="{{ asset('assets/'.($room->image ?: $hotel->image)) }}"
                    alt="{{ __('Photo of :room', ['room' => $room->name]) }}"
                    fetchpriority="high"
                >
                <figcaption>{{ $hotel->city }}, {{ $hotel->province }}</figcaption>
            </figure>
            <div class="rd-room-info">
                <span class="rd-eyebrow">{{ $hotel->name }}</span>
                <h1>{{ $room->name }}</h1>
                <p class="rd-description">{{ __($room->description) }}</p>
                <dl class="rd-room-facts">
                    <div><dt>{{ __('Lokasi') }}</dt><dd>{{ $hotel->city }}, {{ $hotel->province }}</dd></div>
                    <div><dt>{{ __('Kapasitas kamar') }}</dt><dd>{{ __(':count tamu', ['count' => $filters['guests']]) }}</dd></div>
                    <div><dt>{{ __('Harga per malam') }}</dt><dd>Rp{{ number_format((float) $room->price_per_night, 0, ',', '.') }}</dd></div>
                    <div>
                        <dt>{{ __('Tanggal pilihan') }}</dt>
                        <dd>{{ $checkinAt->translatedFormat('d M Y') }} · {{ substr($filters['checkin_time'], 0, 5) }} — {{ $checkoutAt->translatedFormat('d M Y') }} · {{ substr($filters['checkout_time'], 0, 5) }}</dd>
                    </div>
                </dl>
                <a
                    class="rd-primary-button"
                    href="{{ route('reservations.create', array_merge(
                        ['room' => $room],
                        request()->only('destination'),
                        $filters,
                    )) }}"
                >{{ __('Pilih kamar dan lanjutkan') }} <span aria-hidden="true">→</span></a>
            </div>
        </article>

        <div class="rd-summary-row">
            @if ($hasRating)
                <section class="rd-rating-card" aria-label="{{ __('Ulasan & rating hotel') }}">
                    <h2>{{ __('Ulasan & rating hotel') }}</h2>
                    <div class="rd-rating-line">
                        <strong>{{ number_format($displayRating, 1, ',', '.') }}</strong>
                        <div class="rd-rating-meta">
                            <span class="rd-stars" aria-label="Rating {{ number_format($displayRating, 1, ',', '.') }} dari 5">★★★★★</span>
                            <p>
                                {{ __('dari 5') }}
                                @if ($hotel->reviews_count > 0)
                                    · {{ __(':count ulasan tamu', ['count' => number_format($hotel->reviews_count)]) }}
                                @else
                                    · {{ __('rating hotel') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    @if ($hotel->reviews_count > 0)
                        <a href="{{ route('hotels.reviews.show', $hotel->slug) }}">{{ __('Lihat ulasan hotel') }} <span aria-hidden="true">→</span></a>
                    @endif
                </section>
            @endif

            <section class="rd-address-card rd-glass">
                <div>
                    <span class="rd-section-kicker">{{ __('ALAMAT HOTEL') }}</span>
                    <h2>{{ $hotel->name }}</h2>
                    <p>{{ $hotelAddress }}</p>
                </div>
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer">{{ __('Buka di Google Maps') }} <span aria-hidden="true">↗</span></a>
            </section>
        </div>

        <section class="rd-section">
            <div class="rd-section-heading">
                <span class="rd-section-kicker">{{ __('DI DALAM KAMAR') }}</span>
                <h2>{{ __('Fasilitas kamar') }}</h2>
                <p>{{ __('Fasilitas yang tersedia di kamar ini.') }}</p>
            </div>
            <div class="rd-facility-grid">
                @forelse ($room->facilities as $facility)
                    <article class="rd-facility-card rd-glass">
                        <span class="rd-facility-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="{{ $facilityIcons[$facility->icon] ?? $facilityIcons['bed'] }}"></path></svg>
                        </span>
                        <span class="rd-facility-number" aria-hidden="true"></span>
                        <h3>{{ __($facility->name) }}</h3>
                        <p>{{ __($facility->description) }}</p>
                        @if (filled($facility->badge))
                            <span class="rd-facility-badge">{{ __($facility->badge) }}</span>
                        @endif
                    </article>
                @empty
                    <p class="rd-empty-state">{{ __('Fasilitas kamar belum tersedia.') }}</p>
                @endforelse
            </div>
        </section>

        <section class="rd-section">
            <div class="rd-section-heading">
                <span class="rd-section-kicker">{{ __('AREA HOTEL') }}</span>
                <h2>{{ __('Fasilitas publik') }}</h2>
                <p>{{ __('Fasilitas yang tersedia di area hotel.') }}</p>
            </div>
            <div class="rd-facility-grid rd-public-grid">
                @forelse ($hotel->facilities as $facility)
                    <article class="rd-facility-card rd-glass">
                        <span class="rd-facility-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="{{ $facilityIcons[$facility->icon] ?? $facilityIcons['bed'] }}"></path></svg>
                        </span>
                        <span class="rd-facility-number" aria-hidden="true"></span>
                        <h3>{{ __($facility->name) }}</h3>
                        <p>{{ __($facility->description) }}</p>
                        @if (filled($facility->badge))
                            <span class="rd-facility-badge{{ $facility->icon === 'cafe' ? ' rd-facility-badge-cafe' : '' }}">{{ __($facility->badge) }}</span>
                        @endif
                    </article>
                @empty
                    <p class="rd-empty-state">{{ __('Fasilitas publik belum tersedia.') }}</p>
                @endforelse
            </div>
        </section>

        <section class="rd-section rd-cancellation-section">
            <div class="rd-section-heading">
                <span class="rd-section-kicker">{{ __('INFO RESERVASI') }}</span>
                <h2>{{ __('Kebijakan pembatalan') }}</h2>
                <p>{{ __('Persentase refund dihitung dari waktu pembatalan hingga waktu check-in.') }}</p>
            </div>
            <div class="rd-policy-grid">
                @forelse ($policies as $policy)
                    <article class="rd-policy-card rd-glass">
                        <span class="rd-policy-percent">{{ $policy->refund_percent }}% <small>{{ __('refund') }}</small></span>
                        <h3>{{ __($policy->title) }}</h3>
                        <p>{{ __($policy->description) }}</p>
                    </article>
                @empty
                    <p class="rd-empty-state">{{ __('Kebijakan pembatalan belum tersedia. Hubungi hotel untuk informasi.') }}</p>
                @endforelse
            </div>

            <div class="rd-cancel-bar rd-glass">
                <div>
                    <h3>{{ __('Perlu membatalkan pembayaran?') }}</h3>
                    @if ($activeReservation)
                        <p>{{ __('Pembatalan untuk reservasi aktif Anda di :hotel.', ['hotel' => $hotel->name]) }}</p>
                    @else
                        <p>{{ __('Masuk ke akun untuk melihat reservasi yang dapat dibatalkan.') }}</p>
                    @endif
                </div>
                @if ($activeReservation)
                    <button class="rd-cancel-button" type="button" data-open-cancel-dialog>{{ __('Batalkan pembayaran') }}</button>
                @elseif (auth()->check())
                    <a class="rd-cancel-button" href="{{ route('reservations.index') }}">{{ __('Batalkan pembayaran') }}</a>
                @else
                    <a class="rd-cancel-button" href="{{ route('login') }}">{{ __('Masuk') }}</a>
                @endif
            </div>
        </section>
    </div>

    @if ($activeReservation)
        <dialog class="rd-cancel-dialog" data-cancel-dialog aria-labelledby="rd-dialog-title">
            <div class="rd-dialog-content">
                <button class="rd-dialog-close" type="button" aria-label="{{ __('Tutup dialog') }}" data-close-cancel-dialog>×</button>
                <span class="rd-section-kicker">{{ __('KONFIRMASI PEMBATALAN') }}</span>
                <h2 id="rd-dialog-title">{{ __('Batalkan pembayaran') }}</h2>
                <p>{{ $room->name }} · {{ $activeReservation->check_in->format('Y-m-d') }} {{ __('sampai') }} {{ $activeReservation->check_out->format('Y-m-d') }}. {{ __('Pilih alasan pembatalan.') }}</p>
                <form method="POST" action="{{ route('bookings.cancel', $activeReservation) }}" data-cancel-form>
                    @csrf
                    @method('DELETE')
                    <fieldset class="rd-cancel-reasons">
                        <legend>{{ __('Pilih alasan pembatalan.') }}</legend>
                        @foreach ([
                            __('Rencana perjalanan berubah'),
                            __('Salah memilih kamar atau tanggal'),
                            __('Ingin mengganti metode pembayaran'),
                            __('Alasan lain'),
                        ] as $reason)
                            <label class="rd-cancel-reason">
                                <input type="radio" name="reason" value="{{ $reason }}" required>
                                <span>{{ $reason }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                    <label class="rd-other-cancel-reason" data-room-other-reason hidden>
                        <span>{{ __('Ceritakan alasan pembatalan') }}</span>
                        <textarea
                            name="reason_detail"
                            rows="3"
                            maxlength="1000"
                            placeholder="{{ __('Tuliskan alasan pemesanan dibatalkan...') }}"
                            data-room-other-reason-input
                            disabled
                        ></textarea>
                    </label>
                    <label class="rd-acknowledgement">
                        <input type="checkbox" name="ack" value="yes" data-cancel-ack required>
                        <span>{{ __('Saya memahami besar refund mengikuti kebijakan pembatalan di atas.') }}</span>
                    </label>
                    <div class="rd-dialog-actions">
                        <button class="rd-secondary-button" type="button" data-close-cancel-dialog>{{ __('Kembali') }}</button>
                        <button class="rd-danger-button" type="submit" data-cancel-submit disabled>{{ __('Ya, batalkan') }}</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif

    <script src="{{ asset('js/room-detail.js') }}" defer></script>
@endsection
