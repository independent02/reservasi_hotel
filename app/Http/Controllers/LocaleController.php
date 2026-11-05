<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['id', 'en'], true), 404);

        $request->session()->put('locale', $locale);
        app()->setLocale($locale);

        $previousUrl = $request->headers->get('referer');
        $previousOrigin = is_string($previousUrl) ? parse_url($previousUrl) : false;

        if (is_array($previousOrigin)
            && in_array($previousOrigin['scheme'] ?? null, ['http', 'https'], true)
            && $previousOrigin['scheme'] === $request->getScheme()
            && isset($previousOrigin['host'])
            && strcasecmp($request->getHost(), $previousOrigin['host']) === 0
            && ($previousOrigin['port'] ?? $request->getPort()) === $request->getPort()) {
            return redirect()->to($previousUrl);
        }

        return redirect()->route('home');
    }
}
