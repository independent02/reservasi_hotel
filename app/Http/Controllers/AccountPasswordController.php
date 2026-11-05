<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AccountPasswordController
{
    public function edit(Request $request): View
    {
        return view('auth.change-password', [
            'email' => $request->user()->email,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Masukkan alamat email dengan format yang benar.',
            'email.max' => 'Alamat email terlalu panjang.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        if ($validated['email'] !== Str::lower($request->user()->email)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email harus sesuai dengan akun yang sedang masuk.']);
        }

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()
            ->route('home')
            ->with('status', 'Kata sandi berhasil diubah.');
    }
}
