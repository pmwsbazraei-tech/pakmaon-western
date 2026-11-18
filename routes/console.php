<?php

use App\Models\OrderForm;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\TempahanDemoSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Data demo tempahan online (3 cawangan, menu contoh, 2 landing page diterbitkan)
// untuk akaun pemilik SEDIA ADA. Tiada akaun staf berkata laluan "password" dicipta.
Artisan::command('tempahan:demo {email : Emel akaun pemilik}', function (string $email) {
    $owner = User::where('email', $email)->first();
    if (! $owner) {
        $this->error("Akaun {$email} tidak dijumpai.");

        return 1;
    }
    if (OrderForm::where('user_id', $owner->id)->exists()) {
        $this->warn('Akaun ini sudah ada landing page. Tiada data demo ditambah.');

        return 0;
    }
    $taken = OrderForm::whereIn('slug', ['pak-maon-western', 'tempah-meja'])->exists()
        || Outlet::whereIn('slug', ['tangkak', 'yong-peng', 'melaka'])->exists();
    if ($taken) {
        $this->error('Slug demo sudah digunakan oleh akaun lain. Tiada data ditambah.');

        return 1;
    }

    DB::transaction(fn () => (new TempahanDemoSeeder)
        ->setContainer(app())->__invoke(['owner' => $owner, 'withStaff' => false]));

    $this->info('Siap. Landing page demo:');
    $this->line('  '.route('form.show', 'pak-maon-western'));
    $this->line('  '.route('form.show', 'tempah-meja'));
    $this->warn('Nombor WhatsApp cawangan ialah CONTOH (6010000000x). Tukar di Tempahan > Outlet.');

    return 0;
})->purpose('Tambah data demo tempahan online + landing page ke akaun pemilik');
