import { act, renderHook } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { useNotifications } from "../../resources/js/hooks/useNotifications";
import { makeLatest, makeNotification, mockFetch } from "./fixtures";

describe("useNotifications", () => {
  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    document.head.innerHTML = "";
  });

  it("starts with what the page was rendered with", () => {
    mockFetch(makeLatest([], 0));

    const { result } = renderHook(() =>
      useNotifications({ latestUrl: "/latest", readAllUrl: "/read-all", initial: [makeNotification()], initialUnreadCount: 4 }),
    );

    expect(result.current.notifications).toHaveLength(1);
    expect(result.current.unreadCount).toBe(4);
  });

  it("polls the latest route", async () => {
    vi.useFakeTimers();
    mockFetch(makeLatest([makeNotification(), makeNotification({ id: "n-2" })], 2));

    const { result } = renderHook(() => useNotifications({ latestUrl: "/latest", readAllUrl: "/read-all", pollInterval: 2000 }));

    await act(async () => {
      await vi.advanceTimersByTimeAsync(2000);
    });

    expect(result.current.notifications).toHaveLength(2);
    expect(result.current.unreadCount).toBe(2);
  });

  it("marks one read straight away and tells the server", async () => {
    document.head.innerHTML = '<meta name="csrf-token" content="token-2">';
    const fetch = mockFetch(makeLatest([], 0));
    const notification = makeNotification();

    const { result } = renderHook(() =>
      useNotifications({ latestUrl: "/latest", readAllUrl: "/read-all", initial: [notification] }),
    );

    await act(() => result.current.markRead(notification.id));

    expect(result.current.notifications[0]!.read).toBe(true);
    expect(result.current.unreadCount).toBe(0);

    const [url, init] = fetch.mock.calls[0] as unknown as [string, RequestInit];

    expect(url).toBe("/notifications/n-1/read");
    expect((init.headers as Record<string, string>)["X-CSRF-TOKEN"]).toBe("token-2");
  });

  it("marks everything read", async () => {
    document.cookie = "XSRF-TOKEN=abc%3D";
    const fetch = mockFetch({ marked: 2, unread_count: 0 });

    const { result } = renderHook(() =>
      useNotifications({
        latestUrl: "/latest",
        readAllUrl: "/read-all",
        initial: [makeNotification(), makeNotification({ id: "n-2" })],
      }),
    );

    await act(() => result.current.markAllRead());

    expect(result.current.unreadCount).toBe(0);
    expect(result.current.notifications.every((n) => n.read)).toBe(true);

    const [, init] = fetch.mock.calls[0] as unknown as [string, RequestInit];

    expect((init.headers as Record<string, string>)["X-XSRF-TOKEN"]).toBe("abc=");
  });
});
