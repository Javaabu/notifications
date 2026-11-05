<?php

namespace Javaabu\Notifications\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Notifications\Concerns\Notifiable;
use Javaabu\Notifications\Tests\TestSupport\Factories\AdminFactory;
use Javaabu\Permissions\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $guarded = [];

    protected string $guard_name = 'web_admin';

    protected static function newFactory(): AdminFactory
    {
        return new AdminFactory;
    }

    public function getMorphClass(): string
    {
        return 'admin';
    }
}
