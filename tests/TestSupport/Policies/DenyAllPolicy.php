<?php

namespace Javaabu\Notifications\Tests\TestSupport\Policies;

use Illuminate\Database\Eloquent\Model;

class DenyAllPolicy
{
    public function viewAny(Model $user): bool
    {
        return false;
    }
}
