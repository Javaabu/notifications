<?php

namespace Javaabu\Notifications\Tests\TestSupport\Notifications;

use Javaabu\Notifications\BaseNotification;
use Javaabu\Notifications\Messages\NotificationMessage;
use Javaabu\Notifications\Tests\TestSupport\Models\Order;

class OrderShippedNotification extends BaseNotification
{
    protected array $channels = ['database', 'mail', 'sms', 'fcm'];

    protected ?string $icon = 'truck';

    public function __construct(
        public Order $order,
    ) {}

    public static function label(): string
    {
        return __('Order shipped');
    }

    public function message(object $notifiable): NotificationMessage
    {
        return NotificationMessage::make(__('Your order has shipped'))
            ->line(__('Order :number is on its way.', ['number' => $this->order->number]))
            ->action(__('Track order'), url("/orders/{$this->order->id}/track"))
            ->subject($this->order)
            ->data(['order_id' => $this->order->id, 'items' => [1, 2]])
            ->success();
    }
}
