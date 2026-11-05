import type { BellData, LatestResponse, NotificationData, NotificationsStrings } from "../../resources/js/types";

export const strings: NotificationsStrings = {
  title: "Notifications",
  mark_all_read: "Mark all as read",
  mark_read: "Mark as read",
  mark_unread: "Mark as unread",
  delete: "Delete",
  confirm_delete: "Delete this notification?",
  status: "Status",
  all_statuses: "All notifications",
  type: "Type",
  all_types: "All types",
  filter: "Filter",
  unread: "Unread",
  no_notifications: "No notifications",
  no_notifications_message: "You're all caught up.",
  view_all: "View all notifications",
};

export function makeNotification(overrides: Partial<NotificationData> = {}): NotificationData {
  const id = overrides.id ?? "n-1";

  return {
    id,
    type: "App\\Notifications\\OrderShippedNotification",
    type_label: "Order shipped",
    title: "ތިބާގެ އޯޑަރު ފޮނުވައިފި",
    body: "Order ORD-1 is on its way.",
    action_text: "Track order",
    action_url: "/orders/1/track",
    icon: "bell",
    color: "success",
    tone: "success",
    thumbnail: null,
    extra: {},
    subject_type: "order",
    subject_id: 1,
    read: false,
    read_at: null,
    created_at: "2026-11-05T10:00:00+00:00",
    created_at_human: "1 hour ago",
    links: {
      show: `/notifications/${id}`,
      read: `/notifications/${id}/read`,
      unread: `/notifications/${id}/unread`,
      destroy: `/notifications/${id}`,
    },
    can: { update: true, delete: true },
    ...overrides,
  };
}

export function makeLatest(data: NotificationData[], unread_count: number, html?: string): LatestResponse {
  return {
    data,
    meta: {
      unread_count,
      html,
      broadcasting: false,
      channel: null,
      poll_interval: 30000,
      routes: {
        index: "notifications.index",
        latest: "notifications.latest",
        "read-all": "notifications.read-all",
        show: "notifications.show",
        read: "notifications.read",
        unread: "notifications.unread",
        destroy: "notifications.destroy",
      },
      urls: { index: "/notifications", latest: "/notifications/latest", read_all: "/notifications/read-all" },
    },
  };
}

export function makeBell(overrides: Partial<BellData> = {}): BellData {
  return {
    unread_count: 1,
    notifications: [makeNotification()],
    broadcasting: false,
    channel: null,
    poll_interval: 30000,
    urls: { index: "/notifications", latest: "/notifications/latest?limit=10", read_all: "/notifications/read-all" },
    ...overrides,
  };
}

/** A fetch mock that answers every request with the given JSON */
export function mockFetch(body: unknown, status = 200) {
  const fetch = vi.fn(async () => new Response(JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } }));

  vi.stubGlobal("fetch", fetch);

  return fetch;
}
