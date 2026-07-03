import { cookies } from "next/headers";
import type { ConsentValue } from "@/lib/analytics-consent";

/**
 * Server-side read of the ff_consent cookie, so SSR HTML already reflects
 * the visitor's stored choice (banner omitted for deciders, GTM/Pixel tags
 * present for granted visitors). Must stay in sync with the cookie name in
 * lib/analytics-consent.ts (that module is "use client", so the constant
 * can't be imported here).
 */
export async function readConsentFromRequest(): Promise<ConsentValue | null> {
  const value = (await cookies()).get("ff_consent")?.value;
  return value === "granted" || value === "denied" ? value : null;
}
