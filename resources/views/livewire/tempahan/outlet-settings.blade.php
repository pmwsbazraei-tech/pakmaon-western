@php use App\Models\OutletHour; @endphp
<div>
    <x-pagehead :title="__('Tetapan Outlet (Tempahan)')" :sub="__('Setiap outlet ialah cawangan untuk booking meja dan pickup.')" />

    <div class="card" style="margin-bottom:14px">
        <label>{{ __('Outlet') }}</label>
        <select wire:model.live="outlet">@foreach ($outlets as $b)<option value="{{ $b->id }}">{{ $b->name }}{{ $b->accepts_orders ? '' : ' ('.__('belum terima tempahan').')' }}</option>@endforeach</select>
        <div class="hint">{{ __('Tambah atau namakan outlet melalui pemilih outlet di sidebar dan modul Tetapan.') }}</div>
    </div>

    <form wire:submit="save" style="display:grid;gap:14px">
        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Maklumat & status') }}</h2>
            <div class="row">
                <div><label>{{ __('Nombor WhatsApp outlet') }}</label><input wire:model="o.whatsapp" placeholder="012-345 6789">@error('o.whatsapp')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div style="flex:2"><label>{{ __('Alamat / lokasi pickup') }}</label><input wire:model="o.address"></div>
            </div>
            <div style="margin-top:12px"><label>{{ __('Pautan Google Maps') }}</label><input wire:model="o.maps_url">@error('o.maps_url')<div class="auth-error">{{ $message }}</div>@enderror</div>
            <div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:14px">
                <label class="chk"><input type="checkbox" wire:model="o.accepts_orders"> <b>{{ __('Terima tempahan online') }}</b></label>
                <label class="chk"><input type="checkbox" wire:model="o.booking_enabled"> {{ __('Booking meja') }}</label>
                <label class="chk"><input type="checkbox" wire:model="o.pickup_enabled"> {{ __('Pickup') }}</label>
                <label class="chk"><input type="checkbox" wire:model="o.delivery_enabled"> {{ __('Delivery') }}</label>
            </div>
        </div>

        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Slot booking') }}</h2>
            <div class="row">
                <div><label>{{ __('Tempoh slot (minit)') }}</label><select wire:model="o.slot_minutes">@foreach ([15, 30, 45, 60, 90, 120] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div>
                <div><label>{{ __('Kapasiti tetamu / slot') }}</label><input type="number" wire:model="o.slot_capacity"></div>
                <div><label>{{ __('Maks tetamu / booking') }}</label><input type="number" wire:model="o.max_guests_per_booking"></div>
            </div>
            <div class="row" style="margin-top:12px">
                <div><label>{{ __('Notis minimum (minit)') }}</label><input type="number" wire:model="o.lead_minutes"></div>
                <div><label>{{ __('Boleh tempah hingga (hari)') }}</label><input type="number" wire:model="o.advance_days"></div>
                <div><label>{{ __('Caj delivery (RM)') }}</label><input type="number" step="0.01" wire:model="o.delivery_fee"></div>
            </div>
        </div>

        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Waktu operasi') }}</h2>
            <div class="table-wrap">
                <table>
                    <tbody>
                        @foreach (OutletHour::DAYS as $d => $label)
                            <tr wire:key="h-{{ $d }}">
                                <td style="width:90px"><b>{{ __($label) }}</b></td>
                                <td><input type="time" wire:model="hours.{{ $d }}.opens_at"></td>
                                <td style="width:50px;color:var(--muted)">{{ __('hingga') }}</td>
                                <td><input type="time" wire:model="hours.{{ $d }}.closes_at"></td>
                                <td style="width:90px"><label class="chk"><input type="checkbox" wire:model="hours.{{ $d }}.is_closed"> {{ __('Tutup') }}</label></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="hint">{{ __('Hari tanpa waktu dianggap tutup. Slot terakhir = waktu tutup tolak tempoh slot.') }}</div>
        </div>

        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Hari tutup / cuti') }}</h2>
            <label>{{ __('Satu tarikh setiap baris: YYYY-MM-DD sebab') }}</label>
            <textarea wire:model="closures" rows="4" placeholder="{{ __('2026-12-25 Cuti umum') }}"></textarea>
        </div>

        <div><button class="btn" type="submit" wire:loading.attr="disabled">{{ __('Simpan tetapan outlet') }}</button></div>
    </form>
</div>
