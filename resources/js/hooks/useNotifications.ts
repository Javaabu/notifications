import { useCallback, useEffect, useRef, useState } from "react";
import { subscribe } from "../support/echo";
import { requestJson } from "../support/http";
import type { LatestResponse, NotificationData } from "../types";

export interface UseNotificationsOptions {
  /** The latest route (`notifications.latest`) */
  latestUrl: string;
  /** The read-all route (`notifications.read-all`) */
  readAllUrl: string;
  /** What the page was rendered with, so the bell shows something before the first request */
  initial?: NotificationData[];
  initialUnreadCount?: number;
  /** Listen with window.Echo when true (and Echo is there); poll otherwise */
  broadcasting?: boolean;
  /** The notifiable's private channel, from the server */
  channel?: string | null;
  /** Milliseconds between polls */
  pollInterval?: number;
}

export interface UseNotificationsResult {
  notifications: NotificationData[];
  unreadCount: number;
  loading: boolean;
  refresh: () => Promise<void>;
  markRead: (notification: NotificationData | string) => Promise<void>;
  markAllRead: () => Promise<void>;
}

/**
 * The latest notifications and the unread count, kept up to date over Laravel Echo when the
 * server broadcasts, by polling the latest route otherwise
 */
export function useNotifications(options: UseNotificationsOptions): UseNotificationsResult {
  const [notifications, setNotifications] = useState<NotificationData[]>(options.initial ?? []);
  const [unreadCount, setUnreadCount] = useState(
    options.initialUnreadCount ?? (options.initial ?? []).filter((item) => !item.read).length,
  );
  const [loading, setLoading] = useState(false);
  const latest_url = useRef(options.latestUrl);
  latest_url.current = options.latestUrl;

  const refresh = useCallback(async () => {
    setLoading(true);

    try {
      const response = await requestJson<LatestResponse>(latest_url.current);

      if (response) {
        setNotifications(response.data);
        setUnreadCount(response.meta.unread_count);
      }
    } finally {
      setLoading(false);
    }
  }, []);

  const markRead = useCallback(
    async (notification: NotificationData | string) => {
      const item = typeof notification === "string" ? notifications.find((n) => n.id === notification) : notification;

      if (!item || item.read) {
        return;
      }

      setNotifications((current) => current.map((n) => (n.id === item.id ? { ...n, read: true } : n)));
      setUnreadCount((count) => Math.max(count - 1, 0));

      await requestJson(item.links.read, "POST");
    },
    [notifications],
  );

  const markAllRead = useCallback(async () => {
    setNotifications((current) => current.map((n) => ({ ...n, read: true })));
    setUnreadCount(0);

    await requestJson(options.readAllUrl, "POST");
  }, [options.readAllUrl]);

  useEffect(
    () =>
      subscribe({
        broadcasting: options.broadcasting ?? false,
        channel: options.channel ?? null,
        pollInterval: options.pollInterval ?? 30000,
        onChange: () => void refresh(),
      }),
    [options.broadcasting, options.channel, options.pollInterval, refresh],
  );

  return { notifications, unreadCount, loading, refresh, markRead, markAllRead };
}
