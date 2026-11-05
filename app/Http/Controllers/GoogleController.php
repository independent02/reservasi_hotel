<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController
{
    public function redirectToGoogle(): RedirectResponse
    {
        return $this->redirectToGoogleFor(false);
    }

    public function redirectToGoogleForPasswordReset(Request $request): RedirectResponse
    {
        return $this->redirectToGoogleFor(true, $request);
    }

    private function redirectToGoogleFor(bool $passwordReset, ?Request $request = null): RedirectResponse
    {
        if (! filled(config('services.google.client_id'))
            || ! filled(config('services.google.client_secret'))
            || ! filled(config('services.google.redirect'))) {
            return redirect()->route($passwordReset ? 'password.request' : 'login')->with(
                'warning',
                'Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_REDIRECT_URL di file .env.',
            );
        }

        if ($passwordReset) {
            $request?->session()->put('google_password_reset', true);
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $passwordReset = $request->session()->pull('google_password_reset', false);
        $googleUser = Socialite::driver('google')->user();
        $googleProfile = $googleUser->getRaw();
        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! filter_var($googleProfile['email_verified'] ?? false, FILTER_VALIDATE_BOOL)) {
            return redirect()
                ->route($passwordReset ? 'password.request' : 'login')
                ->with('warning', 'Google tidak memberikan alamat email terverifikasi. Silakan coba akun Google lain.');
        }

        if ($passwordReset) {
            $user = User::query()->where('email', $email)->first();

            if (! $user || ! in_array($user->role, ['customer', 'pemesan'], true)) {
                return redirect()
                    ->route('password.request')
                    ->with('warning', 'Tidak ditemukan akun pemesan yang cocok dengan akun Google tersebut.');
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()
                ->route('account.password.edit')
                ->with('status', 'Identitas Google terverifikasi. Silakan buat kata sandi baru untuk akun Anda.');
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => Str::limit(trim((string) $googleUser->getName()) ?: Str::before($email, '@'), 100, ''),
                'password' => Str::random(40),
                'role' => 'pemesan',
                'email_verified_at' => now(),
            ],
        );

        if (! in_array($user->role, ['customer', 'pemesan'], true)) {
            return redirect()->route('login')->with('warning', 'Akun ini tidak dapat digunakan untuk masuk ke Portal Pemesan.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
