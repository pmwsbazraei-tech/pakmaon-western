<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Tetapan tempahan (landing page, produk, outlet, staf) untuk pemilik sahaja. */
class EnsureCanManageShop
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->canManageShop(), 403, 'Hanya pemilik boleh mengurus tetapan tempahan.');

        return $next($request);
    }
}
