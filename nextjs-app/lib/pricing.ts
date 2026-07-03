/**
 * Volume-pricing DISPLAY config for the product page.
 *
 * IMPORTANT: these tiers are presentational only. Checkout (app/api/checkout)
 * always charges the server-side base price — nothing in the order flow reads
 * this file. The buy box renders the tier table (DiscountMugs-style) so bulk
 * buyers can see indicative per-unit rates and contact us to arrange a bulk
 * order. When real bulk pricing ships, checkout must re-derive tiers
 * server-side; never trust a client-computed price.
 *
 * Client-safe: pure functions, no server-only imports.
 */

export type QuantityTier = {
  /** inclusive lower bound of the tier */
  minQty: number;
  /** exclusive upper bound (null = open-ended top tier) */
  maxQty: number | null;
  /** human label, e.g. "10–24" */
  label: string;
  /** fraction off the base price, e.g. 0.05 = 5% */
  discount: number;
};

/**
 * Placeholder tier ladder — tune per business reality later.
 * Tiers follow the promo-products convention (1-9 / 10-24 / 25-49 / 50+).
 */
export const QUANTITY_TIERS: QuantityTier[] = [
  { minQty: 1, maxQty: 10, label: "1–9", discount: 0 },
  { minQty: 10, maxQty: 25, label: "10–24", discount: 0.05 },
  { minQty: 25, maxQty: 50, label: "25–49", discount: 0.1 },
  { minQty: 50, maxQty: null, label: "50+", discount: 0.15 },
];

/** The tier a given quantity falls into (qty < 1 clamps to the first tier). */
export function tierForQuantity(qty: number): QuantityTier {
  const q = Math.max(1, Math.floor(qty));
  return (
    QUANTITY_TIERS.find((t) => q >= t.minQty && (t.maxQty === null || q < t.maxQty)) ??
    QUANTITY_TIERS[0]
  );
}

/**
 * Indicative per-unit price at a given quantity, rounded to cents.
 * Display only — see the module header.
 */
export function calculatePrice(basePrice: number, qty: number): number {
  const tier = tierForQuantity(qty);
  return Math.round(basePrice * (1 - tier.discount) * 100) / 100;
}
