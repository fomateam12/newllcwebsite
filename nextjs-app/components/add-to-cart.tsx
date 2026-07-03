"use client";

import Link from "next/link";
import { useState } from "react";
import { useCart } from "./cart/cart-context";

type Props = {
  productId: number;
  slug: string;
  name: string;
  unitPrice: number;
  image: string | null;
};

/** Client island for the product page's add-to-cart button. */
export function AddToCart({ productId, slug, name, unitPrice, image }: Props) {
  const { addItem } = useCart();
  const [added, setAdded] = useState(false);

  return (
    <div className="mt-6 flex flex-wrap items-center gap-3">
      <button
        type="button"
        onClick={() => {
          addItem({ productId, slug, name, unitPrice, image });
          setAdded(true);
        }}
        className="w-full rounded-md bg-pine px-6 py-3 font-medium text-white transition-colors hover:bg-pine-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine sm:w-auto"
      >
        {added ? "Added ✓ Add another" : "Add to cart"}
      </button>
      {added && (
        <Link href="/sepet" className="font-mono text-sm text-pine underline underline-offset-4 hover:text-pine-deep">
          View cart →
        </Link>
      )}
    </div>
  );
}
