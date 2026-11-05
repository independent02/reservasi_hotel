@extends('layouts.auth')

@section('title', __('Dashboard Admin'))
@section('eyebrow', __('Portal pemilik hotel'))
@section('heading', __('Dashboard Admin'))
@section('intro', __('Ringkasan aktivitas dan verifikasi reservasi Hotel HAR Official.'))
@section('mainClass', 'admin-dashboard-main')
@section('cardClass', 'admin-dashboard-card')
@section('bodyClass', 'auth-glass-page admin-dashboard-page')

@section('form')
    <section class="admin-dashboard-welcome" aria-label="{{ __('Informasi akun admin') }}">
        <div>
            <span>{{ __('Anda masuk sebagai') }}</span>
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ auth()->user()->email }} - {{ __(ucfirst(auth()->user()->role)) }}</small>
        </div>
        <form action="{{ route('logout') }}" method="post">
            @csrf
            <button class="button soft" type="submit">{{ __('Keluar') }}</button>
        </form>
    </section>

    <section class="admin-dashboard-stats" aria-label="{{ __('Ringkasan hotel') }}">
        <article>
            <span>{{ __('Menunggu verifikasi') }}</span>
            <strong>{{ $pendingReviews }}</strong>
            <small>{{ __('Bukti pembayaran perlu diperiksa') }}</small>
        </article>
        <article>
            <span>{{ __('Total reservasi terkonfirmasi') }}</span>
            <strong>{{ $confirmedBookings }}</strong>
            <small>{{ __('Reservasi dengan status terkonfirmasi') }}</small>
        </article>
        <article>
            <span>{{ __('Akun pemesan') }}</span>
            <strong>{{ $customers }}</strong>
            <small>{{ __('Akun pelanggan terdaftar') }}</small>
        </article>
    </section>

    <section class="admin-dashboard-action" aria-label="{{ __('Aksi verifikasi pembayaran') }}">
        <div>
            <h2>{{ __('Verifikasi pembayaran') }}</h2>
            <p>{{ __('Buka bukti transfer, periksa jumlah dan tujuan pembayaran, lalu setujui atau batalkan reservasi.') }}</p>
        </div>
        <a class="button primary" href="{{ route('admin.payment-reviews.index') }}">
            {{ __('Lihat antrean') }}{{ $pendingReviews > 0 ? ' ('.$pendingReviews.')' : '' }}
        </a>
    </section>
@endsection

@section('switch')
    <a href="{{ route('home') }}">{{ __('Lihat situs sebagai pengunjung') }}</a>
@endsection
