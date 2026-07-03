# Analytics integration guide

Everything on the `analytics-gtm` branch is **infrastructure only** — no
store component fires an e-commerce event yet. This document is the recipe
for the single post-merge integration session: exact file, location, and
snippet for each GA4 event, plus setup notes for Cloudflare Web Analytics
and attribution.

## Architecture recap

| Piece | File | Behavior |
| --- | --- | --- |
| Consent state | `lib/analytics-consent.ts` | `ff_consent` cookie (`granted`\|`denied`, 365 d). `useConsent()` hook, `hasAnalyticsConsent()` guard. |
| Consent banner | `components/analytics/consent-banner.tsx` | Bottom banner while no cookie exists. Accept / Decline; links to `/privacy`. |
| GTM | `components/analytics/gtm.tsx` | Loads only when `NEXT_PUBLIC_GTM_ID` set **and** consent granted. Consent Mode v2 defaults (all denied) are pushed before the container boots, then updated to granted. |
| Meta Pixel | `components/analytics/meta-pixel.tsx` | Same gating with `NEXT_PUBLIC_META_PIXEL_ID`. PageView on load + per route change. |
| CF Web Analytics | `components/analytics/cf-analytics.tsx` | Cookieless → consent-exempt. Needs `NEXT_PUBLIC_CF_BEACON_TOKEN`. |
| GA4 helpers | `lib/analytics.ts` | Typed dataLayer pushes; silent no-op on SSR or without consent. |
| Attribution | `lib/attribution.ts` + `components/analytics/attribution-capture.tsx` | First-touch UTM/gclid/fbclid → `ff_attrib` cookie (30 d). |

All env vars are empty placeholders in `.env.example`. With none set, the
rendered HTML contains **zero** analytics scripts.

Two implementation notes:

- The consent cookie is also read **server-side**
  (`components/analytics/consent-server.ts` via `next/headers`), so SSR
  HTML already reflects the visitor's stored choice: banner present for
  first-timers, GTM/Pixel bootstrap present for consented returners.
  Side effect: every route is dynamically rendered (they nearly all were
  already — the app runs on a Worker, so this costs nothing).
- GTM and Pixel bootstraps are plain inline `<script>` elements rather
  than `next/script(afterInteractive)`: App Router injects
  afterInteractive scripts client-side post-hydration, which would keep
  the consent-gated tags out of server HTML entirely (untestable via
  curl, and slower for consented visitors). The just-clicked-Accept path
  is covered by an idempotent `useEffect` bootstrap guarded by a window
  flag. The Cloudflare beacon (src-based, consent-exempt) still uses
  `next/script`.

## GA4 e-commerce events — where each call goes

> Convention for `AnalyticsItem`: `item_id` = `String(product.id)` (D1 id),
> `price` in dollars (the display/unit price), `quantity` integer.

### 1. `view_item` — product page

`app/urun/[slug]/page.tsx` is a server component, so the event needs a tiny
client beacon. Create `components/analytics/view-item-beacon.tsx`:

```tsx
"use client";

import { useEffect } from "react";
import { viewItem, type AnalyticsItem } from "@/lib/analytics";

export function ViewItemBeacon({ item }: { item: AnalyticsItem }) {
  useEffect(() => {
    viewItem({ items: [item] });
  }, [item]);
  return null;
}
```

Then in `app/urun/[slug]/page.tsx`, inside the page component's JSX (next to
the existing `<AddToCart …/>` island):

```tsx
<ViewItemBeacon
  item={{
    item_id: String(product.id),
    item_name: product.name,
    price: product.price,
    quantity: 1,
  }}
/>
```

### 2. `view_item_list` — category page

Same beacon pattern. Create `components/analytics/view-item-list-beacon.tsx`
(identical shape, calls `viewItemList({ item_list_id, item_list_name, items })`),
and mount it in `app/kategori/[slug]/page.tsx` after products are fetched:

```tsx
<ViewItemListBeacon
  listId={cat.slug}
  listName={cat.name}
  items={products.map((p, index) => ({
    item_id: String(p.id),
    item_name: p.name,
    price: p.price,
    index,
    item_list_id: cat.slug,
    item_list_name: cat.name,
  }))}
/>
```

(If pagination matters later, include `?page` in `item_list_id` or pass
`index` offset by `(page - 1) * PAGE_SIZE`.)

### 3. `add_to_cart` / `remove_from_cart` — cart context

`components/cart/cart-context.tsx`. Fire inside the mutation callbacks so
every entry point (product page button, quick add, quantity steppers) is
covered once, centrally.

```tsx
import { addToCart, removeFromCart } from "@/lib/analytics";

// in addItem, after setItems(...):
addToCart({
  items: [{
    item_id: String(item.productId),
    item_name: item.name,
    price: item.unitPrice,
    quantity,
  }],
});

// in removeItem, before filtering (need the item's details):
setItems((prev) => {
  const removed = prev.find((i) => i.productId === productId);
  if (removed) {
    removeFromCart({
      items: [{
        item_id: String(removed.productId),
        item_name: removed.name,
        price: removed.unitPrice,
        quantity: removed.quantity,
      }],
    });
  }
  return prev.filter((i) => i.productId !== productId);
});
```

For `setQuantity`, optionally fire `add_to_cart` / `remove_from_cart` with
the quantity **delta** (GA4 convention); if skipped, cart totals still
reconcile at `begin_checkout`.

### 4. `begin_checkout` — checkout submit

The checkout page is being built on another branch; wherever its submit
handler creates the Stripe Checkout session (expected
`app/checkout/page.tsx` or its client form component), add before the
redirect:

