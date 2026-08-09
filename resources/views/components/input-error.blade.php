@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'auth-error']) }} style="list-style:none;padding:0">
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
