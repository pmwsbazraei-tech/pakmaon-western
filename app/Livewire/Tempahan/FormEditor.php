<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\OrderForm;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Editor penuh landing page: Overview, Options, Products, Purchasing, Pickup/Delivery, Slots, WhatsApp, Payment, Popup, Maintenance. */
#[Layout('layouts.app')]
class FormEditor extends Component
{
    use InteractsWithShop;

    public ?OrderForm $form = null;

    public array $f = [];

    public array $outletIds = [];

    public array $productIds = [];

    public function mount(?OrderForm $form = null): void
    {
        if ($form?->exists) {
            abort_unless($form->user_id === $this->shopOwner()->id, 404);
            $this->form = $form;
        }
        $m = $this->form ?? new OrderForm([
            'status' => 'draft', 'purpose_booking' => true, 'purpose_purchase' => false, 'booking_mode' => 'preorder_required',
            'prices_include_sst' => true, 'sst_rate' => 6, 'allow_pickup' => true, 'allow_delivery' => false,
            'action_whatsapp' => true, 'action_online' => true, 'auto_confirm_on_payment' => false,
        ]);
        $c = fn ($v) => $v !== null ? number_format($v / 100, 2, '.', '') : '';

        $this->f = [
            'title' => $m->title ?? '', 'slug' => $m->slug ?? '', 'status' => $m->status, 'description' => $m->description ?? '',
            'banner_url' => $m->banner_url ?? '', 'notices' => implode("\n", $m->notices ?? []), 'welcome_popup' => $m->welcome_popup ?? '',
            'maintenance_message' => $m->maintenance_message ?? '', 'purpose_booking' => (bool) $m->purpose_booking,
            'purpose_purchase' => (bool) $m->purpose_purchase, 'booking_mode' => $m->booking_mode, 'booking_notice' => $m->booking_notice ?? '',
            'prices_include_sst' => (bool) $m->prices_include_sst, 'sst_rate' => (string) ($m->sst_rate ?? 6),
            'min_order' => $c($m->min_order_cents), 'max_order' => $c($m->max_order_cents), 'allow_pickup' => (bool) $m->allow_pickup,
            'allow_delivery' => (bool) $m->allow_delivery, 'delivery_fee' => $c($m->delivery_fee_cents ?? 0),
            'action_whatsapp' => (bool) $m->action_whatsapp, 'action_online' => (bool) $m->action_online,
            'auto_confirm_on_payment' => (bool) $m->auto_confirm_on_payment,
            'whatsapp_template' => $m->whatsapp_template ?: OrderForm::DEFAULT_WHATSAPP_TEMPLATE,
        ];
        $this->outletIds = $this->form ? $this->form->outlets()->pluck('outlets.id')->map(fn ($i) => (string) $i)->all()
            : $this->visibleOutlets()->pluck('id')->map(fn ($i) => (string) $i)->all();
        $this->productIds = $this->form ? $this->form->products()->pluck('products.id')->map(fn ($i) => (string) $i)->all() : [];
    }

    public function save()
    {
        $owner = $this->shopOwner();
        $this->f['slug'] = Str::slug($this->f['slug'] ?: $this->f['title']);

        $this->validate([
            'f.title' => ['required', 'string', 'max:150'],
            'f.slug' => ['required', 'string', 'max:80', Rule::unique('order_forms', 'slug')->ignore($this->form?->id)],
            'f.status' => ['required', Rule::in(['draft', 'published', 'maintenance'])],
            'f.banner_url' => ['nullable', 'url', 'max:500'],
            'f.booking_mode' => ['required', Rule::in(['preorder_required', 'table_only_allowed'])],
            'f.sst_rate' => ['required', 'numeric', 'min:0', 'max:20'],
            'f.min_order' => ['nullable', 'numeric', 'min:0'],
            'f.max_order' => ['nullable', 'numeric', 'min:0'],
            'f.delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'f.whatsapp_template' => ['nullable', 'string', 'max:3000'],
            'outletIds' => ['required', 'array', 'min:1'],
            'outletIds.*' => [Rule::in($owner->outlets()->pluck('id')->map(fn ($i) => (string) $i)->all())],
            'productIds.*' => [Rule::in(Product::where('user_id', $owner->id)->pluck('id')->map(fn ($i) => (string) $i)->all())],
        ], ['outletIds.required' => 'Pilih sekurang-kurangnya satu outlet.'], ['f.title' => 'tajuk', 'f.slug' => 'slug']);

        if (! $this->f['purpose_booking'] && ! $this->f['purpose_purchase']) {
            return $this->addError('f.purpose_booking', 'Aktifkan sekurang-kurangnya satu tujuan.');
        }
        if (! $this->f['action_whatsapp'] && ! $this->f['action_online']) {
            return $this->addError('f.action_whatsapp', 'Aktifkan sekurang-kurangnya satu tindakan selepas Proceed.');
        }
        if ($this->f['purpose_purchase'] && ! $this->f['allow_pickup'] && ! $this->f['allow_delivery']) {
            return $this->addError('f.allow_pickup', 'Pembelian produk memerlukan pickup atau delivery.');
        }

        $data = collect($this->f)->except(['notices', 'min_order', 'max_order', 'delivery_fee'])->all();
        $data['notices'] = collect(preg_split('/\R/', $this->f['notices']))->map(fn ($l) => trim($l))->filter()->values()->all();
        $data['min_order_cents'] = Money::toCents($this->f['min_order']);
        $data['max_order_cents'] = Money::toCents($this->f['max_order']);
        $data['delivery_fee_cents'] = Money::toCents($this->f['delivery_fee']) ?? 0;
        foreach (['banner_url', 'description', 'welcome_popup', 'maintenance_message', 'booking_notice'] as $k) {
            $data[$k] = $data[$k] !== '' ? $data[$k] : null;
        }
        if ($data['status'] === 'published' && ! $this->form?->published_at) {
            $data['published_at'] = now();
        }

        $form = $this->form ?? new OrderForm(['user_id' => $owner->id]);
        $form->fill($data)->save();
        $form->outlets()->sync(array_map('intval', $this->outletIds));
        $form->products()->sync(collect($this->productIds)->values()->mapWithKeys(fn ($id, $i) => [(int) $id => ['sort' => $i]])->all());

        if (! $this->form) {
            session()->flash('toast', 'Landing page dicipta.');

            return $this->redirectRoute('tempahan.forms.edit', $form, navigate: true);
        }
        $this->form = $form->fresh();
        $this->dispatch('toast', message: 'Tetapan disimpan.');
    }

    public function render()
    {
        $owner = $this->shopOwner();

        return view('livewire.tempahan.form-editor', [
            'outlets' => $owner->outlets()->orderBy('name')->get(),
            'products' => Product::where('user_id', $owner->id)->with('category')->orderBy('product_category_id')->orderBy('sort')->get(),
            'gateway' => config('services.payment_driver') === 'chip' ? 'CHIP Collect' : 'Simulasi (ujian)',
        ]);
    }
}
