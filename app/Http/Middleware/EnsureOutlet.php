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
            // User baharu diberi outlet pertama dengan data DEFAULT
            if (! $user->outlets()->exists()) {
                DefaultOutletData::provision($user);
            }

            $outlet = $user->currentOutlet();
            session(['active_outlet_id' => $outlet->id]);

            View::share('outlet', $outlet);
            View::share('outlets', $user->outlets()->orderBy('id')->get());
        }

        return $next($request);
    }
}
