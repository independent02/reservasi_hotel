@extends('layouts.auth')

@section('bodyClass', 'auth-glass-page')
@section('title', __('Lupa kata sandi'))
@section('eyebrow', __('Pemulihan akun'))
@section('heading', __('Lupa kata sandi?'))
@section('intro', __('Verifikasi akun Google yang terhubung dengan akun Stayvia Anda untuk membuat kata sandi baru tanpa email.'))

@section('cardIntro')
    <div class="customer-login-mark" aria-hidden="true">
        <img src="{{ asset('assets/logo.png') }}" alt="">
    </div>
@endsection

@section('form')
    <a class="customer-login-google" href="{{ route('auth.google.password-reset') }}">
        <svg viewBox="0 0 48 48" aria-hidden="true" focusable="false">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z" />
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.91c-.58 2.96-2.26 5.48-4.75 7.18l7.73 6C44.4 38.01 46.98 31.89 46.98 24.55Z" />
            <path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.87.93 7.53 2.56 10.78l7.97-6.19Z" />
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.8l-7.73-6c-2.14 1.45-4.89 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48Z" />
        </svg>
        <span>{{ __('Lanjutkan dengan Google') }}</span>
    </a>
@endsection

@section('switch')
    <span>{{ __('Ingat kata sandi?') }} <a href="{{ route('login') }}">{{ __('Kembali ke masuk') }}</a></span>
@endsection
