@extends('layouts.shop')
@section('title', __('Gateway Simulasi'))
@section('robots', 'noindex,nofollow')
@section('content')
<main class="sf-wrap" style="padding:40px 0">
    <div class="card sf-stack">
        <div class="sf-alert warn" style="margin:0"><b>{{ __('Gateway simulasi (ujian sahaja).') }}</b> {{ __('Tiada wang sebenar.') }} {!! __('Tetapkan :env untuk CHIP Collect.', ['env' => '<code>PAYMENT_DRIVER=chip</code>']) !!}</div>
        <h1 style="font-size:22px;font-weight:800">{{ __('Bayar') }} @sen($payment->amount_cents)</h1>
        <p style="color:var(--muted)">{{ __('Rujukan') }} {{ $payment->order->reference }} · {{ $payment->provider_ref }}</p>
        @if ($payment->status !== 'created')
            <div class="sf-alert info">{{ __('Bayaran ini sudah diproses (:status).', ['status' => $payment->status]) }}</div>
        @endif
        <form method="POST" action="{{ route('payments.fake.complete', $payment->provider_ref) }}" class="sf-stack">
            @csrf
            <button class="btn block" name="result" value="paid">{{ __('Simulasi: Bayaran berjaya') }}</button>
            <button class="btn sec block" name="result" value="failed">{{ __('Simulasi: Bayaran gagal') }}</button>
            <button class="btn sec block" name="result" value="cancelled">{{ __('Simulasi: Batal') }}</button>
        </form>
    </div>
</main>
@endsection
