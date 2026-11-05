@extends('layouts.auth')

@section('bodyClass', 'auth-glass-page')
@section('title', __('Ganti kata sandi'))
@section('eyebrow', __('Keamanan akun'))
@section('heading', __('Ganti kata sandi'))
@section('intro', __('Formulir ini hanya tersedia untuk akun yang sudah masuk.'))

@section('cardIntro')
    <div class="customer-login-mark" aria-hidden="true">
        <img src="{{ asset('assets/logo.png') }}" alt="">
    </div>
@endsection

@section('form')
    <form class="auth-form customer-login-form auth-register-form" action="{{ route('account.password.update') }}" method="post">
        @csrf
        @method('put')

        <div class="customer-login-field">
            <label for="email">{{ __('Alamat email akun') }}</label>
            <div class="customer-login-input-wrap">
                <svg class="customer-login-field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
                    <path d="m4.5 7 7.5 6 7.5-6" />
                </svg>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $email) }}"
                    placeholder="{{ __('nama@email.com') }}"
                    autocomplete="username"
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
            <label for="password">{{ __('Kata sandi baru') }}</label>
            <div class="customer-login-input-wrap">
                <svg class="customer-login-field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="10" width="14" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                </svg>
                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="{{ __('Minimal 8 karakter') }}"
                    autocomplete="new-password"
                    minlength="8"
                    aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                    @if ($errors->has('password')) aria-describedby="password-error" @endif
                    required
                >
            </div>
            @error('password')
                <small class="customer-login-field-error" id="password-error">{{ $message }}</small>
            @enderror
        </div>

        <div class="customer-login-field">
            <label for="password_confirmation">{{ __('Konfirmasi kata sandi baru') }}</label>
            <div class="customer-login-input-wrap">
                <svg class="customer-login-field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="10" width="14" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                </svg>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    placeholder="{{ __('Ulangi kata sandi baru') }}"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </div>
        </div>

        <button class="button primary" type="submit">{{ __('Simpan kata sandi baru') }}</button>
    </form>
@endsection

@section('switch')
    <span><a href="{{ route('home') }}">{{ __('Kembali ke beranda') }}</a></span>
@endsection
