@extends('layouts.shop')
@section('title', 'Gateway Simulasi')
@section('robots', 'noindex,nofollow')
@section('content')
<main class="sf-wrap" style="padding:40px 0">
    <div class="card sf-stack">
        <div class="sf-alert warn" style="margin:0"><b>Gateway simulasi (ujian sahaja).</b> Tiada wang sebenar. Tetapkan <code>PAYMENT_DRIVER=chip</code> untuk CHIP Collect.</div>
        <h1 style="font-size:22px;font-weight:800">Bayar @sen($payment->amount_cents)</h1>
        <p style="color:var(--muted)">Rujukan {{ $payment->order->reference }} · {{ $payment->provider_ref }}</p>
        @if ($payment->status !== 'created')
            <div class="sf-alert info">Bayaran ini sudah diproses ({{ $payment->status }}).</div>
        @endif
        <form method="POST" action="{{ route('payments.fake.complete', $payment->provider_ref) }}" class="sf-stack">
            @csrf
            <button class="btn block" name="result" value="paid">Simulasi: Bayaran berjaya</button>
            <button class="btn sec block" name="result" value="failed">Simulasi: Bayaran gagal</button>
            <button class="btn sec block" name="result" value="cancelled">Simulasi: Batal</button>
        </form>
    </div>
</main>
@endsection
