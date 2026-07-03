"use client";

import Link from "next/link";
import { useCart } from "./cart-context";

/** Small header cart link with a live item count. */
export function CartButton() {
  const { count } = useCart();
  return (
    <Link
      href="/sepet"
      className="inline-flex items-center gap-1.5 rounded-sm border border-dashed border-pine/50 bg-white/60 px-2 py-0.5 font-mono text-sm text-pine hover:border-pine hover:text-pine-deep"
      aria-label={`Cart, ${count} item${count === 1 ? "" : "s"}`}
    >
      <span aria-hidden className="h-1.5 w-1.5 rounded-full border border-current" />
      Cart
      {count > 0 && <span aria-hidden>({count})</span>}
    </Link>
  );
}
