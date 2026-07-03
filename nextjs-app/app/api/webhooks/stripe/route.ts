import { eq } from "drizzle-orm";
import { NextRequest, NextResponse } from "next/server";
import type Stripe from "stripe";
import { decodeItemsFromMetadata } from "@/lib/checkout-metadata";
import { getDb, schema } from "@/lib/db";
import { log, logError, logWarn } from "@/lib/logging";
import { limit } from "@/lib/rate-limit";
import { apiError, getClientIp } from "@/lib/security";
import { getStripe, webCryptoProvider } from "@/lib/stripe";

export const dynamic = "force-dynamic";

// Loose on purpose: Stripe retries with backoff and legit bursts happen
// (batch payouts, replays from the dashboard). This only stops floods of
// junk POSTs from a single address before signature verification burns CPU.
const WEBHOOK_LIMIT = { max: 120, windowSec: 60 };

export async function POST(request: NextRequest) {
  const ip = getClientIp(request.headers);
  const gate = await limit(`stripe-webhook:${ip}`, WEBHOOK_LIMIT);
  if (!gate.allowed) {
    log("rate_limit_triggered", {
      key: `stripe-webhook:${ip}`,
      ...WEBHOOK_LIMIT,
      path: "/api/webhooks/stripe",
    });
    return apiError("RATE_LIMITED", "Too many requests", 429, {
      "retry-after": String(Math.max(gate.resetAt - Math.floor(Date.now() / 1000), 1)),
    });
  }

  const secret = process.env.STRIPE_WEBHOOK_SECRET;
  if (!secret) {
    logError("stripe_webhook_failed", { reason: "STRIPE_WEBHOOK_SECRET is not set" });
    return apiError("WEBHOOK_NOT_CONFIGURED", "Webhook not configured", 500);
  }

  const signature = request.headers.get("stripe-signature");
  if (!signature) {
    logWarn("stripe_webhook_failed", { reason: "missing stripe-signature header", ip });
    return apiError("MISSING_SIGNATURE", "Missing stripe-signature header", 400);
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
  } catch {
    // Details deliberately not echoed to the caller — logs only.
    logWarn("stripe_webhook_failed", { reason: "signature verification failed", ip });
    return apiError("INVALID_SIGNATURE", "Invalid signature", 400);
  }

  log("stripe_webhook_received", { eventType: event.type, eventId: event.id });

  if (event.type === "checkout.session.completed") {
    try {
      await handleCheckoutCompleted(event.data.object);
    } catch (err) {
      logError("stripe_webhook_failed", {
        reason: "order recording failed",
        eventType: event.type,
        eventId: event.id,
        detail: err instanceof Error ? err.message : String(err),
      });
      // Non-2xx so Stripe retries — the handler is idempotent. Generic
      // message outward; the detail lives in the log line above.
      return apiError("ORDER_RECORDING_FAILED", "Order recording failed", 500);
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
      log("stripe_webhook_duplicate", {
        orderId: existing.id,
        paymentIntentId,
      });
      return;
    }
  }

  const items = decodeItemsFromMetadata(session.metadata);
  if (!items) {
    logError("stripe_webhook_failed", {
      reason: "no decodable items metadata",
      sessionId: session.id,
    });
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

  log("order_created", {
    orderId: order.id,
    sessionId: session.id,
    itemCount: items.length,
    total: (session.amount_total ?? 0) / 100,
  });
}
