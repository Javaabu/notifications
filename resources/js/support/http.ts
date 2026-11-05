/**
 * JSON requests to the notification routes, with Laravel's CSRF token: the csrf-token meta
 * tag when there is one, the XSRF-TOKEN cookie otherwise
 */
export function csrfHeaders(): Record<string, string> {
  if (typeof document === "undefined") {
    return {};
  }

  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

  if (meta) {
    return { "X-CSRF-TOKEN": meta };
  }

  const cookie = document.cookie
    .split("; ")
    .find((part) => part.startsWith("XSRF-TOKEN="))
    ?.slice("XSRF-TOKEN=".length);

  return cookie ? { "X-XSRF-TOKEN": decodeURIComponent(cookie) } : {};
}

export async function requestJson<T>(url: string, method: "GET" | "POST" | "DELETE" = "GET"): Promise<T | null> {
  const response = await fetch(url, {
    method,
    headers: {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...(method === "GET" ? {} : csrfHeaders()),
    },
    credentials: "same-origin",
  });

  if (!response.ok || response.status === 204) {
    return null;
  }

  return (await response.json()) as T;
}
