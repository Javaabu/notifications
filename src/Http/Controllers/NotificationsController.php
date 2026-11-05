<?php

namespace Javaabu\Notifications\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\Notifications\Http\Resources\NotificationResource;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Notifications;
use Javaabu\Notifications\Routing\NotificationRoutes;
use Javaabu\Notifications\Support\Broadcasting;
use Javaabu\Notifications\Views\ViewRenderer;

/**
 * List, open, mark and delete database notifications, and feed the bell
 *
 * Every action answers JSON when the request expects it, and a Blade or Inertia page or a
 * redirect otherwise. Override it with `config('notifications.controllers.notifications')`,
 * the `controller` option of `Route::notifications()`, or by binding a subclass in the container.
 */
class NotificationsController extends Controller
{
    public const string STATUS_UNREAD = 'unread';

    public const string STATUS_READ = 'read';

    public function __construct(
        protected ViewRenderer $views,
    ) {}

    public function index(Request $request): View|Responsable|JsonResponse
    {
        $this->authorizeAbility($request, 'viewAny', Notifications::notificationModel());

        $notifiable = $this->notifiable($request);
        $per_page = min(max($request->integer('per_page', config('notifications.per_page', 20)), 1), 100);
        $status = in_array($request->query('status'), [self::STATUS_UNREAD, self::STATUS_READ], true)
            ? $request->query('status')
            : null;
        $type = $request->string('type')->toString() ?: null;

        $query = $this->query($request, $notifiable);

        if ($status === self::STATUS_UNREAD) {
            $query->whereNull('read_at');
        }

        if ($status === self::STATUS_READ) {
            $query->whereNotNull('read_at');
        }

        if ($type) {
            $query->ofType($type);
        }

        $notifications = $query->paginate($per_page)->withQueryString();

        if ($request->expectsJson()) {
            return NotificationResource::collection($notifications)
                ->additional(['meta' => $this->meta($request, $notifiable)])
                ->response();
        }

        return $this->views->render('notifications.index', [
            'notifications' => $this->views->isInertia()
                ? NotificationResource::collection($notifications)
                : $notifications,
            'notifiable' => $notifiable,
            'is_own' => $this->isOwn($request, $notifiable),
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
            'statuses' => [
                ['value' => self::STATUS_UNREAD, 'label' => __('Unread')],
                ['value' => self::STATUS_READ, 'label' => __('Read')],
            ],
            'types' => $this->types($request, $notifiable),
            ...$this->meta($request, $notifiable),
            'strings' => $this->views->isInertia() ? $this->strings() : null,
            'title' => __('Notifications'),
        ]);
    }

    /**
     * The bell: the unread count and the latest notifications
     */
    public function latest(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'viewAny', Notifications::notificationModel());

        $notifiable = $this->notifiable($request);
        $limit = min(max($request->integer('limit', config('notifications.latest_limit', 10)), 1), 50);

        $notifications = $this->query($request, $notifiable)
            ->limit($limit)
            ->get();

