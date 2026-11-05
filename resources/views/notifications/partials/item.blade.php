{{--
    One notification, in the notifications page and the bell.
    Needs $notification (DatabaseNotification) and $routes (route names); $actions shows the buttons.
--}}
@php
    $user = auth()->user();
    $is_read = $notification->read();
    $tint = [
        'neutral' => 'bg-muted text-muted-foreground',
        'info' => 'bg-info/10 text-info',
        'success' => 'bg-success/10 text-success',
        'warning' => 'bg-warning/15 text-warning',
        'danger' => 'bg-destructive/10 text-destructive',
    ][$notification->tone()];
@endphp
<li @class([
        'relative flex items-start gap-3 p-4',
        'bg-primary/5' => ! $is_read,
    ])
    data-slot="notification"
    data-notification-id="{{ $notification->id }}"
    data-read="{{ $is_read ? 'true' : 'false' }}">
    <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full {{ $tint }}"
          data-slot="notification-icon"
          data-tone="{{ $notification->tone() }}">
        @if($notification->thumbnail())
            <img src="{{ $notification->thumbnail() }}" alt="" class="size-full object-cover">
        @else
            @include('notifications::partials.icon', ['name' => $notification->icon()])
        @endif
    </span>

    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
        <a href="{{ route($routes['show'], $notification->id) }}"
           @class(['text-sm hover:underline focus-visible:underline', 'font-semibold' => ! $is_read])
           dir="auto"
           data-slot="notification-title">{{ $notification->title() }}</a>
        @if($notification->body())
            <p class="line-clamp-2 text-sm text-muted-foreground" dir="auto" data-slot="notification-body">{{ $notification->body() }}</p>
        @endif
        <time datetime="{{ $notification->created_at?->toIso8601String() }}" class="text-xs text-muted-foreground">
            {{ $notification->created_at?->diffForHumans() }}
        </time>
        @unless($is_read)
            <span class="sr-only">{{ __('Unread') }}</span>
        @endunless
    </div>

    @if($actions ?? false)
        <div class="flex shrink-0 flex-wrap items-center gap-2" data-slot="notification-actions">
            @if($user?->can('update', $notification))
                <x-forms::form method="POST" :action="route($is_read ? $routes['unread'] : $routes['read'], $notification->id)">
                    <x-forms::submit color="secondary" size="sm" :animate="false">
                        {{ $is_read ? __('Mark as unread') : __('Mark as read') }}
                    </x-forms::submit>
                </x-forms::form>
            @endif

            @if($user?->can('delete', $notification))
                <x-forms::form method="DELETE" :action="route($routes['destroy'], $notification->id)" data-confirm="{{ __('Delete this notification?') }}">
                    <x-forms::submit color="danger" size="sm" :animate="false" start-icon="trash-2" :aria-label="__('Delete')">
                        <span class="sr-only">{{ __('Delete') }}</span>
                    </x-forms::submit>
                </x-forms::form>
            @endif
        </div>
    @endif
</li>
