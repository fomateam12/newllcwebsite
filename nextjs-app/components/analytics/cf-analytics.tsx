"use client";

import Script from "next/script";

/**
 * Cloudflare Web Analytics beacon.
 *
 * Cookieless and privacy-first (no cross-site tracking, no client-side
 * state), so it is treated as consent-EXEMPT and loads regardless of the
 * ff_consent choice — a legal review may still prefer it behind consent;
 * to do that, gate on `useConsent() === "granted"` like gtm.tsx.
 *
 * Renders NOTHING while NEXT_PUBLIC_CF_BEACON_TOKEN is unset. Get the
 * token from the Cloudflare dashboard (see ANALYTICS-INTEGRATION.md).
 */

const BEACON_TOKEN = process.env.NEXT_PUBLIC_CF_BEACON_TOKEN ?? "";

export function CloudflareAnalytics() {
  if (!BEACON_TOKEN) return null;
  return (
    <Script
      id="ff-cf-analytics"
      strategy="afterInteractive"
      src="https://static.cloudflareinsights.com/beacon.min.js"
      data-cf-beacon={JSON.stringify({ token: BEACON_TOKEN })}
    />
  );
}
