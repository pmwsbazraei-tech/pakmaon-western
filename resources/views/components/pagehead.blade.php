@props(['title', 'sub' => '', 'withMonth' => false])

<div class="pagehead">
    <div>
        <h1>{{ $title }}</h1>
        @if ($sub)
            <p>{{ $sub }}</p>
        @endif
    </div>
    @if ($withMonth)
        <div>
            <label>Bulan laporan</label>
            <input type="month" class="monthsel" wire:model.live="month">
        </div>
    @endif
</div>
