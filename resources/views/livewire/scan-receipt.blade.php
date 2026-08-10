<div>
    <x-pagehead title="Scan Resit" sub="Pilih kategori → snap/upload resit → sistem baca jumlah automatik" />

    <div class="card">
        <label>1. Pilih kategori kos</label>
        <div class="catgrid">
            @foreach ($categories as $id => $cat)
                <div class="catbtn @if ($category === $id) on @endif" wire:click="selectCategory('{{ $id }}')">
                    <div class="ic">{{ $cat['ic'] }}</div>
                    <div class="nm">{{ $cat['nm'] }}</div>
                </div>
            @endforeach
        </div>
        @error('category') <div class="auth-error">{{ $message }}</div> @enderror
    </div>

    <div class="split" style="margin-top:14px"
         x-data="receiptOcr()">
        {{-- Langkah 2: upload / snap --}}
        <div class="card">
            <label>2. Upload / snap gambar resit</label>
            <div class="drop" @click="$refs.file.click()">
                📷 Klik untuk pilih gambar resit<br>
                <span style="font-size:12px">(atau guna kamera telefon)</span>
            </div>
            <input type="file" x-ref="file" wire:model="image" @change="handle($event)"
                   accept="image/*" capture="environment" class="hidden">

            <div class="prog" x-show="progress" x-text="progress"></div>
            <div wire:loading wire:target="image" class="prog">Memuat naik imej…</div>

            <template x-if="preview">
                <img :src="preview" style="width:100%;border-radius:10px;margin-top:10px;max-height:280px;object-fit:contain">
            </template>
        </div>

        {{-- Langkah 3: semak & simpan --}}
        <div class="card">
            <label>3. Semak & simpan</label>
            <div class="row">
                <div><label>Tarikh</label><input type="date" wire:model="date"></div>
                <div><label>Jumlah (RM)</label><input type="number" wire:model="amount" step="0.01" placeholder="0.00"></div>
            </div>
            @error('amount') <div class="auth-error">{{ $message }}</div> @enderror
            @error('date') <div class="auth-error">{{ $message }}</div> @enderror

            <div style="margin-top:10px">
                <label>Item dikesan (boleh edit)</label>
                @if (count($items) === 0)
                    <div class="hint">Belum ada. Scan resit atau tambah manual.</div>
                @else
                    @foreach ($items as $i => $item)
                        <div class="row" style="margin-bottom:6px" wire:key="item-{{ $i }}">
                            <div style="flex:2"><input wire:model.blur="items.{{ $i }}.name" placeholder="Nama item"></div>
                            <div><input type="number" step="0.01" class="miniedit" style="width:100%" wire:model.live="items.{{ $i }}.amount" placeholder="0.00"></div>
                            <div style="flex:0"><button class="btn sec sm" wire:click="removeItem({{ $i }})">✕</button></div>
                        </div>
                    @endforeach
                @endif
                <button class="btn sec sm" wire:click="addItem" style="margin-top:8px">+ Tambah item</button>
            </div>

            <input wire:model="note" placeholder="Nota (opsyenal)" style="margin-top:10px">
            <button class="btn" wire:click="save" style="margin-top:12px;width:100%">💾 Rekod Kos</button>
        </div>
    </div>

    <h2 class="sec">
        Resit bulan {{ $month }}
        <input type="month" class="monthsel" wire:model.live="month" style="margin-left:10px">
    </h2>
    <div class="card">
        @if ($receipts->isEmpty())
            <div class="empty">Belum ada resit untuk bulan ini.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tarikh</th><th>Kategori</th><th>Item</th><th class="num">Jumlah</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($receipts as $r)
                            @php $cat = config('outlet.categories.'.$r->category, ['nm' => $r->category, 'ic' => '📁']); @endphp
                            <tr wire:key="rec-{{ $r->id }}">
                                <td>{{ $r->date->format('Y-m-d') }}</td>
                                <td><span class="chip">{{ $cat['ic'] }} {{ $cat['nm'] }}</span></td>
                                <td style="color:var(--muted);font-size:12px">
                                    @if ($r->items->isNotEmpty())
                                        {{ $r->items->take(3)->pluck('name')->implode(', ') }}{{ $r->items->count() > 3 ? '…' : '' }}
                                    @else
                                        {{ $r->note ?: '—' }}
                                    @endif
                                    @if ($r->image_path)
                                        <a href="{{ Storage::url($r->image_path) }}" target="_blank" title="Lihat imej resit">📷</a>
                                    @endif
                                </td>
                                <td class="num">@rm($r->amount)</td>
                                <td><span class="del" wire:click="deleteReceipt({{ $r->id }})">✕</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
{{-- Komponen Alpine `receiptOcr` didaftar dalam resources/js/app.js (alpine:init) --}}
