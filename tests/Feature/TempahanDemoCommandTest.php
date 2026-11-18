<?php

namespace Tests\Feature;

use App\Models\OrderForm;
use App\Models\Product;
use App\Models\User;
use App\Support\DefaultOutletData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempahanDemoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_published_landing_pages_for_existing_owner_without_demo_staff(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        DefaultOutletData::provision($owner);

        $this->artisan('tempahan:demo', ['email' => 'owner@example.test'])->assertExitCode(0);

        $this->assertSame(2, OrderForm::where('user_id', $owner->id)->where('status', 'published')->count());
        $this->assertDatabaseMissing('users', ['email' => 'tangkak@pakmaon.test']);
        $this->get('/p/tempah-meja')->assertOk()->assertSee('Double Butter Kaya Toast');
        $this->get('/p/pak-maon-western')->assertOk();

        // Jalankan semula: tiada pendua
        $this->artisan('tempahan:demo', ['email' => 'owner@example.test'])->assertExitCode(0);
        $this->assertSame(2, OrderForm::count());
    }

    public function test_completes_existing_empty_page_and_publishes_draft(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        DefaultOutletData::provision($owner);
        $base = ['user_id' => $owner->id, 'purpose_purchase' => true, 'purpose_booking' => false, 'allow_pickup' => true, 'action_whatsapp' => true];
        $live = OrderForm::create($base + ['title' => 'Sambal Merah Bonda', 'slug' => 'sambal-merah-bonda', 'status' => 'published', 'published_at' => now()]);
        $draft = OrderForm::create($base + ['title' => 'Kuih Raya', 'slug' => 'kuih-raya', 'status' => 'draft']);

        $this->artisan('tempahan:demo', ['email' => 'owner@example.test'])->assertExitCode(0);

        foreach ([$live, $draft] as $form) {
            $form->refresh();
            $this->assertSame('published', $form->status);
            $this->assertTrue($form->outlets()->where('accepts_orders', true)->exists());
            $this->assertTrue($form->products()->exists());
        }
        $this->get('/p/sambal-merah-bonda')->assertOk()->assertDontSee('Tiada cawangan menerima tempahan');
        $this->get('/p/kuih-raya')->assertOk();
        $this->get('/p/tempah-meja')->assertOk();

        // Jalankan semula: tiada produk pendua
        $count = $owner->hasMany(Product::class)->count();
        $this->artisan('tempahan:demo', ['email' => 'owner@example.test'])->assertExitCode(0);
        $this->assertSame($count, $owner->hasMany(Product::class)->count());
    }

    public function test_unknown_email_fails_without_changes(): void
    {
        $this->artisan('tempahan:demo', ['email' => 'tiada@example.test'])->assertExitCode(1);
        $this->assertSame(0, OrderForm::count());
    }
}
