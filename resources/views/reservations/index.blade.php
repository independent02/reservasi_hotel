@extends('layouts.auth')

@section('title', __('Pesanan saya'))
@section('eyebrow', __('Akun Hotel HAR Official'))
@section('heading', __('Pesanan saya'))
@section('intro', __('Kelola reservasi dan perjalanan Anda.'))
@section('mainClass', 'my-bookings-main')
@section('cardClass', 'my-bookings-card')
@section('bodyClass', 'my-bookings-page')

@section('form')
    @if (auth()->user()->role === 'admin')
        <p><a class="button soft" href="{{ route('admin.dashboard') }}">{{ __('Buka dashboard admin') }}</a></p>
    @endif
    @if (session('status'))
        <div class="auth-success booking-flash" role="status">{{ session('status') }}</div>
    @endif
    @if (session('warning'))
        <div class="auth-error booking-flash" role="alert">{{ session('warning') }}</div>
    @endif
    <nav class="booking-tabs" aria-label="{{ __('Filter pesanan') }}">
        @foreach ($tabs as $tabKey => $tab)
            <a
                class="booking-tab{{ $activeTab === $tabKey ? ' is-active' : '' }}"
                href="{{ route('reservations.index', ['tab' => $tabKey]) }}"
                @if ($activeTab === $tabKey) aria-current="page" @endif
            >
                {{ __($tab['label']) }}
                @if ($tabKey === 'active' || $tab['count'] > 0)
                    <span>{{ $tab['count'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <section class="booking-list" aria-label="{{ __('Daftar pesanan') }}">
        @forelse ($reservations as $reservation)
            @php
                $isPending = $reservation->status === 'pending' && $reservation->payment_status === 'pending';
                $isWaitingConfirmation = $reservation->payment_status === 'waiting_confirmation';
                $canReview = $reservation->status === 'confirmed'
                    && $reservation->hasCheckedOut()
                    && $reservation->review === null;
                $isExpiredUnpaid = $isPending
                    && $reservation->expires_at?->isPast()
                    && blank($reservation->payment_proof);
                $canDelete = in_array($reservation->status, ['cancelled', 'expired'], true) || $isExpiredUnpaid;
                $canCancel = in_array($reservation->status, ['pending', 'confirmed', 'checked_in'], true)
                    && $reservation->check_out->greaterThanOrEqualTo(today())
                    && ! $isExpiredUnpaid;
                $statusLabel = $isWaitingConfirmation
                    ? __('Menunggu Konfirmasi Admin')
                    : ($isExpiredUnpaid ? __('Kedaluwarsa') : match ($reservation->status) {
                    'confirmed' => __('Terkonfirmasi'),
                    'pending' => __('Menunggu Pembayaran'),
                    'cancelled' => __('Dibatalkan'),
                    'expired' => __('Kedaluwarsa'),
                    'refunded' => __('Dikembalikan'),
                    default => ucfirst($reservation->status),
                });
                if ($reservation->status === 'confirmed' && $reservation->hasCheckedOut()) {
                    $statusLabel = __('Selesai');
                }
                $statusStyle = $isExpiredUnpaid ? 'is-cancelled' : match ($reservation->status) {
                    'confirmed' => 'is-confirmed',
                    'pending' => 'is-pending',
                    default => 'is-cancelled',
                };
                $stayNights = max(1, $reservation->check_in->diffInDays($reservation->check_out));
                $image = $reservation->room->image ?: $reservation->hotel->image;
                $roomNumbers = collect($reservation->room_numbers ?? [])->join(', ') ?: '—';
            @endphp

            <article class="booking-card flow-card" data-booking-id="{{ $reservation->id }}">
                <a
                    class="booking-card-image"
                    href="{{ route('reservations.show', $reservation) }}"
                    aria-label="{{ __('Lihat pesanan :room', ['room' => $reservation->room->name]) }}"
                >
                    @if ($image)
                        <img
                            src="{{ asset('assets/'.$image) }}"
                            alt="{{ $reservation->room->name }} di {{ $reservation->hotel->name }}"
                            loading="lazy"
                        >
                    @else
                        <span class="booking-image-placeholder" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M3 20V6m0 10h18v4M3 11h18v5M7 11V8a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3m-7 0V9h5v2m5 0V8a2 2 0 0 1 2-2h1" />
                            </svg>
                        </span>
                    @endif
                </a>

                <div class="booking-card-content">
                    <div class="booking-card-heading">
                        <div class="booking-card-copy">
                            <span class="booking-reference">{{ __('Pesanan #:id', ['id' => $reservation->id]) }}</span>
                            <h2>{{ $reservation->room->name }} — {{ __('Kamar :rooms', ['rooms' => $roomNumbers]) }}</h2>
                            <span class="booking-hotel-name">{{ $reservation->hotel->name }}</span>
                        </div>
                        <span class="booking-status {{ $statusStyle }}">{{ $statusLabel }}</span>
                    </div>

                    <div class="booking-card-meta">
                        <span>{{ $reservation->check_in->format('d M Y') }} {{ substr($reservation->check_in_time ?? '14:00', 0, 5) }} – {{ $reservation->check_out->format('d M Y') }} {{ substr($reservation->check_out_time ?? '12:00', 0, 5) }} · {{ __(':count malam', ['count' => $stayNights]) }}</span>
                        <strong>Rp{{ number_format((float) $reservation->total_price, 0, ',', '.') }}</strong>
                    </div>

                    @if ($isPending && $reservation->expires_at)
                        <p class="booking-deadline{{ $reservation->expires_at->isPast() ? ' is-expired' : '' }}">
                            @if ($reservation->expires_at->isPast())
                                {{ __('Batas waktu pembayaran telah lewat. Periksa status pesanan Anda.') }}
                            @else
                                {{ __('Bayar sebelum :date WIB', ['date' => $reservation->expires_at->format('d M Y, H:i')]) }}
                            @endif
                        </p>
                    @endif

                    <div class="booking-card-actions">
                        @if ($canReview)
                            <button
                                class="button primary booking-action-review"
                                type="button"
                                @click="$dispatch('open-review', {{ $reservation->id }})"
                            >{{ __('Tulis ulasan') }}</button>
                        @elseif ($reservation->review)
                            <a class="button booking-action-detail" href="{{ route('hotels.reviews.show', $reservation->hotel->slug) }}">{{ __('Lihat ulasan') }}</a>
                        @endif
                        <a class="button booking-action-detail" href="{{ route('reservations.show', $reservation) }}">{{ __('Lihat detail') }}</a>
                        @if ($isWaitingConfirmation)
                            <a class="button booking-action-voucher" href="{{ route('reservations.show', $reservation) }}">{{ __('Cek pesanan') }}</a>
                        @elseif ($isPending)
                            <a class="button primary booking-action-pay" href="{{ route('reservations.show', $reservation) }}">
                                {{ $reservation->expires_at?->isPast() ? __('Cek pesanan') : __('Bayar Sekarang') }}
                            </a>
                        @elseif ($reservation->status === 'confirmed' && filled($reservation->voucher_code))
                            <a class="button booking-action-voucher" href="{{ route('reservations.voucher', $reservation) }}">
                                {{ __('Lihat E-Voucher') }}
                            </a>
                        @else
                            <a class="button booking-action-check" href="{{ route('reservations.show', $reservation) }}">{{ __('Cek pesanan') }}</a>
                        @endif
                        @if (($isWaitingConfirmation || $reservation->status === 'confirmed') && $canCancel)
                            <button
                                class="button booking-action-cancel"
                                type="button"
                                data-booking-cancel-trigger
                                data-cancel-url="{{ route('bookings.cancel', $reservation) }}"
                                data-booking-label="{{ __('Pesanan #:id · :room', ['id' => $reservation->id, 'room' => $reservation->room->name]) }}"
                            >{{ __('Batalkan pemesanan') }}</button>
                        @endif
                        @if ($canDelete)
                            <button
                                class="booking-delete-trigger"
                                type="button"
                                data-booking-delete-trigger
                                data-delete-url="{{ route('reservations.destroy', $reservation) }}"
                                data-booking-label="{{ __('Pesanan #:id · :room', ['id' => $reservation->id, 'room' => $reservation->room->name]) }}"
                                aria-label="{{ __('Hapus pesanan #:id', ['id' => $reservation->id]) }}"
                                title="{{ __('Hapus pesanan') }}"
                            >
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </article>
            @if ($canReview)
                @include('hotels.partials.review-modal', ['reservation' => $reservation])
            @endif
        @empty
            <div class="booking-empty-state">
                <span class="booking-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 48 48" focusable="false">
                        <path d="M17 16v-4a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v4" />
                        <rect x="9" y="16" width="30" height="23" rx="5" />
                        <path d="M9 25h30M21 25v3h6v-3" />
                    </svg>
                </span>
                <h2>{{ __('Belum ada pesanan') }}</h2>
                <p>{{ __('Pesanan di tab ini akan muncul di sini.') }}</p>
                <a class="button primary booking-empty-action" href="{{ route('home') }}">{{ __('Mulai Cari Kamar') }}</a>
            </div>
        @endforelse
    </section>

    @if ($reservations->hasPages())
        <div class="booking-pagination">{{ $reservations->links() }}</div>
    @endif

    <dialog class="booking-delete-dialog" data-booking-delete-dialog aria-labelledby="booking-delete-title">
        <form method="post" data-booking-delete-form>
            @csrf
            @method('DELETE')
            <span class="booking-delete-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                </svg>
            </span>
            <h2 id="booking-delete-title">{{ __('Hapus pesanan?') }}</h2>
            <p>{{ __('Pesanan') }} <strong data-booking-delete-label></strong> {{ __('akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.') }}</p>
            <div class="booking-delete-actions">
                <button class="button booking-delete-cancel" type="button" data-booking-delete-cancel>{{ __('Batal') }}</button>
                <button class="button booking-delete-confirm" type="submit">{{ __('Hapus pesanan') }}</button>
            </div>
        </form>
    </dialog>

    <dialog class="booking-delete-dialog booking-cancel-dialog" data-booking-cancel-dialog aria-labelledby="booking-cancel-title">
        <form method="post" data-booking-cancel-form>
            @csrf
            @method('DELETE')
            <span class="booking-delete-icon booking-cancel-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false">
                    <path d="M12 8v4m0 4h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3l-7.5-13.1a2 2 0 0 0-3.4 0Z" />
                </svg>
            </span>
            <h2 id="booking-cancel-title">{{ __('Batalkan pemesanan?') }}</h2>
            <p><strong data-booking-cancel-label></strong> {{ __('akan dibatalkan. Refund, jika ada, mengikuti kebijakan hotel dan ditinjau admin.') }}</p>
            <fieldset class="booking-cancel-reasons">
                <legend>{{ __('Pilih alasan pembatalan') }}</legend>
                @foreach ([
                    __('Rencana perjalanan berubah'),
                    __('Salah memilih kamar atau tanggal'),
                    __('Ingin mengganti metode pembayaran'),
                    __('Alasan lain'),
                ] as $reason)
                    <label class="booking-cancel-reason-option">
                        <input type="radio" name="reason" value="{{ $reason }}" required>
                        <span>{{ $reason }}</span>
                    </label>
                @endforeach
            </fieldset>
            <label class="booking-cancel-detail" data-booking-other-reason hidden>
                <span>{{ __('Ceritakan alasan pembatalan') }}</span>
                <textarea
                    name="reason_detail"
                    rows="3"
                    maxlength="1000"
                    placeholder="{{ __('Tuliskan alasan pemesanan dibatalkan...') }}"
                    data-booking-other-reason-input
                    disabled
                ></textarea>
            </label>
            <label class="booking-cancel-acknowledgement">
                <input type="checkbox" name="ack" value="yes" data-booking-cancel-ack required>
                <span>{{ __('Saya memahami pembatalan dan estimasi refund mengikuti kebijakan yang berlaku.') }}</span>
            </label>
            <div class="booking-delete-actions">
                <button class="button booking-delete-cancel" type="button" data-booking-cancel-close>{{ __('Kembali') }}</button>
                <button class="button booking-delete-confirm" type="submit" data-booking-cancel-submit disabled>{{ __('Ya, batalkan') }}</button>
            </div>
        </form>
    </dialog>
@endsection

@section('switch')
    <a href="{{ route('home') }}">{{ __('Kembali ke beranda') }}</a>
@endsection
