<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\OrderForm;
use App\Models\Outlet;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Services\SlotService;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FormController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $form = $this->resolve($request, $slug);

        if ($form->status === 'maintenance' && ! $this->isPreview($request, $form)) {
            return response()->view('shop.maintenance', ['form' => $form], 503);
        }

        $form->load(['outlets' => fn ($q) => $q->where('accepts_orders', true), 'products' => fn ($q) => $q->where('is_hidden', false)->with('activeVariants.variant.group', 'category')]);

        // Susunan ikut Produk & Stok: kategori (sort), kemudian produk (sort); tanpa kategori di hujung
        $catalog = $form->products
            ->sortBy(fn ($p) => sprintf('%09d-%09d-%09d-%09d',
                $p->category ? $p->category->sort : 999999999, $p->product_category_id ?? 999999999, $p->sort, $p->id))
            ->groupBy(fn ($p) => $p->category?->name ?? 'Lain-lain')
            ->map(fn ($items) => $items->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'image' => $p->image_url,
                'has_variants' => $p->has_variants,
                'price' => $p->has_variants ? null : $p->effectivePrice(),
                'regular_price' => $p->has_variants ? null : $p->price_cents,
                'from_price' => $p->has_variants ? $p->activeVariants->min(fn ($v) => $v->effectivePrice()) : null,
                'sold_out' => $p->isSoldOut(),
                'variants' => $p->activeVariants->map(fn ($v) => [
                    'id' => $v->id, 'name' => $v->name, 'group' => $v->variant?->group?->name, 'price' => $v->effectivePrice(), 'regular_price' => $v->price_cents,
                    'sold_out' => $v->stock !== null && $v->stock <= 0,
                ])->values(),
            ])->values());

        return view('shop.form', [
            'form' => $form,
            'catalog' => $catalog,
            'outlets' => $form->outlets->map(fn (Outlet $b) => [
                'id' => $b->id, 'name' => $b->name, 'address' => $b->address,
                'booking' => $b->booking_enabled, 'pickup' => $b->pickup_enabled, 'delivery' => $b->delivery_enabled,
                'max_guests' => $b->max_guests_per_booking, 'advance_days' => $b->advance_days,
            ])->values(),
            'preview' => $this->isPreview($request, $form),
        ]);
    }

    public function quote(Request $request, string $slug, PricingService $pricing): JsonResponse
    {
        $form = $this->resolve($request, $slug);
        $data = $request->validate([
            'items' => ['array'],
            'outlet_id' => ['nullable', 'integer'],
            'fulfillment' => ['nullable', 'string'],
        ]);
        $outlet = $this->outletFor($form, $data['outlet_id'] ?? null);
        $q = $pricing->quote($form, $data['items'] ?? [], $outlet, $data['fulfillment'] ?? 'dine_in');

        return response()->json($this->presentQuote($q, $form));
    }

    public function slots(Request $request, string $slug, SlotService $slots): JsonResponse
    {
        $form = $this->resolve($request, $slug);
        $data = $request->validate(['outlet_id' => ['required', 'integer'], 'date' => ['required', 'date_format:Y-m-d']]);
        $outlet = $this->outletFor($form, $data['outlet_id']);
        abort_unless($outlet, 404);

        return response()->json($slots->slotsFor($outlet, $data['date']));
    }

    public function store(Request $request, string $slug, OrderService $orders): JsonResponse
    {
        $form = $this->resolve($request, $slug);
        abort_if($form->status === 'maintenance' && ! $this->isPreview($request, $form), 503);

        $request->merge(['phone_normalized' => Phone::normalize($request->input('phone'))]);
        $purposes = array_keys(array_filter(['booking' => $form->purpose_booking, 'purchase' => $form->purpose_purchase]));
        $fulfillments = array_keys(array_filter(['pickup' => $form->allow_pickup, 'delivery' => $form->allow_delivery]));
        if ($form->simple_form) {
            // Borang ringkas: tiada tarikh/masa/cara terima; sentiasa pesanan pickup
            $request->merge(['type' => 'purchase', 'fulfillment' => 'pickup', 'notes' => null]);
            $purposes = ['purchase'];
            $fulfillments = ['pickup'];
        }

        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'min:16', 'max:64'],
            'type' => ['required', Rule::in($purposes)],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string'],
            'phone_normalized' => ['required'],
            'email' => ['nullable', 'email', 'max:150'],
            'order_no' => ['required', 'string', 'max:50'],
            'outlet_id' => ['required', 'integer', Rule::in($form->outlets()->where('accepts_orders', true)->pluck('outlets.id'))],
            'booking_date' => ['exclude_unless:type,booking', 'required', 'date_format:Y-m-d'],
            'booking_time' => ['exclude_unless:type,booking', 'required', 'date_format:H:i'],
            'guests' => ['exclude_unless:type,booking', 'required', 'integer', 'min:1', 'max:200'],
            'fulfillment' => ['exclude_if:type,booking', 'required', Rule::in($fulfillments)],
            'delivery_address' => ['exclude_unless:fulfillment,delivery', 'required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['array', 'max:50'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'phone_normalized.required' => 'Nombor telefon tidak sah. Contoh: 012-345 6789.',
            'order_no.required' => 'Sila isi nombor order.',
            'outlet_id.required' => 'Sila pilih cawangan.',
            'outlet_id.in' => 'Cawangan tidak sah untuk page ini.',
        ]);

        $order = $orders->create($form, [
            'type' => $data['type'],
            'outlet_id' => (int) $data['outlet_id'],
            'name' => trim($data['name']),
            'phone' => $data['phone_normalized'],
            'email' => $data['email'] ?? null,
            'order_no' => trim($data['order_no']),
            'booking_date' => $data['booking_date'] ?? null,
            'booking_time' => $data['booking_time'] ?? null,
            'guests' => $data['guests'] ?? null,
            'fulfillment' => $data['type'] === 'booking' ? 'dine_in' : $data['fulfillment'],
            'delivery_address' => $data['delivery_address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'items' => $data['items'] ?? [],
        ], $data['idempotency_key']);

        return response()->json([
            'reference' => $order->reference,
            'redirect' => $order->publicUrl(),
        ]);
    }

    private function resolve(Request $request, string $slug): OrderForm
    {
        $form = OrderForm::where('slug', $slug)->firstOrFail();
        abort_if($form->status === 'draft' && ! $this->isPreview($request, $form), 404);

        return $form;
    }

    /** Preview draf/maintenance hanya untuk pemilik page. */
    private function isPreview(Request $request, OrderForm $form): bool
    {
        return $request->boolean('preview') && $request->user()?->id === $form->user_id;
    }

    private function outletFor(OrderForm $form, $id): ?Outlet
    {
        return $id ? $form->outlets()->where('outlets.id', $id)->where('accepts_orders', true)->first() : null;
    }

    private function presentQuote(array $q, OrderForm $form): array
    {
        return [
            'lines' => collect($q['lines'])->map(fn ($l) => [
                'product_id' => $l['product_id'], 'variant_id' => $l['variant_id'], 'quantity' => $l['quantity'],
                'name' => $l['product_name'], 'variant' => $l['variant_name'],
                'unit' => Money::format($l['unit_price_cents']), 'total' => Money::format($l['line_total_cents']),
            ]),
            'subtotal' => Money::format($q['subtotal_cents']),
            'discount' => $q['discount_cents'] ? Money::format($q['discount_cents']) : null,
            'delivery' => $q['delivery_fee_cents'] ? Money::format($q['delivery_fee_cents']) : null,
            'sst_added' => $q['sst_added_cents'] ? Money::format($q['sst_added_cents']) : null,
            'sst_note' => $form->prices_include_sst ? 'Harga sudah termasuk SST '.rtrim(rtrim((string) $form->sst_rate, '0'), '.').'%' : null,
            'rounding' => $q['rounding_cents'] ? Money::format($q['rounding_cents']) : null,
            'total' => Money::format($q['total_cents']),
            'total_cents' => $q['total_cents'],
            'errors' => $q['errors'],
            'unavailable' => $q['unavailable'],
        ];
    }
}
