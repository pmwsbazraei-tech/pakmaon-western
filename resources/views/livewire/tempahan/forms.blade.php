<div>
    <div class="pagehead">
        <div><h1>{{ __('Landing Page') }}</h1><p>{{ __('Bina, publish dan salin URL untuk dikongsi kepada pelanggan.') }}</p></div>
        <a class="btn" href="{{ route('tempahan.forms.create') }}" wire:navigate>+ {{ __('Landing page baharu') }}</a>
    </div>

    @if ($readyOutlets === 0)
        <div class="card warn-card" style="margin-bottom:14px">
            <b>{{ __('Belum ada outlet yang menerima tempahan.') }}</b>
            <div class="hint">{!! __('Buka :link, isi nombor WhatsApp dan aktifkan "Terima tempahan online".', ['link' => '<a href="'.e(route('tempahan.outlets')).'" wire:navigate style="text-decoration:underline">'.e(__('Tetapan Outlet')).'</a>']) !!}</div>
        </div>
    @endif

    <div style="display:grid;gap:12px">
        @forelse ($forms as $f)
            <div class="card" wire:key="f-{{ $f->id }}" x-data="{ copied: false }">
                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                    <div>
                        <b style="font-size:16px">{{ $f->title }}</b>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">
                            <span class="badge {{ ['published' => 'ok', 'draft' => '', 'maintenance' => 'warn'][$f->status] }}">{{ __(['published' => 'Published', 'draft' => 'Draf', 'maintenance' => 'Maintenance'][$f->status]) }}</span>
                            @if ($f->purpose_booking)<span class="chip">{{ __('Booking meja') }}</span>@endif
                            @if ($f->purpose_purchase)<span class="chip">{{ __('Pembelian produk') }}</span>@endif
                            <span class="hint" style="margin:0">{{ __(':count rekod', ['count' => $f->orders_count]) }}</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px">
                        <a class="btn sec" href="{{ route('form.show', ['slug' => $f->slug, 'preview' => 1]) }}" target="_blank">{{ __('Preview') }}</a>
                        <a class="btn sec" href="{{ route('tempahan.forms.create', ['salin' => $f->id]) }}" wire:navigate title="{{ __('Salin semua tetapan ke landing page baharu') }}">{{ __('Salin') }}</a>
                        <a class="btn" href="{{ route('tempahan.forms.edit', $f) }}" wire:navigate>{{ __('Edit') }}</a>
                    </div>
                </div>
                <div class="copybox" style="margin-top:12px">
                    <input readonly value="{{ $f->publicUrl() }}" @focus="$el.select()">
                    <button type="button" class="btn sec" style="white-space:nowrap" @click="navigator.clipboard.writeText('{{ $f->publicUrl() }}').then(() => { copied = true; setTimeout(() => copied = false, 1500) })" x-text="copied ? @js(__('Disalin ✓')) : @js(__('Salin URL'))"></button>
                </div>
                @if ($f->status !== 'published')<div class="hint">{{ __('Pelanggan belum boleh buka page ini sehingga status Published.') }}</div>@endif
            </div>
        @empty
            <div class="card empty">{{ __('Belum ada landing page.') }}</div>
        @endforelse
    </div>
</div>
