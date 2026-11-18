<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function outlets(): HasMany
    {
        return $this->hasMany(Outlet::class);
    }

    /** Outlet aktif user ini — ikut pilihan dalam session, jika tidak outlet pertama. */
    public function currentOutlet(): ?Outlet
    {
        $id = session('active_outlet_id');

        if ($id) {
            $outlet = $this->outlets()->whereKey($id)->first();
            if ($outlet) {
                return $outlet;
            }
        }

        return $this->outlets()->orderBy('id')->first();
    }

    // ---- Tempahan online: akses pemilik vs staf cawangan ----

    /** Outlet yang staf ini diberi akses (pivot outlet_user). */
    public function assignedOutlets(): BelongsToMany
    {
        return $this->belongsToMany(Outlet::class)->withPivot('role');
    }

    /**
     * Akaun staf cawangan: tiada outlet sendiri, hanya diberi akses tempahan.
     * Staf tidak boleh masuk modul kewangan dan tidak diberi outlet DEFAULT.
     */
    public function isBranchStaff(): bool
    {
        return ! $this->outlets()->exists() && $this->assignedOutlets()->exists();
    }

    /** Pemilik perniagaan yang data tempahannya dilihat user ini. */
    public function shopOwner(): ?User
    {
        if (! $this->isBranchStaff()) {
            return $this;
        }

        return $this->assignedOutlets()->with('user')->first()?->user;
    }

    /** @return array<int> Outlet yang tempahannya boleh dilihat. Dikuatkuasakan di server. */
    public function bookingOutletIds(): array
    {
        return $this->isBranchStaff()
            ? $this->assignedOutlets()->pluck('outlets.id')->all()
            : $this->outlets()->pluck('id')->all();
    }

    public function canAccessOutlet(?int $outletId): bool
    {
        return $outletId !== null && in_array($outletId, $this->bookingOutletIds(), true);
    }

    /** Hanya pemilik boleh urus landing page, produk, tetapan outlet dan akaun staf. */
    public function canManageShop(): bool
    {
        return ! $this->isBranchStaff();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
