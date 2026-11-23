<?php

namespace Luttje\FilamentUserAttributes\Tests\Fixtures\Policies;

use Luttje\FilamentUserAttributes\Models\UserAttributeConfig;
use Luttje\FilamentUserAttributes\Tests\Fixtures\Models\User;

/**
 * Only users named 'admin' may manage user attribute configurations.
 */
class UserAttributeConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->name === 'admin';
    }

    public function update(User $user, UserAttributeConfig $config): bool
    {
        return $user->name === 'admin';
    }
}
