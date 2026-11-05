/**
 * The Blade notifications page and bell (<x-notifications::bell>).
 *
 * Each [data-notifications-bell] opens and closes its panel, and stays up to date: over
 * Laravel Echo when the bell says broadcasting is on and window.Echo exists (a new
 * notification triggers a refresh), by polling the latest route otherwise. Refreshes swap
 * in the items the server renders, so they look the same as on first load.
 *
 * Forms with data-confirm ask before they submit.
 */
import { subscribe } from "../support/echo";
import type { LatestResponse } from "../types";

function format(template: string | undefined, count: number): string {
  return (template ?? "").replace(":count", count.toLocaleString());
}

/**
 * Show the unread count on the badge and in the button's label
 */
export function updateCount(bell: HTMLElement, count: number): void {
  const badge = bell.querySelector<HTMLElement>("[data-notifications-count]");
  const trigger = bell.querySelector<HTMLElement>("[data-notifications-trigger]");
  const readAll = bell.querySelector<HTMLElement>("[data-notifications-read-all]");

  if (badge) {
    badge.textContent = count > 99 ? "99+" : String(count);
    badge.classList.toggle("hidden", count === 0);
  }

  if (trigger) {
    trigger.setAttribute("aria-label", format(bell.dataset.labelCount, count));
  }

  if (readAll) {
    readAll.classList.toggle("hidden", count === 0);
  }

  bell.dataset.unreadCount = String(count);
}

/**
 * Put the latest notifications (rendered by the server) in the panel
 */
export function updateBell(bell: HTMLElement, response: LatestResponse): void {
  const previous = Number(bell.dataset.unreadCount ?? "0");
  const count = response.meta.unread_count;
  const list = bell.querySelector<HTMLElement>("[data-notifications-list]");
  const empty = bell.querySelector<HTMLElement>("[data-notifications-empty]");
  const announcer = bell.querySelector<HTMLElement>("[data-notifications-announcer]");

  if (list && typeof response.meta.html === "string") {
    list.innerHTML = response.meta.html;
  }

  if (empty) {
    empty.classList.toggle("hidden", response.data.length > 0);
  }

  updateCount(bell, count);

  if (announcer && count > previous) {
    announcer.textContent = format(bell.dataset.newNotifications, count - previous);
  }
}

function latestUrl(bell: HTMLElement): string {
  const url = new URL(bell.dataset.latestUrl ?? "", window.location.href);

  url.searchParams.set("html", "1");

  return url.toString();
}

export async function refresh(bell: HTMLElement): Promise<void> {
  const response = await fetch(latestUrl(bell), {
    headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
    credentials: "same-origin",
  });

  if (!response.ok) {
    return;
  }

  updateBell(bell, (await response.json()) as LatestResponse);
}

async function readAll(bell: HTMLElement): Promise<void> {
  const url = bell.dataset.readAllUrl;

  if (!url) {
    return;
  }

  const response = await fetch(url, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      "X-CSRF-TOKEN": bell.dataset.csrf ?? "",
    },
    credentials: "same-origin",
  });

  if (response.ok) {
    await refresh(bell);
  }
}

function setOpen(bell: HTMLElement, open: boolean): void {
  const trigger = bell.querySelector<HTMLElement>("[data-notifications-trigger]");
  const panel = bell.querySelector<HTMLElement>("[data-notifications-panel]");

  panel?.classList.toggle("hidden", !open);
  trigger?.setAttribute("aria-expanded", open ? "true" : "false");
  bell.dataset.open = open ? "true" : "false";
}

function isOpen(bell: HTMLElement): boolean {
  return bell.dataset.open === "true";
}

/**
 * Follow new notifications: Echo when broadcasting, polling otherwise. Returns a stop function.
 */
export function follow(bell: HTMLElement): () => void {
  return subscribe({
    broadcasting: bell.dataset.broadcasting === "true",
    channel: bell.dataset.channel || null,
    pollInterval: Number(bell.dataset.pollInterval) || 30000,
    onChange: () => void refresh(bell),
  });
}

export function initBell(bell: HTMLElement): () => void {
  if (bell.dataset.initialized === "true") {
    return () => {};
  }

  bell.dataset.initialized = "true";

  const trigger = bell.querySelector<HTMLElement>("[data-notifications-trigger]");
  const badge = bell.querySelector<HTMLElement>("[data-notifications-count]");

  bell.dataset.unreadCount = badge?.classList.contains("hidden") ? "0" : (badge?.textContent ?? "0").replace("+", "");

  trigger?.addEventListener("click", () => {
    const open = !isOpen(bell);

    setOpen(bell, open);

    if (open) {
      void refresh(bell);
    }
  });

  bell.querySelector("[data-notifications-read-all]")?.addEventListener("click", () => void readAll(bell));

  document.addEventListener("click", (event) => {
    if (isOpen(bell) && !bell.contains(event.target as Node)) {
      setOpen(bell, false);
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && isOpen(bell)) {
      setOpen(bell, false);
      trigger?.focus();
    }
  });

  return follow(bell);
}

function confirmForms(): void {
  document.addEventListener("submit", (event) => {
    const form = event.target as HTMLFormElement | null;
    const message = form?.dataset?.confirm;

    if (message && !window.confirm(message)) {
      event.preventDefault();
    }
  });
}

let confirming = false;

export function init(): void {
  document.querySelectorAll<HTMLElement>("[data-notifications-bell]").forEach((bell) => initBell(bell));

  if (!confirming) {
    confirming = true;
    confirmForms();
  }
}

if (typeof document !== "undefined") {
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
}
