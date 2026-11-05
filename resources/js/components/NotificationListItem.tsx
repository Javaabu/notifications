import { Form, Submit } from "@javaabu/forms";
import { Icon, cn } from "@javaabu/onu";
import type { MouseEvent } from "react";
import type { NotificationData, NotificationsStrings, Tone } from "../types";

const TINT: Record<Tone, string> = {
  neutral: "bg-muted text-muted-foreground",
  info: "bg-info/10 text-info",
  success: "bg-success/10 text-success",
  warning: "bg-warning/15 text-warning",
  danger: "bg-destructive/10 text-destructive",
};

export interface NotificationListItemProps {
  notification: NotificationData;
  strings: Pick<NotificationsStrings, "mark_read" | "mark_unread" | "delete" | "confirm_delete" | "unread">;
  /** Show mark read/unread and delete, when the user may use them */
  actions?: boolean;
}

function confirmFirst(message: string) {
  return (event: MouseEvent<HTMLButtonElement>) => {
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  };
}

/** One notification on the notifications page: icon or thumbnail, title, body, time and actions */
export function NotificationListItem({ notification, strings, actions = true }: NotificationListItemProps) {
  const unread = !notification.read;

  return (
    <li
      className={cn("relative flex items-start gap-3 p-4", unread && "bg-primary/5")}
      data-slot="notification"
      data-notification-id={notification.id}
      data-read={notification.read ? "true" : "false"}
    >
      <span
        className={cn(
          "flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-full [&_svg]:size-4",
          TINT[notification.tone] ?? TINT.info,
        )}
        data-slot="notification-icon"
        data-tone={notification.tone}
      >
        {notification.thumbnail ? (
          <img src={notification.thumbnail} alt="" className="size-full object-cover" />
        ) : (
          <Icon name={notification.icon} />
        )}
      </span>

      <div className="flex min-w-0 flex-1 flex-col gap-0.5">
        <a
          href={notification.links.show}
          className={cn("text-sm hover:underline focus-visible:underline", unread && "font-semibold")}
          dir="auto"
          data-slot="notification-title"
        >
          {notification.title}
        </a>
        {notification.body ? (
          <p className="line-clamp-2 text-sm text-muted-foreground" dir="auto" data-slot="notification-body">
            {notification.body}
          </p>
        ) : null}
        {notification.created_at ? (
          <time dateTime={notification.created_at} className="text-xs text-muted-foreground">
            {notification.created_at_human}
          </time>
        ) : null}
        {unread ? <span className="sr-only">{strings.unread}</span> : null}
      </div>

      {actions ? (
        <div className="flex shrink-0 flex-wrap items-center gap-2" data-slot="notification-actions">
          {notification.can.update ? (
            <Form method="POST" action={notification.read ? notification.links.unread : notification.links.read}>
              <Submit color="secondary" size="sm" animate={false}>
                {notification.read ? strings.mark_unread : strings.mark_read}
              </Submit>
            </Form>
          ) : null}

          {notification.can.delete ? (
            <Form method="DELETE" action={notification.links.destroy}>
              <Submit
                color="danger"
                size="sm"
                animate={false}
                aria-label={strings.delete}
                onClick={confirmFirst(strings.confirm_delete)}
              >
                <Icon name="trash" />
                <span className="sr-only">{strings.delete}</span>
              </Submit>
            </Form>
          ) : null}
        </div>
      ) : null}
    </li>
  );
}
