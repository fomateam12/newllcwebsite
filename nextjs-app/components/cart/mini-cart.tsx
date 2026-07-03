"use client";

import Link from "next/link";
import { formatPrice } from "@/lib/format";
import { useCart } from "./cart-context";

const PREVIEW_COUNT = 3;

/**
 * Dropdown cart preview rendered by CartButton. Shows the most recently
 * added items, the subtotal, and the two ways forward.
 */
export function MiniCart({ onNavigate }: { onNavigate: () => void }) {
  const { items, count, subtotal } = useCart();
  const preview = items.slice(-PREVIEW_COUNT).reverse();
  const hiddenCount = count - preview.reduce((n, i) => n + i.quantity, 0);

  return (
    <div
      className="absolute right-0 top-full z-50 mt-2 w-80 rounded-md border border-line bg-white p-4 shadow-lg"
      role="dialog"
      aria-label="Cart preview"
    >
      {items.length === 0 ? (
        <div className="py-2 text-center">
          <p className="text-sm text-ink/60">Your cart is empty.</p>
          <Link
            href="/kategori/gifts"
            onClick={onNavigate}
            className="mt-2 inline-block font-mono text-sm text-pine underline underline-offset-4 hover:text-pine-deep"
          >
            Browse gifts →
          </Link>
        </div>
      ) : (
        <>
          <ul className="divide-y divide-line/60">
            {preview.map((item) => (
              <li key={item.productId} className="flex items-center gap-3 py-2 first:pt-0">
                <div className="h-11 w-11 flex-none overflow-hidden rounded-sm border border-line bg-line/30">
                  {item.image ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={item.image} alt="" className="h-full w-full object-cover" />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center text-[9px] text-ink/40">
                      No photo
                    </div>
                  )}
                </div>
                <div className="min-w-0 flex-1">
                  {item.slug ? (
                    <Link
                      href={`/urun/${item.slug}`}
                      onClick={onNavigate}
                      className="line-clamp-1 text-sm text-ink/90 hover:text-pine"
                    >
                      {item.name}
                    </Link>
                  ) : (
                    <span className="line-clamp-1 text-sm text-ink/90">{item.name}</span>
                  )}
                  <span className="font-mono text-xs text-ink/50">
                    {item.quantity} × {formatPrice(item.unitPrice)}
                  </span>
                </div>
              </li>
            ))}
          </ul>
          {hiddenCount > 0 && (
            <p className="mt-1 font-mono text-xs text-ink/50">
              + {hiddenCount} more item{hiddenCount === 1 ? "" : "s"} in your cart
            </p>
          )}

          <div className="mt-3 flex items-baseline justify-between border-t border-dashed border-line pt-3">
            <span className="text-sm text-ink/70">Subtotal</span>
            <span className="font-mono text-sm text-ink">{formatPrice(subtotal)}</span>
          </div>
          <p className="mt-0.5 text-[11px] text-ink/50">
            Shipping &amp; taxes calculated at checkout.
          </p>

          <div className="mt-3 grid grid-cols-2 gap-2">
            <Link
              href="/sepet"
              onClick={onNavigate}
              className="rounded-md border border-pine px-3 py-2 text-center text-sm font-medium text-pine transition-colors hover:bg-pine/5"
            >
              View cart
            </Link>
            <Link
              href="/checkout"
              onClick={onNavigate}
              className="rounded-md bg-pine px-3 py-2 text-center text-sm font-medium text-white transition-colors hover:bg-pine-deep"
            >
              Checkout
            </Link>
          </div>
        </>
      )}
    </div>
  );
}
