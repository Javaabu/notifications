import { render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { NotificationBell, toNotificationItem } from "../../resources/js/components/NotificationBell";
import { NotificationListItem } from "../../resources/js/components/NotificationListItem";
import { makeBell, makeLatest, makeNotification, mockFetch, strings } from "./fixtures";

describe("NotificationBell", () => {
  afterEach(() => vi.unstubAllGlobals());

  it("shows the unread count on Onu's notification center", () => {
    mockFetch(makeLatest([], 0));

    render(<NotificationBell bell={makeBell({ unread_count: 3, notifications: [makeNotification()] })} />);

    const trigger = document.querySelector("[data-part='notification-center-trigger']")!;

    expect(trigger).toBeTruthy();
    expect(document.querySelector("[data-slot='notification-center-count']")!.textContent).toBe("1");
  });

  it("maps notifications to Onu's items", () => {
    const item = toNotificationItem(makeNotification({ read: true, thumbnail: null }));

    expect(item).toMatchObject({
      id: "n-1",
      title: "ތިބާގެ އޯޑަރު ފޮނުވައިފި",
      description: "Order ORD-1 is on its way.",
      read: true,
      href: "/notifications/n-1",
      icon: "bell",
      tone: "success",
    });
  });
});

describe("NotificationListItem", () => {
  it("shows the notification with its actions", () => {
    render(
      <ul>
        <NotificationListItem notification={makeNotification()} strings={strings} />
      </ul>,
    );

    expect(screen.getByText("ތިބާގެ އޯޑަރު ފޮނުވައިފި").getAttribute("href")).toBe("/notifications/n-1");
    expect(screen.getByText("Mark as read")).toBeTruthy();
    expect(screen.getByLabelText("Delete")).toBeTruthy();
    expect(screen.getByText("Unread")).toBeTruthy();
  });

  it("hides actions the user may not use", () => {
    render(
      <ul>
        <NotificationListItem
          notification={makeNotification({ read: true, can: { update: false, delete: false } })}
          strings={strings}
        />
      </ul>,
    );

    expect(screen.queryByText("Mark as unread")).toBeNull();
    expect(screen.queryByLabelText("Delete")).toBeNull();
  });
});
