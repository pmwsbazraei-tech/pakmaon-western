<?php

namespace Tests\Feature;

use App\Models\OrderForm;
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

    public function test_unknown_email_fails_without_changes(): void
    {
        $this->artisan('tempahan:demo', ['email' => 'tiada@example.test'])->assertExitCode(1);
        $this->assertSame(0, OrderForm::count());
    }
}
