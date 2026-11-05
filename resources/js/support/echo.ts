import type { EchoLike } from "../types";

/** window.Echo (Laravel Echo), when the app set it up */
export function echo(): EchoLike | undefined {
  return typeof window === "undefined" ? undefined : (window as unknown as { Echo?: EchoLike }).Echo;
}

/**
 * Call back when something new happens: on each notification broadcast on the channel when
 * broadcasting is on and Echo is there, every interval otherwise (skipped while the tab is
 * hidden). Returns a function that stops it.
 */
export function subscribe(options: {
  broadcasting: boolean;
  channel: string | null;
  pollInterval: number;
  onChange: () => void;
}): () => void {
  const client = echo();
  const { channel } = options;

  if (options.broadcasting && client && channel) {
    client.private(channel).notification(() => options.onChange());

    return () => client.leave(channel);
  }

  const timer = window.setInterval(() => {
    if (document.visibilityState === "hidden") {
      return;
    }

    options.onChange();
  }, Math.max(options.pollInterval || 30000, 1000));

  return () => window.clearInterval(timer);
}
