/** Onu's tones, which notification colours map to */
export type Tone = "neutral" | "info" | "success" | "warning" | "danger";

/** A database notification as NotificationResource serializes it, in the reader's language */
export interface NotificationData {
  id: string;
  type: string;
  type_label: string;
  title: string;
  body: string | null;
  action_text: string | null;
  action_url: string | null;
  icon: string;
  color: string;
  tone: Tone;
  thumbnail: string | null;
  extra: Record<string, unknown>;
  subject_type: string | null;
  subject_id: string | number | null;
  read: boolean;
  read_at: string | null;
  created_at: string | null;
  created_at_human: string | null;
  links: { show: string; read: string; unread: string; destroy: string };
  can: { update: boolean; delete: boolean };
}

export interface NotificationRoutes {
  index: string;
  latest: string;
  "read-all": string;
  show: string;
  read: string;
  unread: string;
  destroy: string;
}

/** How a page or the bell stays up to date, and where the routes are */
export interface NotificationsMeta {
  broadcasting: boolean;
  channel: string | null;
  poll_interval: number;
  routes: NotificationRoutes;
  urls: { index: string; latest: string; read_all: string };
}

/** The latest route's JSON */
export interface LatestResponse {
  data: NotificationData[];
  meta: NotificationsMeta & { unread_count: number; html?: string };
}

/** What `Javaabu\Notifications\Support\BellData::make()` gives the bell (share it with Inertia) */
export interface BellData {
  unread_count: number;
  notifications: NotificationData[];
  broadcasting: boolean;
  channel: string | null;
  poll_interval: number;
  urls: { index: string; latest: string; read_all: string };
}

export interface Option<T extends string = string> {
  value: T;
  label: string;
}

export interface NotificationsStrings {
  title: string;
  mark_all_read: string;
  mark_read: string;
  mark_unread: string;
  delete: string;
  confirm_delete: string;
  status: string;
  all_statuses: string;
  type: string;
  all_types: string;
  filter: string;
  unread: string;
  no_notifications: string;
  no_notifications_message: string;
  view_all: string;
}

export interface Paginated<T> {
  data: T[];
  links: Record<string, string | null>;
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    path: string;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
}

/** notifications::Notifications/Index */
export interface NotificationsIndexProps extends NotificationsMeta {
  title: string;
  notifications: Paginated<NotificationData>;
  is_own: boolean;
  filters: { status: "unread" | "read" | null; type: string | null };
  statuses: Option<"unread" | "read">[];
  types: Option[];
  strings: NotificationsStrings;
  flash?: { success?: string | null };
}

/** The part of Laravel Echo the package uses */
export interface EchoChannel {
  notification(callback: (notification: Record<string, unknown>) => void): unknown;
}

export interface EchoLike {
  private(channel: string): EchoChannel;
  leave(channel: string): void;
}
