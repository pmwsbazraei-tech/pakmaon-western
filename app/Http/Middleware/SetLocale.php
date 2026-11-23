<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bahasa paparan: Bahasa Melayu (lalai) atau English, disimpan dalam sesi.
 * Teks sumber ditulis dalam BM; terjemahan Inggeris dalam lang/en.json.
 */
class SetLocale
{
    public const LOCALES = ['ms' => 'BM', 'en' => 'EN'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->hasSession() ? $request->session()->get('locale', 'ms') : 'ms';
        app()->setLocale(array_key_exists($locale, self::LOCALES) ? $locale : 'ms');

        return $next($request);
    }
}
