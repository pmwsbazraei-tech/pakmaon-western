<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h2 class="sec" style="margin-top:0">Daftar Akaun</h2>

    <form wire:submit="register">
        <div class="field">
            <label for="name">Nama</label>
            <input wire:model="name" id="name" type="text" name="name" required autofocus autocomplete="name">
            @error('name') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input wire:model="email" id="email" type="email" name="email" required autocomplete="username">
            @error('email') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="password">Kata Laluan</label>
            <input wire:model="password" id="password" type="password" name="password" required autocomplete="new-password">
            @error('password') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Sahkan Kata Laluan</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            @error('password_confirmation') <div class="auth-error">{{ $message }}</div> @enderror
        </div>

        <div class="actions">
            <a class="muted-link" href="{{ route('login') }}" wire:navigate>Sudah ada akaun?</a>
            <button type="submit" class="btn">Daftar</button>
        </div>
    </form>
</div>
