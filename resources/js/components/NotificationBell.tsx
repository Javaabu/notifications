import { NotificationCenter, type NotificationCenterProps, type NotificationItem } from "@javaabu/onu";
import { useNotifications } from "../hooks/useNotifications";
import type { BellData, NotificationData } from "../types";

export interface NotificationBellProps
  extends Omit<NotificationCenterProps, "notifications" | "onMarkRead" | "onMarkAllRead" | "viewAllHref"> {
  /** BellData::make() from the server, usually shared with every Inertia page */
  bell: BellData;
  /** Override where "View all" goes; the notifications page by default */
  viewAllHref?: string;
}

/** A notification as Onu's NotificationCenter shows it */
export function toNotificationItem(notification: NotificationData): NotificationItem {
  return {
    id: notification.id,
    title: notification.title,
    description: notification.body ?? undefined,
    time: notification.created_at ?? undefined,
    read: notification.read,
    href: notification.links.show,
    icon: notification.thumbnail ? (
      <img src={notification.thumbnail} alt="" className="size-full rounded-full object-cover" />
    ) : (
      notification.icon
    ),
    tone: notification.tone,
  };
}

/**
 * The bell with the unread count and the latest notifications (Onu's NotificationCenter),
 * kept up to date over Laravel Echo or by polling. Opening a notification goes through its
 * show route, which marks it read and redirects.
 *
 * ```tsx
 * <NotificationBell bell={props.notifications} />
 * ```
 */
export function NotificationBell({ bell, viewAllHref, onOpenChange, ...props }: NotificationBellProps) {
  const { notifications, refresh, markRead, markAllRead } = useNotifications({
    latestUrl: bell.urls.latest,
    readAllUrl: bell.urls.read_all,
    initial: bell.notifications,
    initialUnreadCount: bell.unread_count,
    broadcasting: bell.broadcasting,
    channel: bell.channel,
    pollInterval: bell.poll_interval,
  });

  return (
    <NotificationCenter
      notifications={notifications.map(toNotificationItem)}
      onMarkRead={(id) => void markRead(id)}
      onMarkAllRead={() => void markAllRead()}
      viewAllHref={viewAllHref ?? bell.urls.index}
      onOpenChange={(open) => {
        if (open) {
          void refresh();
        }

        onOpenChange?.(open);
      }}
      data-notifications-bell
      {...props}
    />
  );
}
