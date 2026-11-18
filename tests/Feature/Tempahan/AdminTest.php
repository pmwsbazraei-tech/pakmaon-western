<?php

namespace Tests\Feature\Tempahan;

use App\Livewire\Tempahan\FormEditor;
use App\Livewire\Tempahan\OrderDetail;
use App\Livewire\Tempahan\Orders;
use App\Livewire\Tempahan\OutletSettings;
use App\Livewire\Tempahan\StaffAccounts;
use App\Models\OrderForm;
use App\Models\User;
use App\Support\DefaultOutletData;
use Livewire\Livewire;

class AdminTest extends PmwTestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('tempahan.overview'))->assertRedirect(route('login'));
    }

    public function test_owner_can_open_every_tempahan_page(): void
    {
        $order = $this->placeOrder($this->bookingPayload());
        $this->actingAs($this->owner);

        foreach (['tempahan.overview', 'tempahan.bookings', 'tempahan.orders', 'tempahan.forms', 'tempahan.forms.create',
            'tempahan.products', 'tempahan.products.create', 'tempahan.outlets', 'tempahan.staff'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('tempahan.orders.show', $order))->assertOk()->assertSee($order->reference);
        $this->get(route('tempahan.forms.edit', $this->form()))->assertOk();
        $this->get(route('tempahan.products.edit', $this->talam()))->assertOk()->assertSee('Chicken Grill');
        // Modul kewangan sedia ada masih berfungsi untuk pemilik
        $this->get(route('dashboard'))->assertOk()->assertSee('Tempahan Online');
    }

    public function test_staff_sees_only_booking_menu_and_no_finance_modules(): void
    {
        $this->actingAs($this->staff());

        $this->get(route('dashboard'))->assertRedirect(route('tempahan.bookings'));
        $this->get(route('sales'))->assertRedirect(route('tempahan.bookings'));
        $this->get(route('tempahan.bookings'))->assertOk()->assertDontSee('Menu Food Cost')->assertDontSee('Landing Page');
        $this->get(route('tempahan.forms'))->assertForbidden();
        $this->get(route('tempahan.products'))->assertForbidden();
        $this->get(route('tempahan.staff'))->assertForbidden();

        // Staf tidak diberi outlet DEFAULT (tiada data kewangan dicipta)
        $this->assertSame(0, $this->staff()->outlets()->count());

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_staff_can_see_and_confirm_own_outlet_booking(): void
    {
        $order = $this->placeOrder($this->bookingPayload());
        $this->actingAs($this->staff());

        $this->get(route('tempahan.orders.show', $order))->assertOk()->assertSee('7:00 PM');
        Livewire::test(OrderDetail::class, ['order' => $order])->call('setStatus', 'booking_status', 'confirmed');

        $order->refresh();
        $this->assertSame('confirmed', $order->booking_status);
        $this->assertSame('unpaid', $order->payment_status); // Confirmed dan Paid direkod berasingan
        $this->get(route('tempahan.bookings', ['date' => '2026-11-19']))->assertOk()->assertSee('Aminah Test')->assertSee('Chicken Grill');
    }

    public function test_staff_cannot_access_other_outlet(): void
    {
        $melaka = $this->placeOrder($this->purchasePayload());
        $this->actingAs($this->staff());

        $this->get(route('tempahan.orders.show', $melaka))->assertForbidden();
        $this->get(route('tempahan.bookings', ['outlet' => $this->outlet('melaka')->id]))->assertForbidden();
        Livewire::test(Orders::class)->assertDontSee($melaka->reference);
        $csv = $this->get(route('tempahan.orders.export'))->streamedContent();
        $this->assertStringNotContainsString($melaka->reference, $csv);
    }

    public function test_other_owner_cannot_see_orders_forms_or_outlets(): void
    {
        $order = $this->placeOrder($this->bookingPayload());
        $other = User::factory()->create();
        DefaultOutletData::provision($other);
        $this->actingAs($other);

        $this->get(route('tempahan.orders.show', $order))->assertForbidden();
        $this->get(route('tempahan.forms.edit', $this->form()))->assertNotFound();
        $this->get(route('tempahan.products.edit', $this->toast()))->assertNotFound();
        $this->get(route('tempahan.outlets', ['outlet' => $this->outlet()->id]))->assertNotFound();
        Livewire::test(Orders::class)->assertDontSee($order->reference);
        // Pratonton draf hanya untuk pemilik form
        $this->form()->update(['status' => 'draft']);
        $this->get(route('form.show', ['slug' => 'pak-maon-western', 'preview' => 1]))->assertNotFound();
    }

    public function test_reschedule_checks_capacity_and_cancel_releases_stock(): void
    {
        $this->toast()->update(['stock' => 10]);
        $order = $this->placeOrder($this->bookingPayload());
        $this->assertSame(8, $this->toast()->fresh()->stock);
        $this->actingAs($this->owner);

        Livewire::test(OrderDetail::class, ['order' => $order])
            ->set('newDate', '2026-11-20')->set('newTime', '20:00')->set('newGuests', 6)->call('reschedule')->assertHasNoErrors();
        $this->assertSame('2026-11-20 20:00', $order->fresh()->booking_at->format('Y-m-d H:i'));

        // 24 Nov ialah Selasa: Tangkak tutup
        Livewire::test(OrderDetail::class, ['order' => $order->fresh()])
            ->set('newDate', '2026-11-24')->set('newTime', '20:00')->call('reschedule')->assertHasErrors('newTime');

        Livewire::test(OrderDetail::class, ['order' => $order->fresh()])->call('setStatus', 'booking_status', 'cancelled');
        $this->assertSame(10, $this->toast()->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->booking_status);
    }

    public function test_owner_creates_and_publishes_form_and_settings_apply(): void
    {
        $this->actingAs($this->owner);
        $melaka = (string) $this->outlet('melaka')->id;

        Livewire::test(FormEditor::class)
            ->set('f.title', 'Pickup Raya')->set('f.purpose_booking', false)->set('f.purpose_purchase', true)
            ->set('outletIds', [$melaka])->set('productIds', [(string) $this->toast()->id])
            ->call('save')->assertHasNoErrors()->assertRedirect();
        $form = OrderForm::where('slug', 'pickup-raya')->firstOrFail();
        $this->assertSame($this->owner->id, $form->user_id);

        auth()->logout();
        $this->get(route('form.show', 'pickup-raya'))->assertNotFound(); // draf

        $this->actingAs($this->owner);
        Livewire::test(FormEditor::class, ['form' => $form])->set('f.status', 'published')->call('save')->assertHasNoErrors();
        auth()->logout();

        $this->get(route('form.show', 'pickup-raya'))->assertOk()->assertSee('Double Butter Kaya Toast')->assertDontSee('Set Talam Dua Muka');
        $this->postJson(route('form.store', 'pickup-raya'), $this->purchasePayload(['outlet_id' => $this->outlet()->id]))->assertStatus(422);
        $this->postJson(route('form.store', 'pickup-raya'), $this->bookingPayload(['outlet_id' => (int) $melaka]))->assertStatus(422);
        $this->placeOrder($this->purchasePayload(), 'pickup-raya');
    }

    public function test_form_requires_purpose_action_and_own_outlets(): void
    {
        $this->actingAs($this->owner);
        Livewire::test(FormEditor::class)->set('f.title', 'Kosong')->set('f.purpose_booking', false)
            ->call('save')->assertHasErrors('f.purpose_booking');

        $foreign = DefaultOutletData::provision(User::factory()->create());
        Livewire::test(FormEditor::class)->set('f.title', 'Curi')->set('outletIds', [(string) $foreign->id])
            ->call('save')->assertHasErrors('outletIds.0');
    }

    public function test_outlet_needs_whatsapp_before_accepting_orders(): void
    {
        $this->actingAs($this->owner);
        Livewire::test(OutletSettings::class, ['outlet' => $this->outlet()->id])
            ->set('o.whatsapp', '')->call('save')->assertHasErrors('o.whatsapp');

        Livewire::test(OutletSettings::class, ['outlet' => $this->outlet()->id])
            ->set('o.whatsapp', '019-222 3333')->set('o.slot_capacity', '10')->set('closures', '2026-12-25 Krismas')
            ->call('save')->assertHasNoErrors();
        $outlet = $this->outlet()->fresh();
        $this->assertSame('60192223333', $outlet->whatsapp);
        $this->assertSame(10, $outlet->slot_capacity);
        $this->assertSame(1, $outlet->closures()->count());
    }

    public function test_owner_creates_staff_account_scoped_to_outlet(): void
    {
        $this->actingAs($this->owner);
        Livewire::test(StaffAccounts::class)
            ->set('name', 'Staf Melaka')->set('email', 'melaka@contoh.test')->set('password', 'rahsia-123')
            ->set('outletIds', [(string) $this->outlet('melaka')->id])->call('create')->assertHasNoErrors();

        $staff = User::where('email', 'melaka@contoh.test')->firstOrFail();
        $this->assertTrue($staff->isBranchStaff());
        $this->assertSame([$this->outlet('melaka')->id], $staff->bookingOutletIds());
        $this->assertFalse($staff->canManageShop());
    }
}
