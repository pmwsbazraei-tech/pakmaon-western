<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h2 class="sec" style="margin-top:0">Log Masuk</h2>

    @if (session('status'))
        <div class="auth-status">{{ session('status') }}</div>
    @endif

    <form wire:submit="login">
        <div class="field">
            <label for="email">Email</label>
            <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username">
            @error('form.email') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="password">Kata Laluan</label>
            <input wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password">
            @error('form.password') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="checkbox-row">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember">
            <label for="remember" style="margin:0">Ingat saya</label>
        </div>

        <div class="actions">
            @if (Route::has('password.request'))
                <a class="muted-link" href="{{ route('password.request') }}" wire:navigate>Lupa kata laluan?</a>
            @endif
            <button type="submit" class="btn">Log Masuk</button>
        </div>

        <div class="hint" style="margin-top:16px">
            Belum ada akaun? <a class="muted-link" href="{{ route('register') }}" wire:navigate>Daftar di sini</a>
        </div>
    </form>
</div>
