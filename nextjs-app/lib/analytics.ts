/**
 * GA4 e-commerce dataLayer helpers.
 *
 * Each helper pushes a GA4-schema ecommerce event onto `window.dataLayer`
 * for GTM to pick up. All helpers are safe to call anywhere:
 *  - no-op during SSR (no `window`),
 *  - no-op until the visitor has granted consent (ff_consent cookie),
 *  - no-op is silent, so call sites never need their own guards.
 *
 * These are NOT wired into store components yet — see
 * ANALYTICS-INTEGRATION.md for the exact call sites (single post-merge
 * integration session to avoid cross-branch conflicts).
 */

import { hasAnalyticsConsent } from "./analytics-consent";

declare global {
  interface Window {
    dataLayer?: unknown[];
  }
}

export const DEFAULT_CURRENCY = "USD";

/** One product line in the GA4 `items` array. */
export type AnalyticsItem = {
  /** stable product id — use the D1 `products.id` (stringified) */
  item_id: string;
  item_name: string;
  /** unit price in major currency units (dollars) */
  price?: number;
  quantity?: number;
  item_category?: string;
  item_variant?: string;
  /** position within a list (view_item_list) */
  index?: number;
  item_list_id?: string;
  item_list_name?: string;
};

function pushEcommerce(event: string, ecommerce: Record<string, unknown>): void {
  if (typeof window === "undefined") return;
  if (!hasAnalyticsConsent()) return;
  window.dataLayer = window.dataLayer || [];
  // GA4 guidance: clear the previous ecommerce object so events don't merge.
  window.dataLayer.push({ ecommerce: null });
  window.dataLayer.push({ event, ecommerce });
}

/** Sum of price × quantity, for events where callers don't pass `value`. */
function itemsValue(items: AnalyticsItem[]): number {
  const v = items.reduce((s, i) => s + (i.price ?? 0) * (i.quantity ?? 1), 0);
  return Math.round(v * 100) / 100;
}

/** Product detail page viewed. */
export function viewItem(params: {
  items: AnalyticsItem[];
  currency?: string;
  value?: number;
}): void {
  pushEcommerce("view_item", {
    currency: params.currency ?? DEFAULT_CURRENCY,
    value: params.value ?? itemsValue(params.items),
    items: params.items,
  });
}

/** Category / listing page viewed. */
export function viewItemList(params: {
  items: AnalyticsItem[];
  item_list_id?: string;
  item_list_name?: string;
}): void {
  pushEcommerce("view_item_list", {
    item_list_id: params.item_list_id,
    item_list_name: params.item_list_name,
    items: params.items,
  });
}

/** Item added to the cart. */
export function addToCart(params: {
  items: AnalyticsItem[];
  currency?: string;
  value?: number;
}): void {
  pushEcommerce("add_to_cart", {
    currency: params.currency ?? DEFAULT_CURRENCY,
    value: params.value ?? itemsValue(params.items),
    items: params.items,
  });
}

/** Item removed from the cart (or quantity decreased). */
export function removeFromCart(params: {
  items: AnalyticsItem[];
  currency?: string;
  value?: number;
}): void {
  pushEcommerce("remove_from_cart", {
    currency: params.currency ?? DEFAULT_CURRENCY,
    value: params.value ?? itemsValue(params.items),
    items: params.items,
  });
}

/** Checkout started (submit to Stripe Checkout). */
export function beginCheckout(params: {
  items: AnalyticsItem[];
  currency?: string;
  value?: number;
  coupon?: string;
}): void {
  pushEcommerce("begin_checkout", {
    currency: params.currency ?? DEFAULT_CURRENCY,
    value: params.value ?? itemsValue(params.items),
    coupon: params.coupon,
    items: params.items,
  });
}

/**
 * Purchase completed — client-side variant.
 *
 * NOTE: the authoritative purchase completion for this store happens
 * server-side in the Stripe webhook, where `window` does not exist. The
 * proper path for that is the GA4 Measurement Protocol (documented in
 * ANALYTICS-INTEGRATION.md). This client helper exists for an optional
 * thank-you-page beacon; if both are used, dedupe on `transaction_id`.
 */
export function purchase(params: {
  transaction_id: string;
  items: AnalyticsItem[];
  currency?: string;
  value?: number;
  tax?: number;
  shipping?: number;
  coupon?: string;
}): void {
  pushEcommerce("purchase", {
    transaction_id: params.transaction_id,
    currency: params.currency ?? DEFAULT_CURRENCY,
    value: params.value ?? itemsValue(params.items),
    tax: params.tax,
    shipping: params.shipping,
    coupon: params.coupon,
    items: params.items,
  });
}
