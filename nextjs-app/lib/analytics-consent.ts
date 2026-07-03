"use client";

/**
 * First-party analytics consent state.
 *
 * The visitor's choice is stored in a `ff_consent` cookie
 * (`granted` | `denied`, 365 days). Until a choice exists no marketing or
 * analytics script (GTM, Meta Pixel) is loaded at all — see
 * components/analytics/*. Cloudflare Web Analytics is cookieless and is
 * treated as consent-exempt (see ANALYTICS-INTEGRATION.md).
 */

import { useSyncExternalStore } from "react";

export type ConsentValue = "granted" | "denied";

export const CONSENT_COOKIE = "ff_consent";
const CONSENT_MAX_AGE = 60 * 60 * 24 * 365; // 365 days
const CONSENT_EVENT = "ff:consent-change";

/** Read the current consent choice from the cookie. `null` = no choice yet. */
export function readConsent(): ConsentValue | null {
  if (typeof document === "undefined") return null;
  const match = document.cookie.match(
    new RegExp(`(?:^|;\\s*)${CONSENT_COOKIE}=(granted|denied)(?:;|$)`),
  );
  return match ? (match[1] as ConsentValue) : null;
}

/** Persist the visitor's choice and notify subscribed components. */
export function setConsent(value: ConsentValue): void {
  if (typeof document === "undefined") return;
  const secure = window.location.protocol === "https:" ? "; Secure" : "";
  document.cookie = `${CONSENT_COOKIE}=${value}; Max-Age=${CONSENT_MAX_AGE}; Path=/; SameSite=Lax${secure}`;
  window.dispatchEvent(new Event(CONSENT_EVENT));
}

/** True only when the visitor explicitly accepted. */
export function hasAnalyticsConsent(): boolean {
  return readConsent() === "granted";
}

function subscribe(callback: () => void): () => void {
  window.addEventListener(CONSENT_EVENT, callback);
  return () => window.removeEventListener(CONSENT_EVENT, callback);
}

const getServerSnapshot = () => null;

/**
 * React hook — current consent state, re-renders when the banner updates it.
 * Returns `null` during SSR/hydration and while no choice has been made.
 */
export function useConsent(): ConsentValue | null {
  return useSyncExternalStore(subscribe, readConsent, getServerSnapshot);
}
