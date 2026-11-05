<?php

namespace Javaabu\Notifications\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Notifications;
use Javaabu\Notifications\Routing\NotificationRoutes;
use Javaabu\Notifications\Support\Broadcasting;

/**
 * `<x-notifications::bell />`: the bell with the unread count and a dropdown of the latest
 * notifications, kept up to date over Reverb or by polling
 *
 * ```blade
 * <x-notifications::bell />
 * <x-notifications::bell guard="web_admin" routes="admin.notifications" :limit="5" />
 * ```
 */
class Bell extends Component
{
    public ?Model $notifiable;

    /** @var Collection<int, DatabaseNotification> */
    public Collection $notifications;

    public int $unread_count = 0;

    /** @var array<string, string> */
    public array $route_names;

    public function __construct(
        public ?string $guard = null,
        public string $routes = 'notifications',
        public ?int $limit = null,
        ?Model $notifiable = null,
    ) {
        $this->notifiable = $notifiable ?? auth()->guard($guard)->user();
        $this->limit ??= (int) config('notifications.latest_limit', 10);
        $this->route_names = NotificationRoutes::names($routes);
        $this->notifications = new Collection;

        if (! $this->notifiable) {
            return;
        }

        $query = Notifications::notificationModel()::query()->forNotifiable($this->notifiable);

        $this->notifications = (clone $query)->latest()->limit($this->limit)->get();
        $this->unread_count = (clone $query)->whereNull('read_at')->count();
    }

    public function shouldRender(): bool
    {
        return $this->notifiable !== null;
    }

    public function broadcasting(): bool
    {
        return Broadcasting::enabled();
    }

    public function channel(): ?string
    {
        return $this->broadcasting() ? Broadcasting::channelFor($this->notifiable) : null;
    }

    public function pollInterval(): int
    {
        return Broadcasting::pollInterval();
    }

    public function render(): View
    {
        return view('notifications::components.bell');
    }
}
