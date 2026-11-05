import { Alert, Form, NoItems, Pagination, Select, Submit } from "@javaabu/forms";
import { Icon } from "@javaabu/onu";
import { router } from "@inertiajs/react";
import { useEffect } from "react";
import { NotificationListItem } from "../../components/NotificationListItem";
import { subscribe } from "../../support/echo";
import type { NotificationsIndexProps } from "../../types";

/** notifications::Notifications/Index — the notifications, filters, mark read and delete, live */
export default function Index(props: NotificationsIndexProps) {
  const { strings, urls, is_own: isOwn, filters } = props;
  const notifications = props.notifications.data;

  useEffect(
    () =>
      subscribe({
        broadcasting: props.broadcasting,
        channel: props.channel,
        pollInterval: props.poll_interval,
        onChange: () => router.reload({ only: ["notifications"] }),
      }),
    [props.broadcasting, props.channel, props.poll_interval],
  );

  return (
    <div className="flex flex-col gap-6" data-notifications-page>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">{props.title}</h1>

        {isOwn && notifications.length > 0 ? (
          <Form method="POST" action={urls.read_all}>
            <Submit color="secondary" size="sm" animate={false}>
              <Icon name="check" />
              {strings.mark_all_read}
            </Submit>
          </Form>
        ) : null}
      </div>

      {props.flash?.success ? <Alert type="success">{props.flash.success}</Alert> : null}

      <Form method="GET" action={urls.index} model={filters} className="flex flex-wrap items-end gap-4">
        <Select
          name="status"
          label={strings.status}
          placeholder={strings.all_statuses}
          showPlaceholder
          options={Object.fromEntries(props.statuses.map((option) => [option.value, option.label]))}
        />
        {props.types.length > 1 ? (
          <Select
            name="type"
            label={strings.type}
            placeholder={strings.all_types}
            showPlaceholder
            options={Object.fromEntries(props.types.map((option) => [option.value, option.label]))}
          />
        ) : null}
        <Submit color="secondary" animate={false}>
          {strings.filter}
        </Submit>
      </Form>

      {notifications.length === 0 ? (
        <NoItems
          icon={<Icon name="success" />}
          showCreate={false}
          title={strings.no_notifications}
          message={strings.no_notifications_message}
        />
      ) : (
        <ul className="divide-y divide-border overflow-hidden rounded-lg border bg-card" data-slot="notifications-list">
          {notifications.map((notification) => (
            <NotificationListItem key={notification.id} notification={notification} strings={strings} />
          ))}
        </ul>
      )}

      {props.notifications.meta.last_page > 1 ? <Pagination paginator={props.notifications} /> : null}
    </div>
  );
}
