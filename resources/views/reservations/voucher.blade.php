<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.preferences-head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('E-Voucher :code — Hotel HAR Official', ['code' => $reservation->voucher_code]) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page voucher-page">
    <header class="flow-topbar voucher-topbar">
        <a class="logo" href="{{ route('home') }}" aria-label="{{ __('Hotel HAR Official, beranda') }}">
            <img class="brand-mark" src="{{ asset('assets/logo.png') }}" alt="">
            Hotel HAR Official
        </a>
        <div class="flow-topbar-actions">
            <a class="flow-topbar-action" href="{{ route('reservations.index') }}">{{ __('Kembali ke pesanan saya') }}</a>
            <x-preferences-controls variant="topbar" />
        </div>
    </header>

    <main class="voucher-card card booking-success">
        <span class="booking-success-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><path d="m5 12 4.5 4.5L19 7" /></svg>
        </span>
        <h1>{{ __('Booking Berhasil!') }}</h1>
        <p>{{ __('Pembayaran telah diverifikasi. Simpan e-voucher ini dan tunjukkan kode booking saat check-in.') }}</p>

        <article class="booking-ticket">
            <header class="booking-ticket-header">
                <div>
                    <strong>Hotel HAR Official</strong>
                    <span>E-VOUCHER · PT HAR Hotel Indonesia</span>
                </div>
                <div class="booking-ticket-code">
                    <span>{{ __('KODE BOOKING') }}</span>
                    <strong>{{ $reservation->voucher_code }}</strong>
                </div>
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
            <footer class="booking-ticket-footer">
                <span>LUNAS</span>
                <span>{{ $reservation->hotel->name }}</span>
            </footer>
        </article>

        @include('partials.voucher-benefits', [
            'room' => $reservation->room,
            'voucherBenefits' => $voucherBenefits,
        ])

        <div class="booking-success-actions">
            <button class="button primary print-button" type="button" onclick="window.print()">{{ __('Cetak / Simpan sebagai PDF') }}</button>
        </div>
        <p class="voucher-note">{{ __('Kode booking ini dibuat untuk reservasi Anda. Pastikan nama tamu dan tanggal menginap sudah sesuai.') }}</p>
    </main>
    @include('partials.help')
</body>
</html>
