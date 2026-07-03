"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { Tag } from "@/components/price-tag";
import { deliveryEstimateLabel } from "@/lib/cart-delivery";
import { formatPrice } from "@/lib/format";
import { useCart, type CartItem } from "./cart-context";
import { CustomizationSummary } from "./customization-summary";

/** Real category slugs from the catalog (see migrations/0002_seed_data.sql). */
const SUGGESTED_CATEGORIES: Array<{ slug: string; name: string; blurb: string }> = [
  { slug: "gifts", name: "Gifts", blurb: "Keepsakes for every occasion" },
  { slug: "clothing-and-fashion", name: "Clothing & Fashion", blurb: "Custom tees, hats & totes" },
  { slug: "home-living", name: "Home & Living", blurb: "Blankets, mugs & decor" },
  { slug: "christmas", name: "Christmas", blurb: "Personalized ornaments & more" },
];

function EmptyCart() {
  return (
    <div className="rounded-md border border-line bg-white p-8">
      <div className="text-center">
        <p className="font-display text-xl text-ink">Your cart is empty.</p>
        <p className="mt-2 text-sm text-ink/60">
          Every piece is made to order — start with one of our favorite corners
          of the shop.
        </p>
      </div>
      <ul className="mt-6 grid gap-3 sm:grid-cols-2">
        {SUGGESTED_CATEGORIES.map((cat) => (
          <li key={cat.slug}>
            <Link
              href={`/kategori/${cat.slug}`}
              className="block rounded-md border border-dashed border-pine/40 bg-paper/60 px-4 py-3 transition-colors hover:border-pine hover:bg-white"
            >
              <span className="font-medium text-pine">{cat.name} →</span>
              <span className="mt-0.5 block text-xs text-ink/60">{cat.blurb}</span>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}

function LineItem({ item }: { item: CartItem }) {
  const { setQuantity, removeItem } = useCart();
  const productHref = item.slug ? `/urun/${item.slug}` : null;

  const image = (
    <div className="h-20 w-20 flex-none overflow-hidden rounded-sm border border-line bg-line/30">
      {item.image ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={item.image} alt="" className="h-full w-full object-cover" />
      ) : (
        <div className="flex h-full w-full items-center justify-center text-[10px] text-ink/40">
          No photo
        </div>
      )}
    </div>
  );

  return (
    <li className="flex flex-wrap items-start gap-4 p-4 sm:flex-nowrap">
      {productHref ? <Link href={productHref}>{image}</Link> : image}

      <div className="min-w-0 flex-1">
        {productHref ? (
          <Link href={productHref} className="line-clamp-2 text-sm text-ink/90 hover:text-pine">
            {item.name}
          </Link>
        ) : (
          <span className="line-clamp-2 text-sm text-ink/90">{item.name}</span>
        )}
        <CustomizationSummary data={item.customizationData} className="mt-1.5" />
        <div className="mt-1.5">
          <Tag>{formatPrice(item.unitPrice)} each</Tag>
        </div>
      </div>

      <div className="flex items-center gap-1 font-mono text-sm">
        <button
          type="button"
          onClick={() => setQuantity(item.productId, item.quantity - 1)}
          aria-label={`Decrease quantity of ${item.name}`}
          className="h-7 w-7 rounded-sm border border-line text-ink/70 hover:border-pine hover:text-pine"
        >
          −
        </button>
        <span className="w-8 text-center" aria-live="polite">{item.quantity}</span>
        <button
          type="button"
          onClick={() => setQuantity(item.productId, item.quantity + 1)}
          aria-label={`Increase quantity of ${item.name}`}
          className="h-7 w-7 rounded-sm border border-line text-ink/70 hover:border-pine hover:text-pine"
        >
          +
        </button>
      </div>

      <div className="flex w-24 flex-col items-end gap-1.5">
        <span className="font-mono text-sm text-ink">
          {formatPrice(item.unitPrice * item.quantity)}
        </span>
        <button
          type="button"
          onClick={() => removeItem(item.productId)}
          className="font-mono text-xs text-ink/40 underline underline-offset-4 hover:text-pine"
        >
          Remove
        </button>
      </div>
    </li>
  );
}

export function CartView({ status }: { status?: string }) {
  const { items, subtotal, clear } = useCart();

  // Client-only so the SSR shell never disagrees with the visitor's clock.
  const [deliveryLabel, setDeliveryLabel] = useState<string | null>(null);
  useEffect(() => {
    setDeliveryLabel(deliveryEstimateLabel());
  }, []);

  // Back from a successful Stripe Checkout: the order is recorded by the
  // webhook; clear the local cart (cookie + display cache included).
  useEffect(() => {
    if (status === "success") clear();
  }, [status, clear]);

  if (status === "success") {
    return (
      <div className="rounded-md border border-line bg-white p-8 text-center">
        <Tag tone="pine" className="text-base">Payment received</Tag>
        <h2 className="mt-4 font-display text-2xl text-ink">Thank you for your order!</h2>
        <p className="mt-2 text-sm text-ink/60">
          A confirmation is on its way to your email. We&apos;ll start crafting
          your pieces right away.
        </p>
        <Link href="/" className="mt-6 inline-block rounded-md bg-pine px-6 py-3 font-medium text-white hover:bg-pine-deep">
          Keep browsing
        </Link>
      </div>
    );
  }

  return (
    <div>
      {status === "cancelled" && (
        <p className="mb-4 rounded-md border border-dashed border-amber/60 bg-white/60 px-3 py-2 text-sm text-ink/70">
          Checkout was cancelled — your cart is still here whenever you&apos;re ready.
        </p>
      )}

      {items.length === 0 ? (
        <EmptyCart />
      ) : (
        <>
          <ul className="divide-y divide-line rounded-md border border-line bg-white">
            {items.map((item) => (
              <LineItem key={item.productId} item={item} />
            ))}
          </ul>

          <div className="mt-6 rounded-md border border-line bg-white p-5">
            <div className="flex items-baseline justify-between gap-4">
              <span className="text-sm text-ink/70">
                Subtotal ({items.reduce((n, i) => n + i.quantity, 0)} item
                {items.reduce((n, i) => n + i.quantity, 0) === 1 ? "" : "s"})
              </span>
              <span className="font-mono text-lg text-ink">{formatPrice(subtotal)}</span>
            </div>
            <p className="mt-1 text-xs text-ink/50">
              Shipping &amp; taxes calculated at checkout.
            </p>
            {deliveryLabel && (
              <p className="mt-2 text-xs text-pine">
                <span aria-hidden>⌁ </span>
                {deliveryLabel}
              </p>
            )}
            <Link
              href="/checkout"
              className="mt-4 block w-full rounded-md bg-pine px-6 py-3 text-center font-medium text-white transition-colors hover:bg-pine-deep sm:ml-auto sm:w-auto sm:max-w-xs"
            >
              Proceed to checkout
            </Link>
          </div>
        </>
      )}
    </div>
  );
}
