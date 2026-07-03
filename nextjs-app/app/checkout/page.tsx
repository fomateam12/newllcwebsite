import type { Metadata } from "next";
import { CheckoutClient } from "./checkout-client";

export const metadata: Metadata = {
  title: "Checkout",
  robots: { index: false },
};

export default function CheckoutPage() {
  return (
    <div className="mx-auto max-w-5xl px-5 py-8">
      <h1 className="mb-6 font-display text-2xl text-ink sm:text-3xl">Checkout</h1>
      <CheckoutClient />
    </div>
  );
}
