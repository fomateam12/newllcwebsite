import Stripe from "stripe";

let client: Stripe | null = null;

/**
 * Lazy per-isolate Stripe client. Reads STRIPE_SECRET_KEY at call time
 * (never at module scope — env is only guaranteed inside a request on
 * Workers). Uses the fetch HTTP client so it runs on Cloudflare Workers
 * without Node's http module.
 */
export function getStripe(): Stripe {
  const key = process.env.STRIPE_SECRET_KEY;
  if (!key) {
    throw new Error("STRIPE_SECRET_KEY is not set");
  }
  if (!client) {
    client = new Stripe(key, {
      httpClient: Stripe.createFetchHttpClient(),
    });
  }
  return client;
}

/**
 * SubtleCrypto provider for webhook signature verification on Workers.
 * Must be used with `stripe.webhooks.constructEventAsync` (the sync
 * variant relies on synchronous Node crypto and can break on Workers).
 */
export const webCryptoProvider = Stripe.createSubtleCryptoProvider();
