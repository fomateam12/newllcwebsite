"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { useCart } from "./cart/cart-context";

type Props = {
  productId: number;
  slug: string;
  name: string;
  unitPrice: number;
  image: string | null;
  /** defaults to 1 */
  quantity?: number;
  /**
   * CustomizationPreview output (CustomizationValue[]), stored on the cart
   * item and forwarded to /api/checkout → Stripe metadata → order_items.
   * NOTE: cart-context merges lines by productId only, so re-adding the
   * same product keeps the FIRST add's customizationData (known limitation,
   * documented for the cart owner).
   */
  customizationData?: unknown;
};

/** Client island: "Add to cart" + "Buy now" CTAs for the product page. */
export function AddToCart({
  productId,
  slug,
  name,
  unitPrice,
  image,
  quantity = 1,
  customizationData,
}: Props) {
  const { addItem } = useCart();
  const router = useRouter();
  const [added, setAdded] = useState(false);

  const add = () => {
    addItem(
      {
        productId,
        slug,
        name,
        unitPrice,
        image,
        ...(customizationData !== undefined ? { customizationData } : {}),
      },
      quantity,
    );
  };

  return (
    <div className="flex flex-col gap-3">
      <div className="flex flex-wrap items-center gap-3">
        <button
          type="button"
          onClick={() => {
            add();
            setAdded(true);
          }}
          className="flex-1 rounded-md bg-pine px-6 py-3 font-medium text-white transition-colors hover:bg-pine-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine sm:flex-none"
        >
          {added ? "Added ✓ Add another" : "Add to cart"}
        </button>
        <button
          type="button"
          onClick={() => {
            add();
            router.push("/sepet");
          }}
          className="flex-1 rounded-md border border-pine px-6 py-3 font-medium text-pine transition-colors hover:bg-pine/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine sm:flex-none"
        >
          Buy now
        </button>
      </div>
      {added && (
        <Link
          href="/sepet"
          className="font-mono text-sm text-pine underline underline-offset-4 hover:text-pine-deep"
        >
          View cart →
        </Link>
      )}
    </div>
  );
}
