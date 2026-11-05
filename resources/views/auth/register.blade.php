@extends('layouts.auth')

@section('bodyClass', 'auth-glass-page')
@section('title', __('Buat akun'))
@section('eyebrow', __('Mulai perjalanan Anda'))
@section('heading', __('Buat akun Hotel HAR Official'))
@section('intro', __('Daftar dengan email agar Anda bisa melanjutkan reservasi.'))

@section('cardIntro')
    <div class="customer-login-mark" aria-hidden="true">
        <img src="{{ asset('assets/logo.png') }}" alt="">
    </div>
@endsection

@section('form')
    <form class="auth-form customer-login-form auth-register-form" action="{{ route('register.store') }}" method="post">
        @csrf
        <div class="customer-login-field">
            <label for="name">{{ __('Nama lengkap') }}</label>
            <div class="customer-login-input-wrap">
                <svg class="customer-login-field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.5" />
                    <path d="M5 20a7 7 0 0 1 14 0" />
                </svg>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    placeholder="{{ __('Nama lengkap') }}"
                    autocomplete="name"
                    maxlength="100"
                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                    @if ($errors->has('name')) aria-describedby="name-error" @endif
                    required
                    autofocus
                >
            </div>
            @error('name')
                <small class="customer-login-field-error" id="name-error">{{ $message }}</small>
            @enderror
        </div>

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
                    value="{{ old('email') }}"
                    placeholder="nama@email.com"
                    autocomplete="email"
                    maxlength="255"
                    aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                    @if ($errors->has('email')) aria-describedby="email-error" @endif
                    required
                >
            </div>
            @error('email')
                <small class="customer-login-field-error" id="email-error">
                    {{ $message }}
                    <a href="{{ route('login', ['email' => old('email')]) }}">{{ __('Masuk dengan email ini') }}</a>
                </small>
            @enderror
        </div>

        <div class="customer-login-field">
            <label for="password">{{ __('Kata sandi') }}</label>
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
                    aria-describedby="{{ $errors->has('password') ? 'password-error password-hint' : 'password-hint' }}"
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
            <small class="customer-login-field-hint" id="password-hint">{{ __('Gunakan minimal 8 karakter.') }}</small>
        </div>

        <div class="customer-login-field">
            <label for="password_confirmation">{{ __('Ulangi kata sandi') }}</label>
            <div class="customer-login-input-wrap">
                <svg class="customer-login-field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="10" width="14" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                </svg>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    placeholder="{{ __('Ketik ulang kata sandi') }}"
                    autocomplete="new-password"
                    minlength="8"
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
            @if ($errors->has('password'))
                <small class="customer-login-field-error">{{ $errors->first('password') }}</small>
            @else
                <small class="customer-login-field-hint">{{ __('Ketik ulang kata sandi yang sama persis.') }}</small>
            @endif
        </div>

        <button class="button primary" type="submit">{{ __('Buat akun') }}</button>
    </form>
@endsection

@section('switch')
    {{ __('Sudah memiliki akun?') }} <a href="{{ route('login') }}">{{ __('Masuk') }}</a>
@endsection
