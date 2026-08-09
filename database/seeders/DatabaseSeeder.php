<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\DefaultOutletData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User demo + outlet contoh dengan data DEFAULT prototaip
        $user = User::firstOrCreate(
            ['email' => 'demo@pakmaon.test'],
            ['name' => 'Pak Maon Demo', 'password' => Hash::make('password')],
        );

        if (! $user->outlets()->exists()) {
            DefaultOutletData::provision($user);
        }
    }
}
