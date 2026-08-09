<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn', 'style' => 'background:var(--red);color:#fff']) }}>
    {{ $slot }}
</button>
