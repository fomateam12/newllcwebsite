"use client";

/**
 * Buy box: quantity stepper, DiscountMugs-style volume-pricing table
 * (display only — lib/pricing.ts), live personalization summary, and the
 * Add to Cart / Buy Now CTAs. Customization JSON arrives via
 * ProductPurchaseProvider from the "Make it yours" section.
 */

import { AddToCart } from "@/components/add-to-cart";
import { formatPrice } from "@/lib/format";
import { calculatePrice, QUANTITY_TIERS, tierForQuantity } from "@/lib/pricing";
import { useState } from "react";
import { useProductPurchase } from "./purchase-provider";

const MAX_QTY = 99;

type Props = {
  productId: number;
  slug: string;
  name: string;
  basePrice: number;
  image: string | null;
  /** whether the page renders a personalization section to link to */
  hasPersonalization: boolean;
};

export function BuyBox({ productId, slug, name, basePrice, image, hasPersonalization }: Props) {
  const [qty, setQty] = useState(1);
  const { customization } = useProductPurchase();

  const activeTier = tierForQuantity(qty);
  const personalizedTexts = (customization ?? [])
    .filter((v) => v.type === "text" && v.value.trim().length > 0)
    .map((v) => v.value.trim());

  const clampQty = (n: number) => Math.min(MAX_QTY, Math.max(1, Math.floor(n) || 1));

  return (
    <div className="mt-6 space-y-5">
      {/* Volume pricing (display only — checkout charges the base price) */}
      <div>
        <div className="mb-1.5 flex items-baseline justify-between">
          <span className="font-mono text-xs uppercase tracking-wide text-ink/50">
            Volume pricing
          </span>
          <span className="font-mono text-[11px] text-ink/40">per unit</span>
        </div>
        <table className="w-full table-fixed border-collapse overflow-hidden rounded-md border border-line bg-white text-center">
          <thead>
            <tr>
              {QUANTITY_TIERS.map((t) => (
                <th
                  key={t.label}
                  scope="col"
                  className={`border-b border-line px-1 py-1.5 font-mono text-xs font-normal ${
                    t === activeTier ? "bg-pine/10 text-pine" : "text-ink/55"
                  }`}
                >
                  {t.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            <tr>
              {QUANTITY_TIERS.map((t) => (
                <td
                  key={t.label}
                  className={`px-1 py-2 text-sm ${
                    t === activeTier ? "bg-pine/10 font-medium text-pine" : "text-ink/80"
                  }`}
                >
                  {formatPrice(calculatePrice(basePrice, t.minQty))}
                  {t.discount > 0 && (
                    <span className="block font-mono text-[10px] text-amber">
                      save {Math.round(t.discount * 100)}%
                    </span>
                  )}
                </td>
              ))}
            </tr>
          </tbody>
        </table>
        <p className="mt-1.5 text-xs text-ink/50">
          Planning a bulk order (10+)? Tier rates shown for reference —{" "}
          <a href="mailto:info@fomafamilyllc.com" className="text-pine underline underline-offset-2 hover:text-pine-deep">
            contact us
          </a>{" "}
          and we&apos;ll set it up. Checkout charges the standard price.
        </p>
      </div>

      {/* Personalization status */}
      {hasPersonalization && (
        <a
          href="#personalize"
          className="flex items-baseline justify-between gap-3 rounded-md border border-dashed border-amber/60 bg-white/60 px-3 py-2 text-sm hover:border-amber"
        >
          {personalizedTexts.length > 0 ? (
            <>
              <span className="min-w-0 truncate text-ink/80">
                Personalization: <span className="font-medium text-ink">“{personalizedTexts.join("” · “")}”</span>
              </span>
              <span className="flex-none font-mono text-xs text-pine underline underline-offset-2">edit</span>
            </>
          ) : (
            <>
              <span className="text-ink/70">Make it yours — add a name or message, free.</span>
              <span className="flex-none font-mono text-xs text-pine underline underline-offset-2">personalize ↓</span>
            </>
          )}
        </a>
      )}

      {/* Quantity + CTAs */}
      <div className="flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-1 font-mono text-sm">
          <button
            type="button"
            onClick={() => setQty((q) => clampQty(q - 1))}
            aria-label="Decrease quantity"
            className="h-11 w-9 rounded-sm border border-line text-ink/70 hover:border-pine hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
          >
            −
          </button>
          <label className="sr-only" htmlFor="pdp-qty">
            Quantity
          </label>
          <input
            id="pdp-qty"
            type="number"
            inputMode="numeric"
            min={1}
            max={MAX_QTY}
            value={qty}
            onChange={(e) => setQty(clampQty(Number(e.target.value)))}
            className="h-11 w-14 rounded-sm border border-line bg-white text-center text-ink focus:border-pine focus:outline-none focus:ring-1 focus:ring-pine"
          />
          <button
            type="button"
            onClick={() => setQty((q) => clampQty(q + 1))}
            aria-label="Increase quantity"
            className="h-11 w-9 rounded-sm border border-line text-ink/70 hover:border-pine hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
          >
            +
          </button>
        </div>
        <span className="font-mono text-sm text-ink/60" aria-live="polite">
          Subtotal {formatPrice(basePrice * qty)}
        </span>
      </div>

      <AddToCart
        productId={productId}
        slug={slug}
        name={name}
        unitPrice={basePrice}
        image={image}
        quantity={qty}
        // Attach only when the buyer actually entered something (text or a
        // future upload value) — font/color picks alone are meaningless.
        customizationData={
          (customization ?? []).some(
            (v) => (v.type === "text" || v.type === "upload") && v.value.trim().length > 0,
          )
            ? customization ?? undefined
            : undefined
        }
      />
    </div>
  );
}
