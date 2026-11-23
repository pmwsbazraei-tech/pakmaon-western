<?php

namespace App\Livewire\Tempahan;

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Akaun staf cawangan untuk tempahan: hanya nampak booking/pesanan outlet yang diberikan.
 * Mereka tidak boleh masuk modul kewangan (Dashboard, Sales, PnL, dll).
 */
#[Layout('layouts.app')]
class StaffAccounts extends Component
{
    use InteractsWithShop;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'staff';

    public array $outletIds = [];

    private function ownerOutletIds(): array
    {
        return $this->shopOwner()->outlets()->pluck('id')->all();
    }

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in(['manager', 'staff'])],
            'outletIds' => ['required', 'array', 'min:1'],
            'outletIds.*' => [Rule::in(array_map('strval', $this->ownerOutletIds()))],
        ], ['outletIds.required' => __('Pilih sekurang-kurangnya satu outlet.')]);

        $user = User::create(['name' => $this->name, 'email' => $this->email, 'password' => Hash::make($this->password)]);
        $user->assignedOutlets()->sync(collect($this->outletIds)->mapWithKeys(fn ($id) => [(int) $id => ['role' => $this->role]])->all());

        $this->reset('name', 'email', 'password', 'outletIds');
        $this->dispatch('toast', message: __('Akaun staf dicipta. Kongsi emel & kata laluan secara peribadi.'));
    }

    public function revoke(int $userId, int $outletId): void
    {
        abort_unless(in_array($outletId, $this->ownerOutletIds(), true), 403);
        User::findOrFail($userId)->assignedOutlets()->detach($outletId);
        $this->dispatch('toast', message: __('Akses dibuang.'));
    }

    public function render()
    {
        return view('livewire.tempahan.staff-accounts', [
            'outlets' => $this->shopOwner()->outlets()->with('members')->orderBy('id')->get(),
        ]);
    }
}
