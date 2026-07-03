"use client";

import Link from "next/link";
import {
  setConsent,
  useConsent,
  type ConsentValue,
} from "@/lib/analytics-consent";

/**
 * Cookie consent banner UI — small, brand-styled, pinned to the bottom.
 *
 * Shows only while no ff_consent cookie exists; Accept / Decline writes
 * the cookie (365 days) and the analytics components react immediately.
 * `initialConsent` is the server-read cookie value, so returning visitors
 * never get the banner in SSR HTML (no flash) while first-time visitors do.
 */
export function ConsentBannerClient({
  initialConsent,
}: {
  initialConsent: ConsentValue | null;
}) {
  const consent = useConsent(initialConsent);

  if (consent !== null) return null;

  return (
    <div
      role="region"
      aria-label="Cookie consent"
      className="fixed inset-x-0 bottom-0 z-50 px-4 pb-4"
    >
      <div className="mx-auto flex max-w-3xl flex-col gap-3 rounded-lg border border-line bg-paper p-4 shadow-lg sm:flex-row sm:items-center">
        <p className="flex-1 text-sm leading-relaxed text-ink/80">
          We use cookies to understand how our store is used and to improve
          your experience. You can change your mind anytime. See our{" "}
          <Link
            href="/privacy"
            className="text-pine underline underline-offset-2 hover:text-pine-deep"
          >
            Privacy Policy
          </Link>
          .
        </p>
        <div className="flex shrink-0 items-center gap-2">
          <button
            type="button"
            onClick={() => setConsent("denied")}
            className="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink/70 transition-colors hover:border-ink/30 hover:text-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine"
          >
            Decline
          </button>
          <button
            type="button"
            onClick={() => setConsent("granted")}
            className="rounded-md bg-pine px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-pine-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine"
          >
            Accept
          </button>
        </div>
      </div>
    </div>
  );
}
