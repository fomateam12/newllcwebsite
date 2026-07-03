import { eq } from "drizzle-orm";
import { NextRequest, NextResponse } from "next/server";
import type Stripe from "stripe";
import { decodeItemsFromMetadata } from "@/lib/checkout-metadata";
import { getDb, schema } from "@/lib/db";
import { getStripe, webCryptoProvider } from "@/lib/stripe";

export const dynamic = "force-dynamic";

export async function POST(request: NextRequest) {
  const secret = process.env.STRIPE_WEBHOOK_SECRET;
  if (!secret) {
    console.error("stripe-webhook: STRIPE_WEBHOOK_SECRET is not set");
    return NextResponse.json({ error: "Webhook not configured" }, { status: 500 });
  }

  const signature = request.headers.get("stripe-signature");
  if (!signature) {
    return NextResponse.json({ error: "Missing stripe-signature header" }, { status: 400 });
  }

  const payload = await request.text();
  let event: Stripe.Event;
  try {
    // Async variant only: sync constructEvent uses Node crypto in a way
    // that can break on Workers; constructEventAsync uses SubtleCrypto.
    event = await getStripe().webhooks.constructEventAsync(
      payload,
      signature,
      secret,
      undefined,
      webCryptoProvider,
    );
  } catch (err) {
    console.warn("stripe-webhook: signature verification failed", err);
    return NextResponse.json({ error: "Invalid signature" }, { status: 400 });
  }

  if (event.type === "checkout.session.completed") {
    try {
      await handleCheckoutCompleted(event.data.object);
    } catch (err) {
      console.error("stripe-webhook: failed to record order", err);
      // Non-2xx so Stripe retries — the handler is idempotent.
      return NextResponse.json({ error: "Order recording failed" }, { status: 500 });
    }
  }

  return NextResponse.json({ received: true });
}

async function handleCheckoutCompleted(session: Stripe.Checkout.Session) {
  const db = getDb();

  const paymentIntentId =
    typeof session.payment_intent === "string"
      ? session.payment_intent
      : session.payment_intent?.id ?? null;

  // Idempotency: Stripe retries deliveries; skip if we already recorded
  // an order for this payment intent.
  if (paymentIntentId) {
    const [existing] = await db
      .select({ id: schema.orders.id })
      .from(schema.orders)
      .where(eq(schema.orders.stripePaymentIntentId, paymentIntentId))
      .limit(1);
    if (existing) {
      console.log(
        `stripe-webhook: order ${existing.id} already exists for ${paymentIntentId}, skipping`,
      );
      return;
    }
  }

  const items = decodeItemsFromMetadata(session.metadata);
  if (!items) {
    console.error(
      `stripe-webhook: session ${session.id} has no decodable items metadata`,
    );
    // Don't retry — the metadata will never change. Surface for manual fix.
    return;
  }

  // Upsert customer by email.
  const details = session.customer_details;
  const email = details?.email?.toLowerCase() ?? null;
  let customerId: number | null = null;
  if (email) {
    const [existingCustomer] = await db
      .select({ id: schema.customers.id })
      .from(schema.customers)
      .where(eq(schema.customers.email, email))
      .limit(1);
    if (existingCustomer) {
      customerId = existingCustomer.id;
    } else {
      const [inserted] = await db
        .insert(schema.customers)
        .values({
          email,
          name: details?.name ?? null,
          phone: details?.phone ?? null,
        })
        .returning({ id: schema.customers.id });
      customerId = inserted.id;
    }
  }

  // Shipping address: newer API surfaces it under collected_information.
  const shipping =
    session.collected_information?.shipping_details ??
    (details?.address ? { name: details.name, address: details.address } : null);

  const [order] = await db
    .insert(schema.orders)
    .values({
      customerId,
      status: "paid",
      subtotal: (session.amount_subtotal ?? 0) / 100,
      total: (session.amount_total ?? 0) / 100,
      stripePaymentIntentId: paymentIntentId,
      shippingAddressJson: shipping ? JSON.stringify(shipping) : null,
    })
    .returning({ id: schema.orders.id });

  for (const item of items) {
    await db.insert(schema.orderItems).values({
      orderId: order.id,
      productId: item.p,
      quantity: item.q,
      unitPrice: item.u / 100,
      customizationDataJson: item.c ?? null,
    });
  }

  console.log(
    `stripe-webhook: recorded order ${order.id} (${items.length} items) for session ${session.id}`,
  );
}
