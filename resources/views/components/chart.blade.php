@props(['config', 'height' => 260])

{{-- wire:key ikut kandungan data → Livewire ganti kanvas bila data berubah (bulan/DB) --}}
<div wire:key="chart-{{ md5(json_encode($config)) }}" style="height:{{ $height }}px">
    <canvas x-data x-init="new window.Chart($el, @js($config))"></canvas>
</div>