        return NotificationResource::collection($notifications)
            ->additional([
                'meta' => [
                    'unread_count' => $this->query($request, $notifiable)->whereNull('read_at')->count(),
                    ...$this->meta($request, $notifiable),
                ],
            ])
            ->response();
    }

    /**
     * Opening a notification: marks it read and goes where it links to
     */
    public function show(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $notification = $this->findNotification($request, $notification);

        $this->authorizeAbility($request, 'view', $notification);

        if ($request->user()->can('update', $notification)) {
            $notification->markAsRead();
        }

        if ($request->expectsJson()) {
            return (new NotificationResource($notification))->response();
        }

        return redirect()->to($this->redirectUrl($request, $notification));
    }

    public function read(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $notification = $this->findNotification($request, $notification);

        $this->authorizeAbility($request, 'update', $notification);

        $notification->markAsRead();

        return $this->updated($request, $notification, __('Marked as read'));
    }

    public function unread(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $notification = $this->findNotification($request, $notification);

        $this->authorizeAbility($request, 'update', $notification);

        $notification->markAsUnread();

        return $this->updated($request, $notification, __('Marked as unread'));
    }

    /**
     * Marks every unread notification of the current user read
     */
    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAbility($request, 'viewAny', Notifications::notificationModel());

        $count = $this->query($request, $request->user())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['marked' => $count, 'unread_count' => 0]);
        }

        return back()->with('success', __('All notifications marked as read'));
    }

    public function destroy(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $notification = $this->findNotification($request, $notification);

        $this->authorizeAbility($request, 'delete', $notification);

        $notification->delete();

        if ($request->expectsJson()) {
            return response()->json(null, 204);
        }

        return back()->with('success', __('Notification deleted'));
    }

    /**
     * The notifiable whose notifications are listed: the user, or (for users who may view
     * others' notifications) the one in `notifiable_type` and `notifiable_id`
     */
    protected function notifiable(Request $request): Model
    {
        $user = $request->user();
        $type = $request->query('notifiable_type');
        $id = $request->query('notifiable_id');

        if (! is_string($type) || ! is_scalar($id)) {
            return $user;
        }

        if ($type === $user->getMorphClass() && (string) $id === (string) $user->getKey()) {
            return $user;
        }

        $this->authorizeAbility($request, 'viewOthers', Notifications::notificationModel());

        $class = Relation::getMorphedModel($type) ?? $type;

        abort_unless(is_a($class, Model::class, true), 404);

        return $class::query()->findOrFail($id);
    }

    protected function isOwn(Request $request, Model $notifiable): bool
    {
        return $notifiable->is($request->user());
    }

    /**
     * @return Builder<DatabaseNotification>
     */
    protected function query(Request $request, Model $notifiable): Builder
    {
        return Notifications::notificationModel()::query()
            ->forNotifiable($notifiable)
            ->latest();
    }

    protected function findNotification(Request $request, string $id): DatabaseNotification
    {
        return Notifications::notificationModel()::query()->findOrFail($id);
    }

    /**
     * Where opening a notification goes: its URL, or the notifications page
     */
    protected function redirectUrl(Request $request, DatabaseNotification $notification): string
    {
        return $notification->actionUrl() ?: route($this->routeName($request, 'index'));
    }

    protected function updated(Request $request, DatabaseNotification $notification, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return (new NotificationResource($notification))
                ->additional(['meta' => [
                    'unread_count' => $this->query($request, $request->user())->whereNull('read_at')->count(),
                ]])
                ->response();
        }

        return back()->with('success', $message);
    }

    /**
     * The types of notifications the notifiable has, for the filter
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function types(Request $request, Model $notifiable): array
    {
        return $this->query($request, $notifiable)
            ->reorder()
            ->distinct()
            ->pluck('type')
            ->map(fn (string $type) => ['value' => $type, 'label' => DatabaseNotification::labelForType($type)])
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * What a page needs to stay up to date: broadcasting or polling, the channel and the URLs
     *
     * @return array<string, mixed>
     */
    protected function meta(Request $request, Model $notifiable): array
    {
        return [
            'broadcasting' => Broadcasting::enabled(),
            'channel' => Broadcasting::enabled() ? Broadcasting::channelFor($notifiable) : null,
            'poll_interval' => Broadcasting::pollInterval(),
            'routes' => NotificationRoutes::names(NotificationRoutes::namePrefix($request)),
            'urls' => [
                'index' => route($this->routeName($request, 'index')),
                'latest' => route($this->routeName($request, 'latest')),
                'read_all' => route($this->routeName($request, 'read-all')),
            ],
        ];
    }

    /**
     * The translated text of the pages, for the Inertia pages (Blade views use __() directly)
     *
     * @return array<string, string>
     */
    protected function strings(): array
    {
        return [
            'title' => __('Notifications'),
            'mark_all_read' => __('Mark all as read'),
            'mark_read' => __('Mark as read'),
            'mark_unread' => __('Mark as unread'),
            'delete' => __('Delete'),
            'confirm_delete' => __('Delete this notification?'),
            'status' => __('Status'),
            'all_statuses' => __('All notifications'),
            'type' => __('Type'),
            'all_types' => __('All types'),
            'filter' => __('Filter'),
            'unread' => __('Unread'),
            'no_notifications' => __('No notifications'),
            'no_notifications_message' => __('You\'re all caught up.'),
            'view_all' => __('View all notifications'),
        ];
    }

    protected function authorizeAbility(Request $request, string $ability, mixed $arguments): void
    {
        $user = $request->user();

        abort_unless($user && $user->can($ability, $arguments), 403);
    }

    protected function routeName(Request $request, string $action): string
    {
        return NotificationRoutes::namePrefix($request) . ".{$action}";
    }
}
