<div>
    <x-pagehead :title="__('Akaun Staf Cawangan')" :sub="__('Staf hanya nampak booking & pesanan outlet yang diberikan. Mereka tidak boleh masuk modul kewangan.')" />

    <div class="split">
        <div class="card">
            <h2 class="sec" style="margin-top:0">{{ __('Akses semasa') }}</h2>
            @foreach ($outlets as $b)
                <div style="padding:10px 0;border-bottom:1px solid var(--line)">
                    <b>{{ $b->name }}</b>
                    @forelse ($b->members as $m)
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px" wire:key="m-{{ $b->id }}-{{ $m->id }}">
                            <span>{{ $m->name }} <span class="hint" style="margin:0">· {{ $m->email }} · {{ $m->pivot->role === 'manager' ? __('Manager') : __('Staf') }}</span></span>
                            <span class="del" wire:click="revoke({{ $m->id }}, {{ $b->id }})" wire:confirm="{{ __('Buang akses :name ke :outlet?', ['name' => $m->name, 'outlet' => $b->name]) }}">✕</span>
                        </div>
                    @empty
                        <div class="hint" style="margin:4px 0 0">{{ __('Tiada staf.') }}</div>
                    @endforelse
                </div>
            @endforeach
        </div>
        <div class="card form-card">
            <h2 class="sec" style="margin-top:0">{{ __('Cipta akaun staf') }}</h2>
            <form wire:submit="create" style="display:grid;gap:10px">
                <div><label>{{ __('Nama') }}</label><input wire:model="name">@error('name')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div><label>{{ __('Emel (untuk log masuk)') }}</label><input type="email" wire:model="email">@error('email')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div><label>{{ __('Kata laluan sementara (min 8 aksara)') }}</label><input type="password" wire:model="password" autocomplete="new-password">@error('password')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div><label>{{ __('Peranan') }}</label><select wire:model="role"><option value="staff">{{ __('Staf') }}</option><option value="manager">{{ __('Manager') }}</option></select></div>
                <div>
                    <label>{{ __('Outlet') }}</label>
                    <div style="display:flex;gap:14px;flex-wrap:wrap">@foreach ($outlets as $b)<label class="chk"><input type="checkbox" value="{{ $b->id }}" wire:model="outletIds"> {{ $b->name }}</label>@endforeach</div>
                    @error('outletIds')<div class="auth-error">{{ $message }}</div>@enderror
                </div>
                <div><button class="btn" type="submit">{{ __('Cipta akaun') }}</button></div>
            </form>
        </div>
    </div>
</div>
