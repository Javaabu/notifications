<?php

namespace Javaabu\Notifications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Javaabu\Notifications\Models\DatabaseNotification;
use Javaabu\Notifications\Routing\NotificationRoutes;

/**
 * A database notification as the bell and the notifications page show it, in the
 * reader's language
 *
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /** The route name prefix the links use; null means the current route's */
    protected ?string $route_prefix = null;

    public function routePrefix(?string $prefix): static
    {
        $this->route_prefix = $prefix;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var DatabaseNotification $notification */
        $notification = $this->resource;
        $prefix = $this->route_prefix ?? NotificationRoutes::namePrefix($request);
        $user = $request->user();

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'type_label' => $notification->typeLabel(),
            'title' => $notification->title(),
            'body' => $notification->body(),
            'action_text' => $notification->actionText(),
            'action_url' => $notification->actionUrl(),
            'icon' => $notification->icon(),
            'color' => $notification->color(),
            'tone' => $notification->tone(),
            'thumbnail' => $notification->thumbnail(),
            // Not `data`: a `data` key would stop JsonResource from wrapping the response
            'extra' => $notification->extraData(),
            'subject_type' => $notification->subject_type,
            'subject_id' => $notification->subject_id,
            'read' => $notification->read(),
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'created_at_human' => $notification->created_at?->diffForHumans(),
            'links' => [
                'show' => route("{$prefix}.show", $notification->id),
                'read' => route("{$prefix}.read", $notification->id),
                'unread' => route("{$prefix}.unread", $notification->id),
                'destroy' => route("{$prefix}.destroy", $notification->id),
            ],
            'can' => [
                'update' => (bool) $user?->can('update', $notification),
                'delete' => (bool) $user?->can('delete', $notification),
            ],
        ];
    }
}
