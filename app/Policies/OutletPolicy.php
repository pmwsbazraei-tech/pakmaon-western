<?php

namespace App\Policies;

use App\Models\Outlet;
use App\Models\User;

class OutletPolicy
{
    /** User hanya boleh guna outlet miliknya sendiri. */
    public function view(User $user, Outlet $outlet): bool
    {
        return $user->id === $outlet->user_id;
    }

    public function update(User $user, Outlet $outlet): bool
    {
        return $user->id === $outlet->user_id;
    }

    public function delete(User $user, Outlet $outlet): bool
    {
        return $user->id === $outlet->user_id;
    }
}
