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
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $user = $request->user();

        if ($user) {
            // Staf cawangan (tempahan sahaja) tiada akses ke modul kewangan
            // (kecuali laluan tempahan yang dibuka dengan middleware `outlet:staff`)
            if ($user->isBranchStaff()) {
                return $mode === 'staff' ? $next($request) : redirect()->route('tempahan.bookings');
            }

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
