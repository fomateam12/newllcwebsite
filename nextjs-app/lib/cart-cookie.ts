/**
 * Compact cart cookie — the persistence layer for the cart.
 *
 * DESIGN (and its tradeoff, documented as required):
 *
 * The canonical persisted cart is a COOKIE, not localStorage, so that server
 * components / route handlers on Cloudflare Workers can read the cart during
 * SSR (localStorage is invisible to the server). Cookies are capped at ~4 KB
 * including name + attributes, so the cookie carries only what the server
 * actually needs and can't re-derive:
 *
 *   { v: 1, i: [{ p: productId, q: quantity, c?: customization }] }
 *
 * Display data (name, slug, price, image URL) is deliberately NOT in the
 * cookie — product slugs alone run ~80 chars here, which would blow the
 * budget after a dozen items. The server can re-join display data from D1 by
 * productId; the client keeps a cosmetic display cache in localStorage (see
 * cart-context.tsx). If that cache is ever missing for a cookie item the UI
 * degrades to a placeholder name instead of dropping the item.
 *
 * Size handling, graceful and in priority order:
 *   1. Free-text customization values are trimmed to MAX_TEXT_LEN chars.
 *   2. At most MAX_COOKIE_ITEMS items are serialized (cart page still shows
 *      everything from client state; the overflow just isn't server-visible).
 *   3. If the encoded value still exceeds MAX_COOKIE_BYTES, customization
 *      payloads are dropped from the OLDEST items first (quantities are tiny
 *      and always survive; personalization text is the heavy part).
 *   4. As a last resort, trailing items are dropped from the cookie.
 *
 * Long-term fix is the server-side cart table drafted in
 * migrations/drafts/carts.sql — the cookie then shrinks to a single opaque
 * token and every limit above disappears.
 */

export const CART_COOKIE_NAME = "foma_cart_v1";
export const CART_COOKIE_MAX_AGE = 60 * 60 * 24 * 30; // 30 days

export const MAX_COOKIE_ITEMS = 24;
export const MAX_TEXT_LEN = 60;
/** Budget for the encoded VALUE; leaves headroom for name + attributes. */
export const MAX_COOKIE_BYTES = 3800;

export type CompactCartItem = {
  /** product id */
  p: number;
  /** quantity */
  q: number;
  /** customization data (trimmed), if any */
  c?: unknown;
};

type CookiePayload = { v: 1; i: CompactCartItem[] };

/* ------------------------------------------------------------------ trim */

/** Recursively trim free-text string fields inside customization data. */
function trimCustomization(value: unknown, depth = 0): unknown {
  if (depth > 4) return undefined;
  if (typeof value === "string") {
    return value.length > MAX_TEXT_LEN ? value.slice(0, MAX_TEXT_LEN) : value;
  }
  if (Array.isArray(value)) {
    return value.slice(0, 12).map((v) => trimCustomization(v, depth + 1));
  }
  if (value && typeof value === "object") {
    const out: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(value as Record<string, unknown>)) {
      const trimmed = trimCustomization(v, depth + 1);
      if (trimmed !== undefined) out[k] = trimmed;
    }
    return out;
  }
  if (typeof value === "number" || typeof value === "boolean" || value === null) {
    return value;
  }
  return undefined;
}

/* ---------------------------------------------------------------- encode */

function byteLength(s: string): number {
  return new TextEncoder().encode(s).length;
}

/**
 * Serialize cart items to the encoded cookie value (URI-encoded JSON).
 * Never throws; always returns a value under MAX_COOKIE_BYTES.
 */
export function encodeCartCookie(
  items: Array<{ productId: number; quantity: number; customizationData?: unknown }>,
): string {
  let compact: CompactCartItem[] = items.slice(0, MAX_COOKIE_ITEMS).map((it) => {
    const entry: CompactCartItem = { p: it.productId, q: it.quantity };
    if (it.customizationData !== undefined) {
      const trimmed = trimCustomization(it.customizationData);
      if (trimmed !== undefined) entry.c = trimmed;
    }
    return entry;
  });

  const encode = (list: CompactCartItem[]) =>
    encodeURIComponent(JSON.stringify({ v: 1, i: list } satisfies CookiePayload));

  let value = encode(compact);

  // Step 3: shed customization from the oldest items first.
  for (let idx = 0; idx < compact.length && byteLength(value) > MAX_COOKIE_BYTES; idx++) {
    if (compact[idx].c === undefined) continue;
    compact = compact.map((it, i) => (i === idx ? { p: it.p, q: it.q } : it));
    value = encode(compact);
  }

  // Step 4: last resort — drop trailing items.
  while (compact.length > 0 && byteLength(value) > MAX_COOKIE_BYTES) {
    compact = compact.slice(0, -1);
    value = encode(compact);
  }

  return value;
}

/* ---------------------------------------------------------------- decode */

/**
 * Parse an encoded cookie value back to compact items. Tolerant: malformed
 * input returns [] rather than throwing (cookies are user-controlled).
 */
export function decodeCartCookie(raw: string | undefined | null): CompactCartItem[] {
  if (!raw) return [];
  try {
    const parsed = JSON.parse(decodeURIComponent(raw)) as unknown;
    if (!parsed || typeof parsed !== "object") return [];
    const { v, i } = parsed as { v?: unknown; i?: unknown };
    if (v !== 1 || !Array.isArray(i)) return [];
    return i.filter(
      (it): it is CompactCartItem =>
        typeof it === "object" &&
        it !== null &&
        Number.isInteger((it as CompactCartItem).p) &&
        (it as CompactCartItem).p > 0 &&
        Number.isInteger((it as CompactCartItem).q) &&
        (it as CompactCartItem).q >= 1,
    );
  } catch {
    return [];
  }
}

/* ------------------------------------------------------- client helpers */

/** Write the cart cookie from the browser. No-op during SSR. */
export function writeCartCookieClient(
  items: Array<{ productId: number; quantity: number; customizationData?: unknown }>,
): void {
  if (typeof document === "undefined") return;
  if (items.length === 0) {
    document.cookie = `${CART_COOKIE_NAME}=; Path=/; Max-Age=0; SameSite=Lax`;
    return;
  }
  const value = encodeCartCookie(items);
  document.cookie = `${CART_COOKIE_NAME}=${value}; Path=/; Max-Age=${CART_COOKIE_MAX_AGE}; SameSite=Lax`;
}

/** Read + decode the cart cookie in the browser. Returns [] during SSR. */
export function readCartCookieClient(): CompactCartItem[] {
  if (typeof document === "undefined") return [];
  const match = document.cookie
    .split("; ")
    .find((c) => c.startsWith(`${CART_COOKIE_NAME}=`));
  return decodeCartCookie(match ? match.slice(CART_COOKIE_NAME.length + 1) : null);
}

/*
 * Server usage (future — nothing reads it server-side yet):
 *
 *   import { cookies } from "next/headers";
 *   const raw = (await cookies()).get(CART_COOKIE_NAME)?.value;
 *   const items = decodeCartCookie(raw);
 *   // join items[].p against D1 products for names/prices
 */
