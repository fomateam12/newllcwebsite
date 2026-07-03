/**
 * Canonical site origin for absolute URLs (metadata, sitemap, emails).
 * Driven by NEXT_PUBLIC_SITE_URL so the workers.dev URL can be swapped
 * for https://fomafamilyllc.com at cutover without code changes.
 */
const DEFAULT_SITE_URL = "https://fomafamily-v2.fomalaser1212.workers.dev";

export function getSiteUrl(): string {
  const raw = process.env.NEXT_PUBLIC_SITE_URL || DEFAULT_SITE_URL;
  return raw.replace(/\/+$/, "");
}

/** Absolute URL from a path; passes already-absolute URLs through. */
export function absoluteUrl(pathOrUrl: string): string {
  if (/^https?:\/\//i.test(pathOrUrl)) return pathOrUrl;
  return `${getSiteUrl()}${pathOrUrl.startsWith("/") ? "" : "/"}${pathOrUrl}`;
}
