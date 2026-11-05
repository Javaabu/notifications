import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { follow, initBell, refresh, updateBell, updateCount } from "../../resources/js/blade/notifications";
import { makeLatest, makeNotification, mockFetch } from "./fixtures";

function bellHtml(count = 1) {
  document.body.innerHTML = `
    <div data-notifications-bell
         data-latest-url="/notifications/latest?limit=10"
         data-read-all-url="/notifications/read-all"
         data-broadcasting="false"
         data-channel=""
         data-poll-interval="5000"
         data-csrf="token-1"
         data-label-count="Notifications (:count unread)"
         data-new-notifications=":count new notifications">
      <button data-notifications-trigger aria-expanded="false"></button>
      <span data-notifications-count class="${count === 0 ? "hidden" : ""}">${count}</span>
      <div data-notifications-panel class="hidden">
        <button data-notifications-read-all></button>
        <ul data-notifications-list><li>old</li></ul>
        <div data-notifications-empty class="hidden"></div>
        <span data-notifications-announcer></span>
      </div>
    </div>`;

  return document.querySelector<HTMLElement>("[data-notifications-bell]")!;
}

describe("the Blade bell", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    delete (window as { Echo?: unknown }).Echo;
  });

  it("shows the unread count", () => {
    const bell = bellHtml();

    updateCount(bell, 120);

    expect(bell.querySelector("[data-notifications-count]")!.textContent).toBe("99+");
    expect(bell.querySelector("[data-notifications-trigger]")!.getAttribute("aria-label")).toBe("Notifications (120 unread)");

    updateCount(bell, 0);

    expect(bell.querySelector("[data-notifications-count]")!.classList.contains("hidden")).toBe(true);
    expect(bell.querySelector("[data-notifications-read-all]")!.classList.contains("hidden")).toBe(true);
  });

  it("swaps in the server-rendered items and announces new ones", () => {
    const bell = bellHtml(1);
    bell.dataset.unreadCount = "1";

    updateBell(bell, makeLatest([makeNotification(), makeNotification({ id: "n-2" })], 3, "<li>ތިބާގެ އޯޑަރު</li>"));

    expect(bell.querySelector("[data-notifications-list]")!.innerHTML).toBe("<li>ތިބާގެ އޯޑަރު</li>");
    expect(bell.querySelector("[data-notifications-announcer]")!.textContent).toBe("2 new notifications");
    expect(bell.querySelector("[data-notifications-empty]")!.classList.contains("hidden")).toBe(true);
  });

  it("shows the empty state when there are none", () => {
    const bell = bellHtml(0);

    updateBell(bell, makeLatest([], 0, ""));

    expect(bell.querySelector("[data-notifications-empty]")!.classList.contains("hidden")).toBe(false);
  });

  it("asks for the rendered items when it refreshes", async () => {
    const fetch = mockFetch(makeLatest([], 0, ""));
    const bell = bellHtml();

    await refresh(bell);

    expect(String((fetch.mock.calls[0] as unknown[])[0])).toContain("/notifications/latest?limit=10&html=1");
  });

  it("polls when there is no broadcasting", async () => {
    const fetch = mockFetch(makeLatest([], 0, ""));
    const stop = follow(bellHtml());

    await vi.advanceTimersByTimeAsync(5000);
    expect(fetch).toHaveBeenCalledTimes(1);

    stop();
    await vi.advanceTimersByTimeAsync(5000);
    expect(fetch).toHaveBeenCalledTimes(1);
  });

  it("listens on the notifiable's channel with Echo", () => {
    const notification = vi.fn();
    const leave = vi.fn();
    (window as { Echo?: unknown }).Echo = { private: vi.fn(() => ({ notification })), leave };
    mockFetch(makeLatest([], 0, ""));

    const bell = bellHtml();
    bell.dataset.broadcasting = "true";
    bell.dataset.channel = "notifications.user.1";

    const stop = follow(bell);

    expect((window as unknown as { Echo: { private: ReturnType<typeof vi.fn> } }).Echo.private).toHaveBeenCalledWith("notifications.user.1");
    expect(notification).toHaveBeenCalledOnce();

    stop();
    expect(leave).toHaveBeenCalledWith("notifications.user.1");
  });

  it("opens and closes the panel", () => {
    mockFetch(makeLatest([], 0, ""));
    const bell = bellHtml();
    initBell(bell);

    const trigger = bell.querySelector<HTMLElement>("[data-notifications-trigger]")!;
    const panel = bell.querySelector<HTMLElement>("[data-notifications-panel]")!;

    trigger.click();
    expect(panel.classList.contains("hidden")).toBe(false);
    expect(trigger.getAttribute("aria-expanded")).toBe("true");

    document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape" }));
    expect(panel.classList.contains("hidden")).toBe(true);
  });

  it("marks everything read with the CSRF token", async () => {
    const fetch = mockFetch(makeLatest([], 0, ""));
    const bell = bellHtml();
    initBell(bell);

    bell.querySelector<HTMLElement>("[data-notifications-read-all]")!.click();
    await vi.advanceTimersByTimeAsync(0);

    const [url, init] = fetch.mock.calls[0] as unknown as [string, RequestInit];

    expect(url).toBe("/notifications/read-all");
    expect(init.method).toBe("POST");
    expect((init.headers as Record<string, string>)["X-CSRF-TOKEN"]).toBe("token-1");
  });
});
