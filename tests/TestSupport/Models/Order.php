<?php

namespace Javaabu\Notifications\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Notifications\Contracts\NotificationSubject;

class Order extends Model implements NotificationSubject
{
    protected $guarded = [];

    public function getMorphClass(): string
    {
        return 'order';
    }

    public function notificationUrl(?object $notifiable = null): ?string
    {
        return url("/orders/{$this->id}");
    }

    public function notificationThumbnail(?object $notifiable = null): ?string
    {
        return url("/images/orders/{$this->id}.png");
    }
}
