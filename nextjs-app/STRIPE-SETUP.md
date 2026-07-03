# Stripe setup — remaining manual steps

The cart + Checkout integration is code-complete and tested locally with
fake keys (webhook signature verified end-to-end against local D1). To go
live with real (test-mode) payments:

## 1. Keys

From the [Stripe dashboard](https://dashboard.stripe.com/test/apikeys)
(test mode):

| Variable | Where to get it | Where to set it |
| --- | --- | --- |
| `STRIPE_SECRET_KEY` | Developers → API keys → Secret key (`sk_test_…`) | `.dev.vars` locally; `wrangler secret put STRIPE_SECRET_KEY` for the deployed Worker |
| `STRIPE_WEBHOOK_SECRET` | Developers → Webhooks → endpoint signing secret (`whsec_…`) | same as above |
| `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY` | Developers → API keys → Publishable key (`pk_test_…`) | `.dev.vars` / Worker var (currently unused — reserved for future embedded elements) |

Never commit real values. `.dev.vars`, `.env` and `.env*.local` are
gitignored; `.env.example` keeps empty placeholders only.

## 2. Webhook endpoint

Dashboard → Developers → Webhooks → Add endpoint:

- URL: `https://<your-domain>/api/webhooks/stripe`
- Events: `checkout.session.completed`
- Copy the signing secret into `STRIPE_WEBHOOK_SECRET`.

For local testing with real Stripe events:

```sh
stripe listen --forward-to localhost:8792/api/webhooks/stripe
# use the whsec_… it prints as STRIPE_WEBHOOK_SECRET in .dev.vars
```

## 3. End-to-end test (test mode)

1. `npm run preview` (or `npx opennextjs-cloudflare build && npx wrangler dev`)
2. Add a product to the cart, open `/sepet`, click **Checkout**.
3. Pay with card `4242 4242 4242 4242`, any future expiry, any CVC,
   any US ZIP.
4. Verify a row lands in `orders` (status `paid`, payment intent id set)
   and `order_items`, and the customer is upserted by email:
   ```sh
   npx wrangler d1 execute fomawebsitedatabase --local \
     --command "SELECT * FROM orders ORDER BY id DESC LIMIT 1"
   ```
5. Re-deliver the webhook from the dashboard — no duplicate order should
   appear (idempotent on `stripe_payment_intent_id`).

## Implementation notes

- Prices are always re-read from D1 in `/api/checkout`; client-sent
  prices are display-only. Unknown or non-`active` products are rejected.
- Order items (incl. customization data) ride in session metadata,
  chunked to respect Stripe's 500-char value / 50-key limits
  (`lib/checkout-metadata.ts`).
- The webhook uses `constructEventAsync` + SubtleCrypto provider — the
  sync variant can break on Cloudflare Workers.
- Success/cancel URLs land on `/sepet?status=success|cancelled`; the
  cart clears itself client-side on success.
- Shipping address collection is enabled for US; the address is stored
  as JSON on the order.
