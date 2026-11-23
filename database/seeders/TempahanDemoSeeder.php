<?php

namespace Database\Seeders;

use App\Models\OrderForm;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data CONTOH tempahan online untuk local dev / demo sahaja.
 * - 3 outlet cawangan (Tangkak, Yong Peng, Melaka) dengan nombor WhatsApp CONTOH.
 * - Menu ujian dari screenshot: Double Butter Kaya Toast & Set Talam Dua Muka.
 * - Staf cawangan demo: tangkak@pakmaon.test / password (sama seperti user demo).
 * Tukar semua nombor WhatsApp & waktu operasi di Tempahan > Tetapan Outlet sebelum guna sebenar.
 */
class TempahanDemoSeeder extends Seeder
{
    public function run(User $owner, bool $withStaff = true, bool $onlyIfNoForms = true): void
    {
        if ($onlyIfNoForms && OrderForm::where('user_id', $owner->id)->exists()) {
            return;
        }

        $outlets = collect([
            ['Pak Maon Western Tangkak', 'tangkak', '60100000001', 'Tangkak, Johor'],
            ['Pak Maon Western Yong Peng', 'yong-peng', '60100000002', 'Yong Peng, Johor'],
            ['Pak Maon Western Melaka', 'melaka', '60100000003', 'Melaka'],
        ])->map(function ($o) use ($owner) {
            $outlet = Outlet::firstOrCreate(['user_id' => $owner->id, 'name' => $o[0]]);
            $outlet->update([
                'slug' => $o[1], 'whatsapp' => $o[2], 'address' => $o[3], 'accepts_orders' => true,
                'slot_minutes' => 30, 'slot_capacity' => 40, 'max_guests_per_booking' => 20, 'lead_minutes' => 60, 'advance_days' => 30,
            ]);
            foreach (range(0, 6) as $d) {
                $outlet->hours()->updateOrCreate(['weekday' => $d], [
                    'opens_at' => '11:00', 'closes_at' => '23:00',
                    'is_closed' => $o[1] === 'tangkak' && $d === 2,   // contoh: Tangkak tutup hari Selasa
                ]);
            }

            return $outlet;
        });

        $roti = ProductCategory::create(['user_id' => $owner->id, 'name' => 'Roti & Toast', 'sort' => 1]);
        $set = ProductCategory::create(['user_id' => $owner->id, 'name' => 'Set Talam', 'sort' => 2]);

        $toast = Product::create([
            'user_id' => $owner->id, 'product_category_id' => $roti->id, 'name' => 'Double Butter Kaya Toast', 'sku' => 'PMW-TOAST-DBK',
            'description' => 'Roti bakar rangup dengan butter berganda dan kaya.', 'price_cents' => 530, 'sort' => 1,
        ]);
        $talam = Product::create([
            'user_id' => $owner->id, 'product_category_id' => $set->id, 'name' => 'Set Talam Dua Muka Spaghetti Chicken',
            'sku' => 'PMW-TALAM-SPG', 'description' => 'Set kongsi spaghetti dengan pilihan ayam.', 'has_variants' => true, 'sort' => 1,
        ]);
        foreach ([['Chicken Crispy Breast', 5830, 'PMW-TALAM-SPG-CB'], ['Chicken Grill', 6255, 'PMW-TALAM-SPG-CG'], ['Crispy Chicken Thigh', 6470, 'PMW-TALAM-SPG-CT']] as $i => [$n, $p, $sku]) {
            $lib = Variant::create(['user_id' => $owner->id, 'name' => $n, 'price_cents' => $p, 'sku' => $sku, 'sort' => $i]);
            $talam->variants()->create(['variant_id' => $lib->id, 'name' => $n, 'price_cents' => $p, 'sku' => $sku, 'sort' => $i]);
        }

        $notice = 'Makanan pre-order disediakan 5–10 minit sebelum waktu yang ditetapkan. Jika anda lewat dan makanan menjadi sejuk, penggantian atau pemanasan semula tertakluk kepada polisi kedai mengikut jenis hidangan.';
        $base = [
            'user_id' => $owner->id, 'status' => 'published', 'published_at' => now(),
            'notices' => ['Harga menu sudah termasuk SST 6%.', $notice], 'booking_notice' => $notice,
            'prices_include_sst' => true, 'sst_rate' => 6, 'action_whatsapp' => true, 'action_online' => true,
        ];
        $forms = [
            OrderForm::create($base + [
                'title' => 'Pak Maon Western: Tempah & Pesan', 'slug' => 'pak-maon-western',
                'description' => 'Tempah meja dan pre-order menu, atau pesan untuk pickup.',
                'purpose_booking' => true, 'purpose_purchase' => true, 'booking_mode' => 'preorder_required', 'allow_pickup' => true,
            ]),
            OrderForm::create($base + [
                'title' => 'Tempah Meja Pak Maon Western', 'slug' => 'tempah-meja',
                'description' => 'Tempah meja. Pre-order menu adalah pilihan.',
                'purpose_booking' => true, 'purpose_purchase' => false, 'booking_mode' => 'table_only_allowed',
            ]),
        ];
        foreach ($forms as $form) {
            $form->outlets()->sync($outlets->pluck('id'));
            $form->products()->sync([$toast->id => ['sort' => 1], $talam->id => ['sort' => 2]]);
        }

        if (! $withStaff) {
            return;
        }

        $staff = User::firstOrCreate(['email' => 'tangkak@pakmaon.test'], ['name' => 'Staf Tangkak', 'password' => Hash::make('password')]);
        $staff->assignedOutlets()->syncWithoutDetaching([$outlets->first()->id => ['role' => 'staff']]);
    }
}
