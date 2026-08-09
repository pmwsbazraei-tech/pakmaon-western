<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DefaultOutletData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModuleRoutesTest extends TestCase
{
    use RefreshDatabase;

    public static function moduleRoutes(): array
    {
        return [
            ['dashboard'], ['scan'], ['staff'], ['fixed-costs'], ['sales'],
            ['petty-cash'], ['menu'], ['equipment'], ['capex'], ['roi'],
            ['report'], ['settings'],
        ];
    }

    #[DataProvider('moduleRoutes')]
    public function test_module_route_renders_for_authenticated_user(string $routeName): void
    {
        $user = User::factory()->create();
        DefaultOutletData::provision($user);

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_new_user_is_auto_provisioned_with_default_outlet(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->currentOutlet());

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $outlet = $user->fresh()->currentOutlet();
        $this->assertNotNull($outlet);
        $this->assertSame('Pak Maon Western', $outlet->name);
        $this->assertSame(4, $outlet->staff()->count());
        $this->assertSame(12, $outlet->equipment()->count());
        $this->assertSame(4, $outlet->menus()->count());
    }
}
