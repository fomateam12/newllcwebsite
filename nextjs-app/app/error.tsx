"use client";

import Link from "next/link";
import { useEffect } from "react";

/**
 * Branded client error boundary. Renders whenever a route segment throws
 * during render — the digest goes to the console (and server logs); the
 * visitor only ever sees this generic page.
 */
export default function ErrorPage({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Surface for observability; nothing internal reaches the DOM.
    console.error("app-error-boundary", error.digest ?? error.message);
  }, [error]);

  return (
    <div className="mx-auto flex min-h-[60vh] max-w-lg flex-col items-center justify-center px-5 text-center">
      <p className="font-mono text-xs uppercase tracking-widest text-amber">
        Something went wrong
      </p>
      <h1 className="mt-3 font-display text-3xl font-semibold text-pine-deep">
        The workshop hit a snag
      </h1>
      <p className="mt-3 text-sm leading-relaxed text-ink/60">
        An unexpected error interrupted this page. Your cart and orders are
        safe — this was on our end, not yours.
      </p>
      <div className="mt-6 flex items-center gap-3">
        <button
          type="button"
          onClick={reset}
          className="rounded-md bg-pine px-5 py-2 font-mono text-sm text-paper transition-colors hover:bg-pine-deep"
        >
          Try again
        </button>
        <Link
          href="/"
          className="rounded-md border border-line px-5 py-2 font-mono text-sm text-ink/70 transition-colors hover:border-pine hover:text-pine"
        >
          Back home
        </Link>
      </div>
      {error.digest ? (
        <p className="mt-6 font-mono text-[11px] text-ink/40">
          Reference: {error.digest}
        </p>
      ) : null}
    </div>
  );
}
