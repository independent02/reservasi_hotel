@extends('layouts.auth')

@section('title', __('Data tamu'))
@section('eyebrow', __('Langkah 2 · Data tamu'))
@section('heading', __('Data tamu'))
@section('intro', $room->hotel->name.' · '.$room->name)

@section('mainClass', 'room-selection-main')
@section('cardClass', 'guest-details-card')
@section('bodyClass', 'room-selection-page')

@section('form')
    <div
        class="guest-checkout"
        data-selection-countdown
        data-selection-expiry="{{ $selectionExpiry ?? '' }}"
    >
        @include('reservations.partials.steps', ['activeStep' => 2])

        @if (! $selectionExpiry)
            <div class="auth-error" role="alert">{{ __('Waktu penguncian kamar habis. Silakan pilih ulang kamar.') }}</div>
            <a class="button primary full-button" href="{{ route('reservations.create', array_merge(['room' => $room], collect($filters)->except('room_numbers')->all())) }}">{{ __('Pilih kamar kembali') }}</a>
        @else
            <p class="auth-error" role="alert" data-guest-expired hidden>{{ __('Waktu penguncian kamar habis. Pilih kembali kamar untuk melanjutkan.') }}</p>
            <div class="guest-lock-notice flow-status-banner" aria-live="polite">
                <span aria-hidden="true">⌑</span>
                <span>{{ __('Kamar :rooms dikunci untuk Anda. Selesaikan sebelum waktu habis.', ['rooms' => $displayRoomNumbers->join(', ')]) }}</span>
                <strong data-countdown-time></strong>
                <i data-countdown-progress></i>
            </div>

            <form class="guest-booking-form" action="{{ route('reservations.store', $room) }}" method="post">
                @csrf
                <input type="hidden" name="checkin" value="{{ $filters['checkin'] }}">
                <input type="hidden" name="checkin_time" value="{{ $filters['checkin_time'] }}">
                <input type="hidden" name="checkout" value="{{ $filters['checkout'] }}">
                <input type="hidden" name="checkout_time" value="{{ $filters['checkout_time'] }}">
                <input type="hidden" name="guests" value="{{ $filters['guests'] }}">
                <input type="hidden" name="rooms" value="{{ $filters['rooms'] }}">
                @foreach ($filters['room_numbers'] as $number)
                    <input type="hidden" name="room_numbers[]" value="{{ $number }}">
                @endforeach

                <div class="guest-booking-layout">
                    <section class="guest-form-panel" aria-labelledby="guest-form-heading">
                        <h2 id="guest-form-heading">{{ __('Data Tamu Utama') }}</h2>
                        <div class="guest-fields">
                            <label class="guest-field guest-field-wide" for="guest_name">
                                <span>{{ __('Nama lengkap (sesuai identitas)') }}</span>
                                <input id="guest_name" name="guest_name" value="{{ old('guest_name', auth()->user()->name) }}" maxlength="100" autocomplete="name" required>
                            </label>

                            <label class="guest-field" for="identity_type">
                                <span>{{ __('Tipe identitas') }}</span>
                                <select id="identity_type" name="identity_type" required>
                                    <option value="ktp" @selected(old('identity_type', 'ktp') === 'ktp')>KTP</option>
                                    <option value="passport" @selected(old('identity_type') === 'passport')>{{ __('Paspor') }}</option>
                                </select>
                            </label>

                            <label class="guest-field" for="identity_number">
                                <span>{{ __('Nomor identitas') }}</span>
                                <input id="identity_number" name="identity_number" value="{{ old('identity_number') }}" maxlength="50" autocomplete="off" required>
                            </label>

                            <label class="guest-field guest-field-wide" for="guest_email">
                                <span>{{ __('Alamat email') }}</span>
                                <input id="guest_email" name="guest_email" type="email" value="{{ old('guest_email', auth()->user()->email) }}" maxlength="255" autocomplete="email" required>
                                <small>{{ __('Alamat ini digunakan sebagai kontak pada detail reservasi.') }}</small>
                            </label>

                            <label class="guest-field guest-field-wide" for="guest_phone">
                                <span>{{ __('Nomor telepon') }} <em>({{ __('opsional') }})</em></span>
                                <input id="guest_phone" name="guest_phone" type="tel" value="{{ old('guest_phone') }}" maxlength="32" autocomplete="tel">
                            </label>

                            <label class="guest-field guest-field-wide" for="special_request">
                                <span>{{ __('Permintaan khusus') }} <em>({{ __('opsional') }})</em></span>
                                <textarea id="special_request" name="special_request" rows="3" maxlength="1000" placeholder="{{ __('Contoh: kamar lantai tinggi, kamar non-smoking') }}">{{ old('special_request') }}</textarea>
                            </label>
                        </div>
                    </section>

                    <aside class="guest-order-column" aria-label="{{ __('Ringkasan pesanan') }}">
                        <section class="guest-order-summary">
                            <h2>{{ __('Ringkasan pesanan') }}</h2>
                            <strong>{{ $room->hotel->name }}</strong>
                            <span>{{ $room->name }} · {{ __('Kamar :rooms', ['rooms' => $displayRoomNumbers->join(', ')]) }}</span>
                            <span>{{ \Illuminate\Support\Carbon::parse($filters['checkin'])->format('d M Y') }} {{ $filters['checkin_time'] }} – {{ \Illuminate\Support\Carbon::parse($filters['checkout'])->format('d M Y') }} {{ $filters['checkout_time'] }} ({{ __(':count malam', ['count' => $nights]) }})</span>
                            <span>{{ __(':count tamu', ['count' => $filters['guests']]) }} · {{ __(':count kamar', ['count' => $filters['rooms']]) }}</span>
                            <dl>
                                <div>
                                    <dt>{{ __('Harga kamar') }}</dt>
                                    <dd>Rp{{ number_format((float) $room->price_per_night * $nights * count($filters['room_numbers']), 0, ',', '.') }}</dd>
                                </div>
                            </dl>
                            <div class="guest-order-total">
                                <span>{{ __('Total') }}</span>
                                <strong>Rp{{ number_format((float) $room->price_per_night * $nights * count($filters['room_numbers']), 0, ',', '.') }}</strong>
                            </div>
                        </section>

                        <button
                            class="button primary full-button guest-payment-button"
                            type="submit"
                            data-guest-submit
                        >{{ __('Lanjut ke Pembayaran') }}</button>
                        <a
                            class="button soft full-button guest-reselect"
                            href="{{ route('reservations.create', array_merge(['room' => $room], collect($filters)->except('room_numbers')->all())) }}"
                            data-guest-expired
                            hidden
                        >{{ __('Pilih kamar kembali') }}</a>
                        <a class="guest-back-link" href="{{ route('reservations.create', array_merge(['room' => $room], collect($filters)->except('room_numbers')->all())) }}">{{ __('Kembali ke pilih kamar') }}</a>
                    </aside>
                </div>
            </form>
        @endif
    </div>
@endsection

@section('switch')
    <a href="{{ route('reservations.create', array_merge(['room' => $room], collect($filters)->except('room_numbers')->all())) }}">{{ __('Kembali ubah pilihan kamar') }}</a>
@endsection
