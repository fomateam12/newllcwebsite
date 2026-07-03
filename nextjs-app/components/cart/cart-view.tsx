"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { Tag } from "@/components/price-tag";
import { formatPrice } from "@/lib/format";
import { useCart } from "./cart-context";

export function CartView({ status }: { status?: string }) {
  const { items, subtotal, setQuantity, removeItem, clear } = useCart();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Back from a successful Stripe Checkout: the order is recorded by the
  // webhook; clear the local cart.
  useEffect(() => {
    if (status === "success") clear();
  }, [status, clear]);

  async function checkout() {
    setSubmitting(true);
    setError(null);
    try {
      const res = await fetch("/api/checkout", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          items: items.map(({ productId, quantity, customizationData }) => ({
            productId,
            quantity,
            ...(customizationData !== undefined ? { customizationData } : {}),
          })),
        }),
      });
      const data = (await res.json().catch(() => null)) as
        | { url?: string; error?: string }
        | null;
      if (!res.ok || !data?.url) {
        setError(data?.error ?? "Checkout is unavailable right now. Please try again.");
        setSubmitting(false);
        return;
      }
      window.location.href = data.url;
    } catch {
      setError("Checkout is unavailable right now. Please try again.");
      setSubmitting(false);
    }
  }

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
        <div className="rounded-md border border-line bg-white p-8 text-center">
          <p className="text-ink/60">Your cart is empty.</p>
          <Link href="/" className="mt-4 inline-block font-mono text-sm text-pine underline underline-offset-4 hover:text-pine-deep">
            Browse gifts →
          </Link>
        </div>
      ) : (
        <>
          <ul className="divide-y divide-line rounded-md border border-line bg-white">
            {items.map((item) => (
              <li key={item.productId} className="flex items-center gap-4 p-4">
                <div className="h-16 w-16 flex-none overflow-hidden rounded-sm border border-line bg-line/30">
                  {item.image ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={item.image} alt="" className="h-full w-full object-cover" />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center text-[10px] text-ink/40">
                      No photo
                    </div>
                  )}
                </div>
                <div className="min-w-0 flex-1">
                  <Link href={`/urun/${item.slug}`} className="line-clamp-2 text-sm text-ink/90 hover:text-pine">
                    {item.name}
                  </Link>
                  <div className="mt-1">
                    <Tag>{formatPrice(item.unitPrice)}</Tag>
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
                <button
                  type="button"
                  onClick={() => removeItem(item.productId)}
                  className="font-mono text-xs text-ink/40 underline underline-offset-4 hover:text-pine"
                >
                  Remove
                </button>
              </li>
            ))}
          </ul>

          <div className="mt-6 flex flex-wrap items-center justify-between gap-4">
            <p className="text-sm text-ink/70">
              Subtotal{" "}
              <span className="font-mono text-base text-ink">{formatPrice(subtotal)}</span>
              <span className="ml-2 text-xs text-ink/50">Shipping &amp; tax at checkout</span>
            </p>
            <button
              type="button"
              onClick={checkout}
              disabled={submitting}
              className="rounded-md bg-pine px-6 py-3 font-medium text-white transition-colors hover:bg-pine-deep disabled:cursor-wait disabled:opacity-60"
            >
              {submitting ? "Heading to checkout…" : "Checkout"}
            </button>
          </div>
          {error && (
            <p role="alert" className="mt-3 text-sm text-amber">
              {error}
            </p>
          )}
        </>
      )}
    </div>
  );
}
