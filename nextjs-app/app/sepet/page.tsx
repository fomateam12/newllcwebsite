import type { Metadata } from "next";
import { CartView } from "@/components/cart/cart-view";

export const metadata: Metadata = { title: "Your cart" };

type Props = { searchParams: Promise<{ status?: string }> };

export default async function CartPage({ searchParams }: Props) {
  const { status } = await searchParams;
  return (
    <div className="mx-auto max-w-4xl px-5 py-8">
      <h1 className="mb-6 font-display text-2xl text-ink sm:text-3xl">Your cart</h1>
      <CartView status={status} />
    </div>
  );
}
