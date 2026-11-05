<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale'));
        app()->setLocale(in_array($locale, ['id', 'en'], true) ? $locale : 'id');

        return $next($request);
    }
}
