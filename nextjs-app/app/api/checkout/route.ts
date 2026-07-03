import { inArray } from "drizzle-orm";
import { NextRequest, NextResponse } from "next/server";
import type Stripe from "stripe";
import { encodeItemsToMetadata, type MetadataOrderItem } from "@/lib/checkout-metadata";
import { getDb, schema } from "@/lib/db";
import { getStripe } from "@/lib/stripe";

export const dynamic = "force-dynamic";

type CartItemInput = {
  productId: number;
  quantity: number;
  customizationData?: unknown;
};

const MAX_ITEMS = 30;
const MAX_QTY = 99;

function parseItems(body: unknown): CartItemInput[] | null {
  const raw = Array.isArray(body)
    ? body
    : body && typeof body === "object" && Array.isArray((body as { items?: unknown }).items)
      ? (body as { items: unknown[] }).items
      : null;
  if (!raw || raw.length === 0 || raw.length > MAX_ITEMS) return null;

  const items: CartItemInput[] = [];
  for (const entry of raw) {
    if (typeof entry !== "object" || entry === null) return null;
    const { productId, quantity, customizationData } = entry as CartItemInput;
    if (!Number.isInteger(productId) || productId < 1) return null;
    if (!Number.isInteger(quantity) || quantity < 1 || quantity > MAX_QTY) return null;
    items.push({ productId, quantity, customizationData });
  }
  return items;
}

export async function POST(request: NextRequest) {
  let body: unknown;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ error: "Invalid JSON body" }, { status: 400 });
  }

  const items = parseItems(body);
  if (!items) {
    return NextResponse.json(
      { error: "Expected { items: [{ productId, quantity, customizationData? }] }" },
      { status: 400 },
    );
  }

  // Server-side truth: fetch products from D1; never trust client prices.
  const db = getDb();
  const ids = [...new Set(items.map((i) => i.productId))];
  const products = await db
    .select()
    .from(schema.products)
    .where(inArray(schema.products.id, ids));
  const byId = new Map(products.map((p) => [p.id, p]));

  for (const item of items) {
    const product = byId.get(item.productId);
    if (!product || product.status !== "active") {
      return NextResponse.json(
        { error: `Product ${item.productId} is not available` },
        { status: 400 },
      );
    }
  }

  const metadataItems: MetadataOrderItem[] = [];
  const lineItems: Stripe.Checkout.SessionCreateParams.LineItem[] = [];
  for (const item of items) {
    const product = byId.get(item.productId)!;
    const unitAmountCents = Math.round(product.basePrice * 100);
    metadataItems.push({
      p: product.id,
      q: item.quantity,
      u: unitAmountCents,
      ...(item.customizationData !== undefined
        ? { c: JSON.stringify(item.customizationData) }
        : {}),
    });
    lineItems.push({
      quantity: item.quantity,
      price_data: {
        currency: "usd",
        unit_amount: unitAmountCents,
        product_data: {
          name: product.name,
          ...(product.sku ? { metadata: { sku: product.sku } } : {}),
        },
      },
    });
  }

  const origin = request.nextUrl.origin;
  let session: Stripe.Checkout.Session;
  try {
    session = await getStripe().checkout.sessions.create({
      mode: "payment",
      line_items: lineItems,
      success_url: `${origin}/sepet?status=success`,
      cancel_url: `${origin}/sepet?status=cancelled`,
      shipping_address_collection: { allowed_countries: ["US"] },
      metadata: encodeItemsToMetadata(metadataItems),
    });
  } catch (err) {
    console.error("checkout: Stripe session creation failed", err);
    return NextResponse.json(
      { error: "Checkout is unavailable right now. Please try again." },
      { status: 502 },
    );
  }

  if (!session.url) {
    return NextResponse.json(
      { error: "Stripe did not return a checkout URL" },
      { status: 502 },
    );
  }
  return NextResponse.json({ url: session.url });
}
