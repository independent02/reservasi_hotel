@extends('layouts.auth')

@php($isAdminLogin = ($loginType ?? 'customer') === 'admin')

@section('bodyClass', 'auth-glass-page')
@section('title', $isAdminLogin ? __('Masuk admin portal') : __('Masuk Pemesan'))
@section('eyebrow', $isAdminLogin ? 'Portal pemilik hotel' : 'Portal pemesan')
@section('heading', $isAdminLogin ? __('Masuk Admin Hotel HAR Official') : __('Masuk ke akun pemesan'))
@section('intro', $isAdminLogin ? __('Kelola hotel dan verifikasi pembayaran menggunakan akun admin.') : __('Masuk untuk membuat dan mengelola reservasi hotel Anda.'))

@section('cardIntro')
    <div class="customer-login-mark" aria-hidden="true">
        <img src="{{ asset('assets/logo.png') }}" alt="">
    </div>
@endsection

@section('form')
    <form class="auth-form customer-login-form" action="{{ $isAdminLogin ? route('admin.login.store') : route('login.store') }}" method="post">
        @csrf
        <div class="customer-login-field">
            <label for="email">{{ __('Alamat email') }}</label>
            <div class="customer-login-input-wrap">
                <svg class="customer-login-field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
                    <path d="m4.5 7 7.5 6 7.5-6" />
                </svg>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', request('email')) }}"
                    placeholder="{{ __('nama@email.com') }}"
                    autocomplete="email"
                    maxlength="255"
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    @if ($errors->has('email')) aria-describedby="email-error" @endif
                    required
                    autofocus
                >
            </div>
            @error('email')
                <small class="customer-login-field-error" id="email-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="customer-login-field">
            <label for="password">{{ __('Kata sandi') }}</label>
            <div class="customer-login-input-wrap">
                <svg class="customer-login-field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="10" width="14" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v2" />
                </svg>
                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="{{ __('Masukkan kata sandi') }}"
                    autocomplete="current-password"
                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    @if ($errors->has('password')) aria-describedby="password-error" @endif
                    required
                >
                <button
                    class="customer-login-password-toggle"
                    type="button"
                    data-password-toggle
                    aria-label="{{ __('Tampilkan kata sandi') }}"
                    aria-pressed="false"
                >
                    <svg class="customer-login-eye-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" />
                        <circle cx="12" cy="12" r="2.5" />
                    </svg>
                </button>
            </div>
            @error('password')
                <small class="customer-login-field-error" id="password-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="customer-login-options">
            <label class="customer-login-remember" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                <span>{{ __('Ingat saya') }}</span>
            </label>
            @unless ($isAdminLogin)
                <a class="customer-login-forgot" href="{{ route('password.request') }}">{{ __('Lupa kata sandi?') }}</a>
            @endunless
        </div>
        <button class="button primary" type="submit">{{ __('Masuk') }}</button>
    </form>
    @unless ($isAdminLogin)
        <a class="customer-login-google" href="{{ route('auth.google.redirect') }}">
            <svg viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z" />
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.91c-.58 2.96-2.26 5.48-4.75 7.18l7.73 6C44.4 38.01 46.98 31.89 46.98 24.55Z" />
                <path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.87.93 7.53 2.56 10.78l7.97-6.19Z" />
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.8l-7.73-6c-2.14 1.45-4.89 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48Z" />
            </svg>
            <span>{{ __('Masuk dengan Google') }}</span>
        </a>
    @endunless
@endsection

@section('switch')
    @if ($isAdminLogin)
        {{ __('Pemesan?') }} <a href="{{ route('login') }}">{{ __('Masuk sebagai pemesan') }}</a>
    @else
        <span>{{ __('Belum memiliki akun pemesan?') }} <a href="{{ route('register') }}">{{ __('Buat akun') }}</a></span>
        <span>{{ __('Pemilik hotel?') }} <a href="{{ route('admin.login') }}">{{ __('Masuk admin portal') }}</a></span>
    @endif
@endsection
