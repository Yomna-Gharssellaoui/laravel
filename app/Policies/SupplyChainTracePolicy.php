<?php

namespace App\Policies;

use App\Models\SupplyChainTrace;
use App\Models\User;

class SupplyChainTracePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessBackOffice();
    }

    public function view(User $user, SupplyChainTrace $trace): bool
    {
        return $this->owns($user, $trace);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin()
            || ($user->hasRole('distributor') && $user->distributor !== null)
            || ($user->hasRole('producer') && $user->producer !== null)
            || ($user->hasRole('transformer') && $user->transformer !== null);
    }

    public function update(User $user, SupplyChainTrace $trace): bool
    {
        return $this->owns($user, $trace);
    }

    public function delete(User $user, SupplyChainTrace $trace): bool
    {
        return $this->owns($user, $trace);
    }

    private function owns(User $user, SupplyChainTrace $trace): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->hasRole('distributor')) {
            return $user->distributor !== null && $trace->distributor_id === $user->distributor->id;
        }

        return $trace->product !== null && $trace->product->isOwnedBy($user);
    }
}
