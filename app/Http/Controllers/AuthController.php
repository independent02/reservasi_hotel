<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController
{
    public function showLogin(): View
    {
        return view('auth.login', ['loginType' => 'customer']);
    }

    public function login(Request $request): RedirectResponse
    {
        return $this->authenticate($request, 'customer');
    }

    public function showAdminLogin(): View
    {
        return view('auth.login', ['loginType' => 'admin']);
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        return $this->authenticate($request, 'admin');
    }

    private function authenticate(Request $request, string $role): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email', ''))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $roles = $role === 'customer' ? ['customer', 'pemesan'] : [$role];
        $authenticated = false;

        foreach ($roles as $allowedRole) {
            if (Auth::attempt([...$credentials, 'role' => $allowedRole], $request->boolean('remember'))) {
                $authenticated = true;
                break;
            }
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route($role === 'admin' ? 'admin.dashboard' : 'home');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap maksimal 100 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Masukkan alamat email dengan format yang benar.',
            'email.max' => 'Alamat email terlalu panjang.',
            'email.unique' => 'Alamat email ini sudah terdaftar. Silakan masuk dengan akun yang sudah ada.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama. Ketik kata sandi yang sama pada kedua kolom.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'customer',
        ]);
        return redirect()
            ->route('login')
            ->with('status', 'Akun berhasil dibuat. Silakan masuk menggunakan email dan kata sandi Anda.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
