@extends('layouts.auth')

@section('title', $reservation->status === 'confirmed' ? __('Booking Berhasil') : __('Pembayaran reservasi'))
@section('eyebrow', $reservation->status === 'confirmed' ? __('Reservasi terkonfirmasi') : __('Pembayaran'))
@section('heading', $reservation->status === 'confirmed' ? __('Booking Berhasil!') : __('Pembayaran Transfer Bank'))
@section('intro', $reservation->hotel->name.' · '.$reservation->room->name)
@section('mainClass', 'room-selection-main')
@section('cardClass', 'payment-card'.($reservation->status === 'confirmed' ? ' payment-card-confirmed' : ' payment-card-pending'))
@section('bodyClass', 'room-selection-page'.($reservation->payment_status === 'waiting_confirmation' ? ' payment-confirmation-page' : ''))

@section('form')
    @include('reservations.partials.steps', [
        'activeStep' => $reservation->status === 'confirmed' || $reservation->payment_status === 'waiting_confirmation' ? 4 : 3,
        'room' => $reservation->room,
        'filters' => [
            'checkin' => $reservation->check_in->toDateString(),
            'checkout' => $reservation->check_out->toDateString(),
            'guests' => $reservation->guest_count,
            'rooms' => $reservation->room_count,
        ],
        'allowRoomEdit' => false,
    ])

    @if ($reservation->status === 'confirmed')
        <section class="booking-success" role="status">
            <span class="booking-success-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="m5 12 4.5 4.5L19 7" /></svg>
            </span>
            <h2>{{ __('Booking Berhasil!') }}</h2>
            <p>{{ __('Pembayaran telah diverifikasi. Simpan kode booking ini untuk kebutuhan check-in.') }}</p>

            <article class="booking-ticket">
                <header class="booking-ticket-header">
                    <div><strong>Hotel HAR Official</strong><span>{{ __('E-VOUCHER') }} · PT HAR Hotel Indonesia</span></div>
                    <div class="booking-ticket-code"><span>{{ __('KODE BOOKING') }}</span><strong>{{ $reservation->voucher_code }}</strong></div>
                </header>
                <dl class="booking-ticket-details">
                    <div><dt>{{ __('Nama tamu') }}</dt><dd>{{ $reservation->guest_name }}</dd></div>
                    <div><dt>{{ __('Kamar') }}</dt><dd>{{ $reservation->room->name }} · {{ implode(', ', $reservation->room_numbers ?? []) }}</dd></div>
                    <div><dt>{{ __('Check-in') }}</dt><dd>{{ $reservation->check_in->format('d M Y') }} · {{ substr($reservation->check_in_time ?? '14:00', 0, 5) }}</dd></div>
                    <div><dt>{{ __('Check-out') }}</dt><dd>{{ $reservation->check_out->format('d M Y') }} · {{ substr($reservation->check_out_time ?? '12:00', 0, 5) }}</dd></div>
                    <div><dt>{{ __('Tamu') }}</dt><dd>{{ __(':count orang', ['count' => $reservation->guest_count]) }} · {{ __(':count kamar', ['count' => $reservation->room_count]) }}</dd></div>
                    <div><dt>{{ __('Total dibayar') }}</dt><dd>Rp{{ number_format((float) $reservation->total_price, 0, ',', '.') }}</dd></div>
                </dl>
                <div class="booking-ticket-qr">
                    <img src="{{ $voucherQrCode }}" alt="{{ __('QR code berisi kode booking :code', ['code' => $reservation->voucher_code]) }}" width="110" height="110">
                    <p>{{ __('QR ini berisi kode booking di atas.') }}</p>
                </div>
                <footer class="booking-ticket-footer"><span>{{ __('LUNAS') }}</span><span>{{ $reservation->hotel->name }}</span></footer>
            </article>
            @include('partials.voucher-benefits', [
                'room' => $reservation->room,
                'voucherBenefits' => $voucherBenefits,
            ])
            <div class="booking-success-actions">
                <a class="button primary" href="{{ route('reservations.voucher', $reservation) }}">{{ __('Lihat E-Voucher') }}</a>
            </div>
        </section>
    @elseif (in_array($reservation->status, ['expired', 'cancelled'], true) || $reservation->payment_status === 'cancelled')
        <div class="auth-error" role="alert">{{ __('Batas waktu pembayaran telah berakhir atau reservasi dibatalkan. Silakan buat reservasi baru.') }}</div>
    @elseif ($reservation->payment_status === 'waiting_confirmation')
        <section class="payment-waiting flow-card">
            <div class="payment-waiting-banner" role="status">
                <span class="payment-waiting-icon" aria-hidden="true">✓</span>
                <div>
                    <h2>{{ __('Bukti transfer diterima') }}</h2>
                    <p>{{ __('Pesanan menunggu verifikasi admin. Status akan diperbarui setelah pembayaran selesai diperiksa.') }}</p>
                </div>
            </div>
            <dl class="payment-waiting-summary">
                <div><dt>{{ __('Status pembayaran') }}</dt><dd>{{ __('Menunggu Verifikasi Admin') }}</dd></div>
                <div><dt>{{ __('Bank/metode tujuan') }}</dt><dd>{{ $reservation->destination_bank }}</dd></div>
                <div><dt>{{ __('Total') }}</dt><dd>Rp{{ number_format((float) $reservation->total_price, 0, ',', '.') }}</dd></div>
                <div><dt>{{ __('Tanggal bukti dikirim') }}</dt><dd>{{ $reservation->payment_submitted_at?->format('d M Y, H:i') }} {{ __('WIB') }}</dd></div>
            </dl>
        </section>
    @else
        @php
            $enabledMethods = collect($paymentMethods)->filter(fn (array $method): bool => $method['enabled']);
            $selectedMethod = old('payment_method', $enabledMethods->keys()->first());
            if (! $enabledMethods->has($selectedMethod)) {
                $selectedMethod = $enabledMethods->keys()->first();
            }
        @endphp
        <form
            class="payment-upload-form"
            action="{{ route('reservations.payment.proof', $reservation) }}"
            method="post"
            enctype="multipart/form-data"
            data-payment-selector
        >
            @csrf
            <div class="payment-layout">
                <section class="payment-method-panel" aria-labelledby="payment-method-heading">
                    <h2 id="payment-method-heading">{{ __('Pilih Metode Pembayaran') }}</h2>
                    <p>{{ __('Pilih tujuan pembayaran yang tersedia, lalu transfer sesuai jumlah tagihan.') }}</p>

                    <h3>{{ __('TRANSFER BANK') }}</h3>
                    <div class="payment-method-list">
                        @foreach ($paymentMethods as $methodKey => $method)
                            @continue($method['category'] !== 'bank')
                            <label class="payment-method-option{{ $method['enabled'] && $selectedMethod === $methodKey ? ' is-selected' : '' }}{{ ! $method['enabled'] ? ' is-unavailable' : '' }}">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $methodKey }}"
                                    @checked($method['enabled'] && $selectedMethod === $methodKey)
                                    @disabled(! $method['enabled'])
                                    @required($method['enabled'])
                                    data-payment-method
                                    data-method-label="{{ $method['label'] }}"
                                    data-method-bank-name="{{ $method['label'] }}"
                                    data-method-recipient="{{ $method['recipient'] }}"
                                    data-method-account-name="{{ $method['account_name'] }}"
                                >
                                <span class="payment-method-radio" aria-hidden="true"></span>
                                <span class="payment-method-logo payment-logo-{{ str_replace('_', '-', $methodKey) }}">{{ $method['short_label'] }}</span>
                                <span>{{ $method['label'] }}</span>
                                @if ($method['enabled'])
                                    <span class="payment-method-selected">{{ __('Dipilih') }}</span>
                                @else
                                    <span class="payment-method-unavailable">{{ __('Belum tersedia') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <h3>{{ __('E-WALLET') }}</h3>
                    <div class="payment-method-list">
                        @foreach ($paymentMethods as $methodKey => $method)
                            @continue($method['category'] !== 'ewallet')
                            <label class="payment-method-option{{ $method['enabled'] && $selectedMethod === $methodKey ? ' is-selected' : '' }}{{ ! $method['enabled'] ? ' is-unavailable' : '' }}">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $methodKey }}"
                                    @checked($method['enabled'] && $selectedMethod === $methodKey)
                                    @disabled(! $method['enabled'])
                                    @required($method['enabled'])
                                    data-payment-method
                                    data-method-label="{{ $method['label'] }}"
                                    data-method-bank-name="{{ $method['label'] }}"
                                    data-method-recipient="{{ $method['recipient'] }}"
                                    data-method-account-name="{{ $method['account_name'] }}"
                                >
                                <span class="payment-method-radio" aria-hidden="true"></span>
                                <span class="payment-method-logo payment-logo-{{ str_replace('_', '-', $methodKey) }}">{{ $method['short_label'] }}</span>
                                <span>{{ $method['label'] }}</span>
                                @if ($method['enabled'])
                                    <span class="payment-method-selected">{{ __('Dipilih') }}</span>
                                @else
                                    <span class="payment-method-unavailable">{{ __('Belum tersedia') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <h3>{{ __('QRIS') }}</h3>
                    <div class="payment-method-list">
                        @foreach ($paymentMethods as $methodKey => $method)
                            @continue($method['category'] !== 'qris')
                            <label class="payment-method-option{{ $method['enabled'] && $selectedMethod === $methodKey ? ' is-selected' : '' }}{{ ! $method['enabled'] ? ' is-unavailable' : '' }}">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $methodKey }}"
                                    @checked($method['enabled'] && $selectedMethod === $methodKey)
                                    @disabled(! $method['enabled'])
                                    @required($method['enabled'])
                                    data-payment-method
                                    data-method-label="{{ $method['label'] }}"
                                    data-method-bank-name="{{ $method['label'] }}"
                                    data-method-recipient=""
                                    data-method-account-name="{{ $method['account_name'] }}"
                                    data-method-qr-image="{{ $method['enabled'] ? asset($method['qr_image']) : '' }}"
                                >
                                <span class="payment-method-radio" aria-hidden="true"></span>
                                <span class="payment-method-logo payment-logo-qris" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false">
                                        <path d="M3 3h7v7H3V3Zm2 2v3h3V5H5Zm9-2h7v7h-7V3Zm2 2v3h3V5h-3ZM3 14h7v7H3v-7Zm2 2v3h3v-3H5Zm8-3h3v3h-3v-3Zm4 0h4v3h-4v-3Zm-4 5h3v3h-3v-3Zm5-1h3v4h-3v-4ZM9 12h3v3H9v-3Zm0 5h2v4H9v-4Z" />
                                    </svg>
                                </span>
                                <span>{{ $method['label'] }}</span>
                                @if ($method['enabled'])
                                    <span class="payment-method-selected">{{ __('Dipilih') }}</span>
                                @else
                                    <span class="payment-method-unavailable">{{ __('Belum tersedia') }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    @if ($enabledMethods->isEmpty())
                        <div class="auth-error" role="status">
                            {{ __('Metode pembayaran belum tersedia. Admin perlu mengisi nomor/ID penerima dan nama pemilik untuk setiap metode di file .env.') }}
                        </div>
                    @endif

                </section>

                <aside class="payment-aside">
                    <section class="payment-instructions payment-instructions-panel">
                        <h2>{{ __('Instruksi Pembayaran') }}</h2>
                        <article class="payment-issued-details" data-selected-method-details @if (! $selectedMethod) hidden @endif>
                            <h3 data-selected-method-label>
                                {{ $selectedMethod ? $paymentMethods[$selectedMethod]['label'] : '' }}
                            </h3>
                            <div class="payment-qris-code" data-selected-method-qr-container @if (! $selectedMethod || empty($paymentMethods[$selectedMethod]['qr_image'])) hidden @endif>
                                <img
                                    data-selected-method-qr
                                    src="{{ $selectedMethod && ! empty($paymentMethods[$selectedMethod]['qr_image']) ? asset($paymentMethods[$selectedMethod]['qr_image']) : '' }}"
                                    alt="{{ __('Kode QRIS tujuan pembayaran Hotel HAR Official') }}"
                                    width="240"
                                    height="240"
                                >
                                <p>{{ __('Pindai QRIS ini melalui aplikasi pembayaran Anda.') }}</p>
                            </div>
                            <div class="payment-recipient-details" data-selected-method-recipient-container @if (! $selectedMethod || $selectedMethod === 'qris') hidden @endif>
                                <p class="payment-issued-caption">{{ __('Nomor/tujuan pembayaran') }}</p>
                                <div class="payment-copy-row payment-copy-row-inline">
                                    <code data-selected-method-recipient>{{ $selectedMethod && $selectedMethod !== 'qris' ? $paymentMethods[$selectedMethod]['recipient'] : '' }}</code>
                                    <button class="button soft" type="button" data-copy-selected-payment @if (! $selectedMethod || $selectedMethod === 'qris') disabled @endif>{{ __('Salin') }}</button>
                                </div>
                            </div>
                            <p class="payment-issued-caption" data-selected-method-account-container @if (! $selectedMethod) hidden @endif>
                                {{ __('Atas nama') }} <span data-selected-method-account-name>{{ $selectedMethod ? $paymentMethods[$selectedMethod]['account_name'] : '' }}</span>
                            </p>
                            <p class="payment-bank-note">{{ __('Transfer sesuai total tagihan. Pembayaran diverifikasi manual oleh admin.') }}</p>
                        </article>
                        @if ($reservation->expires_at)
                            <p
                                class="payment-issued-expiry"
                                data-payment-countdown
                                data-payment-expiry="{{ $reservation->expires_at->toIso8601String() }}"
                            >
                                {{ __('Bayar dan unggah bukti sebelum :date WIB.', ['date' => $reservation->expires_at->format('d M Y, H:i')]) }}
                                <strong data-payment-countdown-time></strong>
                            </p>
                        @endif
                    </section>

                    <section class="payment-instructions payment-proof-panel">
                        <h2>{{ __('Unggah Bukti Pembayaran') }}</h2>
                        @if ($reservation->expires_at && $reservation->expires_at->isFuture())
                            <p>{{ __('Unggah bukti yang jelas (JPG, PNG, atau WEBP, maksimal 5 MB). Admin akan memverifikasi pembayaran Anda.') }}</p>
                            <fieldset class="payment-sender-details" @disabled($enabledMethods->isEmpty())>
                                <legend>
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M3 7.5h18v12H3zM3 10h18M7 15h4m-6-10h14" />
                                    </svg>
                                    {{ __('Rekening pengirim') }}
                                </legend>
                                <p>{{ __('Isi nama dan bank atau aplikasi yang digunakan untuk mengirim pembayaran.') }}</p>
                                <label class="guest-field" for="account_name">
                                    <span>{{ __('Nama pemilik rekening pengirim') }}</span>
                                    <input
                                        id="account_name"
                                        name="account_name"
                                        type="text"
                                        value="{{ old('account_name', $reservation->account_name) }}"
                                        maxlength="100"
                                        autocomplete="name"
                                        required
                                        @disabled($enabledMethods->isEmpty())
                                    >
                                </label>
                                <label class="guest-field" for="bank_name">
                                    <span>{{ __('Bank/aplikasi pengirim') }}</span>
                                    <input
                                        id="bank_name"
                                        name="bank_name"
                                        type="text"
                                        value="{{ old('bank_name', $reservation->bank_name ?: ($selectedMethod && $paymentMethods[$selectedMethod]['category'] !== 'qris' ? $paymentMethods[$selectedMethod]['label'] : '')) }}"
                                        maxlength="80"
                                        @readonly(! $selectedMethod || $paymentMethods[$selectedMethod]['category'] !== 'qris')
                                        data-sender-bank-name
                                        placeholder="{{ __('Contoh: BCA, GoPay, atau bank/aplikasi yang Anda gunakan') }}"
                                        @disabled($enabledMethods->isEmpty())
                                    >
                                </label>
                            </fieldset>
                            <label class="guest-field" for="payment_proof">
                                <span>{{ __('Foto bukti pembayaran') }}</span>
                                <span class="payment-upload-dropzone">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5" /></svg>
                                    <strong data-payment-file-name>{{ __('Pilih atau jatuhkan file bukti di sini') }}</strong>
                                    <small>{{ __('JPG, PNG, atau WEBP · Maksimal 5 MB') }}</small>
                                    <input id="payment_proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required @disabled($enabledMethods->isEmpty()) data-payment-proof-file>
                                </span>
                            </label>
                        @else
                            <p>{{ __('Batas waktu pembayaran telah berakhir. Silakan buat reservasi baru.') }}</p>
                        @endif
                    </section>
                </aside>

                <section class="payment-total payment-total-flow">
                    <span>{{ __('Total tagihan') }}</span>
                    <strong>Rp{{ number_format((float) $reservation->total_price, 0, ',', '.') }}</strong>
                    @if ($reservation->expires_at && $reservation->expires_at->isFuture())
                        <label class="payment-terms">
                            <input type="checkbox" name="manual_verification_consent" value="1" required @checked(old('manual_verification_consent')) @disabled($enabledMethods->isEmpty())>
                            <span>{{ __('Saya memahami bukti pembayaran akan diverifikasi secara manual oleh admin.') }}</span>
                        </label>
                        <button class="button primary full-button" type="submit" @disabled($enabledMethods->isEmpty())>{{ __('Kirim Bukti Pembayaran') }}</button>
                    @endif
                </section>
            </div>
        </form>

        <section class="payment-footnotes">
            <section>
                <h2>{{ __('Ringkasan Reservasi') }}</h2>
                <p>{{ $reservation->guest_name }} · {{ $reservation->room->name }} · {{ implode(', ', $reservation->room_numbers ?? []) }}</p>
                <p>{{ $reservation->check_in->format('d M Y') }} {{ substr($reservation->check_in_time ?? '14:00', 0, 5) }} – {{ $reservation->check_out->format('d M Y') }} {{ substr($reservation->check_out_time ?? '12:00', 0, 5) }} · {{ __(':count tamu', ['count' => $reservation->guest_count]) }} · {{ __(':count kamar', ['count' => $reservation->room_count]) }}</p>
            </section>
        </section>
    @endif

    <p class="payment-copy-feedback" aria-live="polite" data-copy-feedback></p>
@endsection

@section('switch')
    <a href="{{ route('reservations.index') }}">{{ __('Kembali ke pesanan saya') }}</a>
@endsection
