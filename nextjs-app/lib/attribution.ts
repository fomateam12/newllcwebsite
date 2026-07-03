/**
 * First-touch marketing attribution.
 *
 * On a visitor's first landing with campaign parameters we capture
 * utm_source / utm_medium / utm_campaign / utm_term / utm_content plus
 * gclid / fbclid into a first-party `ff_attrib` cookie (30 days).
 * First-touch: the cookie is only written when absent, so later visits
 * with different UTMs do NOT overwrite the original source.
 *
 * `getAttribution()` reads it back — checkout should attach it to the
 * Stripe session metadata so the webhook can persist it on the order
 * (see ANALYTICS-INTEGRATION.md § Attribution).
 */

export const ATTRIBUTION_COOKIE = "ff_attrib";
const ATTRIBUTION_MAX_AGE = 60 * 60 * 24 * 30; // 30 days

const ATTRIBUTION_KEYS = [
  "utm_source",
  "utm_medium",
  "utm_campaign",
  "utm_term",
  "utm_content",
  "gclid",
  "fbclid",
] as const;

type AttributionKey = (typeof ATTRIBUTION_KEYS)[number];

export type Attribution = Partial<Record<AttributionKey, string>> & {
  /** pathname of the first-touch landing page */
  landing_page?: string;
  /** ISO timestamp of first touch */
  first_touch_at?: string;
};

const MAX_VALUE_LENGTH = 200; // keep the cookie small and un-abusable

/**
 * Capture first-touch attribution from the current URL. Client-only,
 * idempotent, no-op when the cookie already exists or the URL carries no
 * campaign parameters. Mounted once per page load via
 * components/analytics/attribution-capture.tsx.
 */
export function captureAttribution(): void {
  if (typeof window === "undefined") return;
  if (readCookie(ATTRIBUTION_COOKIE) !== null) return; // first-touch only

  const params = new URLSearchParams(window.location.search);
  const attribution: Attribution = {};
  let found = false;
  for (const key of ATTRIBUTION_KEYS) {
    const value = params.get(key);
    if (value) {
      attribution[key] = value.slice(0, MAX_VALUE_LENGTH);
      found = true;
    }
  }
  if (!found) return;

  attribution.landing_page = window.location.pathname.slice(0, MAX_VALUE_LENGTH);
  attribution.first_touch_at = new Date().toISOString();

  const secure = window.location.protocol === "https:" ? "; Secure" : "";
  document.cookie = `${ATTRIBUTION_COOKIE}=${encodeURIComponent(
    JSON.stringify(attribution),
  )}; Max-Age=${ATTRIBUTION_MAX_AGE}; Path=/; SameSite=Lax${secure}`;
}

/** Read the stored first-touch attribution, or `null` when none exists. */
export function getAttribution(): Attribution | null {
  const raw = readCookie(ATTRIBUTION_COOKIE);
  if (raw === null) return null;
  try {
    const parsed = JSON.parse(decodeURIComponent(raw));
    if (typeof parsed !== "object" || parsed === null) return null;
    return parsed as Attribution;
  } catch {
    return null;
  }
}

function readCookie(name: string): string | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(
    new RegExp(`(?:^|;\\s*)${name}=([^;]*)`),
  );
  return match ? match[1] : null;
}
