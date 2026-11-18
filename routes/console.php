<?php

use App\Models\OrderForm;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\TempahanDemoSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Data demo tempahan online untuk akaun pemilik SEDIA ADA, terus "live":
// - 3 cawangan contoh + menu contoh + 2 landing page (tempah-meja, pak-maon-western) jika slug masih kosong.
// - Setiap landing page pemilik yang tiada cawangan / produk dilengkapkan dengan data contoh, dan draf diterbitkan.
// Tiada akaun staf berkata laluan "password" dicipta.
Artisan::command('tempahan:demo {email : Emel akaun pemilik}', function (string $email) {
    $owner = User::where('email', $email)->first();
    if (! $owner) {
        $this->error("Akaun {$email} tidak dijumpai.");

        return 1;
    }

    $demoForms = ['pak-maon-western', 'tempah-meja'];
    $demoOutlets = ['tangkak', 'yong-peng', 'melaka'];
    $takenByOthers = OrderForm::whereIn('slug', $demoForms)->where('user_id', '!=', $owner->id)->exists()
        || Outlet::whereIn('slug', $demoOutlets)->where('user_id', '!=', $owner->id)->exists();
    $alreadySeeded = OrderForm::whereIn('slug', $demoForms)->where('user_id', $owner->id)->exists();

    DB::transaction(function () use ($owner, $takenByOthers, $alreadySeeded) {
        if (! $takenByOthers && ! $alreadySeeded) {
            (new TempahanDemoSeeder)->setContainer(app())
                ->__invoke(['owner' => $owner, 'withStaff' => false, 'onlyIfNoForms' => false]);
            // Bayaran online perlu kunci CHIP; tanpanya, guna WhatsApp sahaja
            if (config('services.payment_driver') !== 'chip') {
                OrderForm::where('user_id', $owner->id)->whereIn('slug', ['pak-maon-western', 'tempah-meja'])->update(['action_online' => false]);
            }
        }

        $outletIds = Outlet::where('user_id', $owner->id)->where('accepts_orders', true)->pluck('id');
        if ($outletIds->isEmpty()) {
            return;
        }

        foreach (OrderForm::where('user_id', $owner->id)->get() as $form) {
            if (! $form->outlets()->where('accepts_orders', true)->exists()) {
                $form->outlets()->syncWithoutDetaching($outletIds);
            }
            if (! $form->products()->where('is_hidden', false)->exists()) {
                $product = Product::create([
                    'user_id' => $owner->id, 'name' => $form->title, 'price_cents' => 1000, 'sort' => 1,
                    'description' => 'CONTOH: tukar harga & butiran di Tempahan > Produk.',
                ]);
                $form->products()->syncWithoutDetaching([$product->id => ['sort' => 1]]);
            }
            if ($form->status === 'draft') {
                $form->update(['status' => 'published', 'published_at' => now()]);
            }
        }
    });

    if ($takenByOthers) {
        $this->warn('Slug demo (tempah-meja / pak-maon-western) sudah digunakan akaun lain: page demo tidak dicipta.');
    }
    if (! Outlet::where('user_id', $owner->id)->where('accepts_orders', true)->exists()) {
        $this->error('Tiada outlet yang menerima tempahan. Hidupkan "Terima tempahan online" di Tempahan > Outlet, kemudian jalankan semula.');

        return 1;
    }

    $this->info('Siap. Landing page yang live:');
    OrderForm::where('user_id', $owner->id)->where('status', 'published')->orderBy('id')
        ->each(fn ($f) => $this->line('  '.route('form.show', $f->slug)));
    $this->warn('Nombor WhatsApp & harga ialah CONTOH. Tukar di Tempahan > Outlet dan Tempahan > Produk.');

    return 0;
})->purpose('Tambah data demo tempahan online & terbitkan semua landing page pemilik');
