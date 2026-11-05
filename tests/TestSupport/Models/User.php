<?php

namespace Javaabu\Notifications\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Notifications\Concerns\ManagesNotificationPreferences;
use Javaabu\Notifications\Concerns\Notifiable;
use Javaabu\Notifications\Contracts\HasNotificationPreferences;
use Javaabu\Notifications\Tests\TestSupport\Factories\UserFactory;
use Javaabu\Permissions\Traits\HasRoles;
use Javaabu\SmsNotifications\Notifiable\HasSmsNumber;
use Javaabu\SmsNotifications\Notifiable\SmsNotifiable;

class User extends Authenticatable implements HasNotificationPreferences, SmsNotifiable
{
    use HasFactory;
    use HasRoles;
    use HasSmsNumber;
    use ManagesNotificationPreferences;
    use Notifiable;

    protected string $guard_name = 'web';

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected static function newFactory(): UserFactory
    {
        return new UserFactory;
    }

    public function getMorphClass(): string
    {
        return 'user';
    }

    public function routeNotificationForSms(): string|array|null
    {
        return $this->phone;
    }

    public function routeNotificationForFcm(): ?string
    {
        return $this->fcm_token;
    }
}