```tsx
import { beginCheckout } from "@/lib/analytics";
import { useCart } from "@/components/cart/cart-context";

// inside the submit handler, before redirecting to Stripe:
beginCheckout({
  value: subtotal,
  items: items.map((i) => ({
    item_id: String(i.productId),
    item_name: i.name,
    price: i.unitPrice,
    quantity: i.quantity,
  })),
});
```

### 5. `purchase` — Stripe webhook (server-side → Measurement Protocol)

The authoritative purchase completion is `app/api/webhooks/stripe/route.ts`
(`checkout.session.completed`) — **server-side, no `window`, no dataLayer**.
The correct path is the **GA4 Measurement Protocol**. Env placeholders
already exist: `GA4_MEASUREMENT_ID` (G-XXXXXXX) and `GA4_API_SECRET`
(GA4 Admin → Data Streams → Measurement Protocol API secrets); set the
secret via `npx wrangler secret put GA4_API_SECRET` in production.

Add a helper (suggested `lib/analytics-server.ts`) and call it after the
order insert succeeds — fire-and-forget, never fail the webhook on it:

```ts
export async function sendPurchaseToGA4(params: {
  clientId: string;          // see attribution note below
  transactionId: string;     // order id or paymentIntentId (dedupe key)
  value: number;             // session.amount_total / 100
  currency: string;          // session.currency?.toUpperCase() ?? "USD"
  items: { item_id: string; item_name?: string; price: number; quantity: number }[];
}): Promise<void> {
  const id = process.env.GA4_MEASUREMENT_ID;
  const secret = process.env.GA4_API_SECRET;
  if (!id || !secret) return; // not configured — silent no-op
  try {
    await fetch(
      `https://www.google-analytics.com/mp/collect?measurement_id=${id}&api_secret=${secret}`,
      {
        method: "POST",
        body: JSON.stringify({
          client_id: params.clientId,
          events: [{
            name: "purchase",
            params: {
              transaction_id: params.transactionId,
              value: params.value,
              currency: params.currency,
              items: params.items,
            },
          }],
        }),
      },
    );
  } catch (err) {
    console.warn("ga4-mp: purchase event failed", err);
  }
}
```

Notes:
- `client_id` should be the visitor's GA client id (`_ga` cookie value,
  format `GAx.y.<client_id>`), captured at checkout and passed through
  Stripe session metadata (one extra key, e.g. `ga_cid`). Fallback:
  a random `<timestamp>.<random>` string (event records but won't join
  the session).
- Dedupe: if a client-side thank-you-page `purchase()` beacon is ever
  added, GA4 dedupes on `transaction_id` only within the same client_id
  — prefer **one** source of truth (the webhook).
- Meta CAPI (server-side pixel purchase) is the analogous path for the
  Pixel; out of scope for the skeleton, note it needs `fbclid`/`fbp`
  from the attribution cookie.

## Cloudflare Web Analytics — enabling

1. Cloudflare dashboard → **Analytics & Logs → Web Analytics → Add a site**
   (choose the manual/JS-snippet install, not automatic injection, since
   the site is a Worker).
2. Copy the token from the offered snippet
   (`data-cf-beacon='{"token": "…"}'`).
3. Set `NEXT_PUBLIC_CF_BEACON_TOKEN=<token>` (Workers env var / `.env.local`).
4. `components/analytics/cf-analytics.tsx` (already mounted via
   `<Analytics/>`) renders the beacon script automatically.

It is cookieless and stores nothing client-side, so it loads without
consent. If legal review prefers it gated anyway, add the same
`useConsent() === "granted"` check used in `gtm.tsx`.

## Attribution — attaching first-touch data to orders

`ff_attrib` (30-day first-party cookie) holds
`utm_source/medium/campaign/term/content`, `gclid`, `fbclid`,
`landing_page`, `first_touch_at`. Read it client-side with
`getAttribution()` from `lib/attribution.ts`.

Post-merge wiring:

1. **Checkout**: when creating the Stripe session, read
   `getAttribution()` in the client (or parse the cookie server-side from
   the request in the checkout API route — it's a normal first-party
   cookie) and put a compact JSON string into session metadata, e.g.
   `metadata.attrib` (stay under Stripe's 500-char value limit — the
   cookie is capped well below that).
2. **Webhook**: in `app/api/webhooks/stripe/route.ts`, read
   `session.metadata.attrib` and persist it on the order (either a new
   `orders.attributionJson` column via a migration, or fold it into an
   existing JSON column). This gives per-order channel reporting in
   `/admin` without any third-party dependency.

## GTM container checklist (dashboard side, no code)

- Create GA4 Configuration tag (your `G-…` id), trigger: Initialization.
- Enable **consent overview** in container settings; all Google tags
  honor Consent Mode v2 automatically (defaults are pushed as `denied`
  by `gtm.tsx` before the container loads).
- Create GA4 event tags for `view_item`, `view_item_list`, `add_to_cart`,
  `remove_from_cart`, `begin_checkout` with "Send Ecommerce data →
  Data Layer" enabled, each triggered by its matching Custom Event.
- History-change trigger if SPA pageviews should be tracked as pageviews.

## Test matrix (already verified for the skeleton)

- No env IDs → HTML contains no `googletagmanager`, `fbevents`, or
  `cloudflareinsights` references; banner shows on first visit.
- `NEXT_PUBLIC_GTM_ID` set but no consent cookie → still no GTM script.
- Consent accepted (`ff_consent=granted`) → GTM inline snippet present
  with Consent Mode default(denied)+update(granted) before the loader.
- `ff_consent=denied` → banner hidden, still zero marketing scripts.
