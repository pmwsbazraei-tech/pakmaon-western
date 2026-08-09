<?php

namespace App\Http\Middleware;

use App\Support\DefaultOutletData;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureOutlet
{
    /**
     * Pastikan user log masuk mempunyai sekurang-kurangnya satu outlet.
     * User baharu diberi data DEFAULT (macam prototaip mula dengan objek DEFAULT).
     * Kongsi outlet aktif ke semua view sebagai $outlet.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $outlet = $user->currentOutlet();

            if (! $outlet) {
                $outlet = DefaultOutletData::provision($user);
            }

            View::share('outlet', $outlet);
        }

        return $next($request);
    }
}
