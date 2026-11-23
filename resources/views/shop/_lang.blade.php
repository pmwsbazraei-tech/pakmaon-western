{{-- Suis bahasa pelanggan: BM (lalai) / EN. Pilihan disimpan dalam sesi; cart kekal dalam pelayar. --}}
<nav class="sf-lang" aria-label="{{ __('Bahasa') }}">
    @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $short)
        <a href="{{ route('locale.switch', $code) }}" @class(['on' => app()->getLocale() === $code]) hreflang="{{ $code }}"
           @if (app()->getLocale() === $code) aria-current="true" @endif>{{ $short }}</a>
    @endforeach
</nav>
