<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessBackOffice();
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->isAdmin()
            || ($certificate->product !== null && (new ProductPolicy)->view($user, $certificate->product));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin()
            || ($user->hasRole('producer') && $user->producer !== null)
            || ($user->hasRole('transformer') && $user->transformer !== null);
    }

    public function update(User $user, Certificate $certificate): bool
    {
        return $certificate->product !== null && $certificate->product->isOwnedBy($user);
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $this->update($user, $certificate);
    }
}
