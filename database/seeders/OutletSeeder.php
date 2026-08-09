<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\DefaultOutletData;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    /**
     * Isi satu outlet contoh dengan data DEFAULT identik prototaip
     * untuk user yang diberi (atau user demo pertama).
     */
    public function run(?User $user = null): void
    {
        $user ??= User::query()->orderBy('id')->firstOrFail();

        // Elak duplikasi jika seeder dijalankan berulang
        if ($user->outlets()->exists()) {
            return;
        }

        DefaultOutletData::provision($user);
    }
}
