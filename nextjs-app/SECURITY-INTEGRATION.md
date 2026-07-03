# Security integration guide

What the `backend-hardening` branch adds, the decisions behind it, and the
exact steps other branches / post-merge work need to take. All code lives in
`lib/security.ts`, `lib/rate-limit.ts`, `lib/logging.ts`,
`lib/security-schemas.ts` and `middleware.ts`.

## 1. Security headers (middleware.ts)

Every non-static route now gets:

| Header | Value |
| --- | --- |
| `Content-Security-Policy-Report-Only` | see below |
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` |

CSP (report-only for now):

```
default-src 'self'; script-src 'self' 'unsafe-inline';
style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;
font-src 'self'; connect-src 'self' https://api.stripe.com;
frame-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'
```

### Path to enforcing mode

1. Add a `report-to` / `report-uri` endpoint (a tiny `/api/csp-report` route
   logging via `lib/logging.ts`) OR watch browser consoles during a full
   staging click-through: home → category → product → cart → checkout
   redirect → admin.
2. Kill `'unsafe-inline'` in `script-src`: Next.js supports CSP nonces —
   generate a nonce in middleware, forward it via the `x-nonce` request
   header, set `script-src 'self' 'nonce-...'`. `style-src 'unsafe-inline'`
   has to stay (Tailwind/Next inline style attributes are not nonceable).
3. If Stripe.js is ever loaded on-page (today checkout is redirect-based),
   add `https://js.stripe.com` to `script-src` and `frame-src`.
4. Rename the header to `Content-Security-Policy` in `middleware.ts`
   (one-line change) once a week of reports is clean.

## 2. Rate limiting — evaluation and choice

Interface (in `lib/rate-limit.ts`):

```ts
limit(key, { max, windowSec })  // consume 1 → { allowed, remaining, resetAt }
peek(key,  { max, windowSec })  // read-only check, consumes nothing
reset(key, { max, windowSec })  // clear current window (post-login-success)
```

### Options evaluated

| | (a) Workers `ratelimit` binding | (b) KV counter — **chosen** | (c) Durable Objects |
| --- | --- | --- | --- |
| Window | **10s or 60s only**, fixed in wrangler.toml at deploy time | any (`windowSec`) | any |
| Return value | `{ success }` only — no `remaining` | full result | full result |
| Scope | per-colo | global-ish (eventually consistent, ~60s) | global, exact |
| Storage/cost | none, free | KV free tier is plenty at this traffic | DO requests + duration |
| OpenNext friction | none | none | **must export a custom DO class from the worker entry, which OpenNext generates** — nontrivial config |

(a) is GA (Sept 2025) and would be the pick for a simple "N per minute"
gate, but it cannot express the 15-minute login lockout, can't report
`remaining` (needed for the login UX and logs), and each key is counted
per-datacenter. (c) is exact but the custom-export friction with the
generated `.open-next/worker.js` is exactly what this architecture avoids.
(b) covers every window we need with zero worker-entry changes.

### KV accuracy caveats (accepted)

- Read-modify-write is not atomic: a tight same-millisecond burst can
  undercount. Fine for abuse throttling; never use this for billing.
- Cross-colo convergence takes up to ~60s, so a *distributed* attacker can
  exceed `max` before counters converge. Single-IP keys (our only use
  today) nearly always hit one colo.
- **Fail-open**: if the `RATE_LIMIT` binding is missing or KV errors, the
  request is allowed and `rate_limit_unavailable` is logged. The
  storefront never goes down because KV hiccuped; the admin still has the
  password wall behind it.

### Infra action taken (reversible)

`npx wrangler kv namespace create RATE_LIMIT` was run on 2026-07-03 —
namespace id `cb4edc85761b4404af40928d7df5b9db`, bound as `RATE_LIMIT` in
`wrangler.toml` and typed in `env.d.ts`. It is empty and free; delete with
`npx wrangler kv namespace delete --namespace-id cb4edc85761b4404af40928d7df5b9db`
if the branch is abandoned.

### Wired today

| Surface | Key | Limit | Behavior |
| --- | --- | --- | --- |
| `/api/webhooks/stripe` | `stripe-webhook:<ip>` | 120 / 60s (loose — Stripe retries legitimately) | 429 + `retry-after` |
| Admin login | `admin-login:<ip>` | 5 failures / 15 min | lockout message; correct password also refused while locked |

## 3. `/api/search` integration (route built on another branch)

When the search route lands, add this at the top of its handler — nothing
else is needed:

```ts
import { limit } from "@/lib/rate-limit";
import { apiError, getClientIp } from "@/lib/security";
import { searchQuerySchema } from "@/lib/security-schemas";
import { log } from "@/lib/logging";

export async function GET(request: NextRequest) {
  const ip = getClientIp(request.headers);
  const gate = await limit(`search:${ip}`, { max: 30, windowSec: 60 });
  if (!gate.allowed) {
    log("rate_limit_triggered", { key: `search:${ip}`, max: 30, windowSec: 60, path: "/api/search" });
    return apiError("RATE_LIMITED", "Too many requests", 429, {
      "retry-after": String(gate.resetAt - Math.floor(Date.now() / 1000)),
    });
  }

  const parsed = searchQuerySchema.safeParse({
    q: request.nextUrl.searchParams.get("q") ?? "",
  });
  if (!parsed.success) {
    return apiError("INVALID_QUERY", parsed.error.issues[0].message, 400);
  }
  const { q } = parsed.data; // trimmed, 2–64 chars, no control chars
  // ... search with q ...
}
```

Suggested budget: 30/min per IP (a human types ~1 query every few seconds;
this only stops scrapers). Tune `max` freely — no config change needed.

## 4. Checkout body validation (route untouched by this branch)

`lib/security-schemas.ts` exports `checkoutBodySchema` mirroring the
hand-rolled `parseItems()` in `app/api/checkout/route.ts` (same
MAX_ITEMS=30 / MAX_QTY=99 limits, accepts both a bare array and
`{ items: [...] }`). The one-line integration, whenever that route's owner
wants it:

```ts
const parsed = checkoutBodySchema.safeParse(body);
// replace parseItems(body): items = Array.isArray(parsed.data) ? parsed.data : parsed.data.items
```

## 5. Admin auth design

- **Login**: `/admin/login` (brand-styled). The server action verifies the
  password against `ADMIN_PASSWORD` via constant-time compare (both sides
  SHA-256 hashed first, so length leaks nothing).
- **Session**: stateless token `v1.<expiryUnixSeconds>.<base64url HMAC>`.
  HMAC-SHA256 key is derived from `ADMIN_PASSWORD` with HKDF-SHA256
  (salt `fomafamily-admin-session`, info `v1`) — no session store, and
  rotating the password instantly invalidates every session.
- **Cookie**: `admin_session`, `HttpOnly; Secure; SameSite=Strict;
  Path=/admin; Max-Age=86400` (24h).
- **Middleware**: verifies structure → expiry → HMAC (constant-time) for
  `/admin/:path*`. Unauthenticated GET → 307 to `/admin/login?from=…`;
  non-GET (stale server-action POSTs) → JSON 401. Authenticated GET of
  `/admin/login` bounces to `/admin`. Fail closed when `ADMIN_PASSWORD`
  is unset.
- **Lockout**: 5 failed attempts per IP per 15 min. The check runs
  *before* password verification (`peek`), failures consume (`limit`),
  success clears the window (`reset`).
- **Logout**: button in the admin header (server action clears the cookie).
- **Ops**: the existing `ADMIN_PASSWORD` Workers secret keeps working —
  no secret changes needed at deploy. Local dev uses `.dev.vars`
  (gitignored). Basic Auth is gone; nothing sends `Authorization` anymore.

## 6. Error shape and error page

- API errors: `apiError(code, message, status)` → `{ error: { code,
  message } }`. Wired into the image proxy and Stripe webhook. Messages
  are always client-safe; internals go to structured logs only (the image
  proxy has a top-level catch → generic `INTERNAL_ERROR` 500).
- `app/error.tsx`: branded (paper/pine) client error boundary with a
  "Try again" reset button; shows only the Next.js digest, never the
  error message.
- Current error codes: `RATE_LIMITED`, `WEBHOOK_NOT_CONFIGURED`,
  `MISSING_SIGNATURE`, `INVALID_SIGNATURE`, `ORDER_RECORDING_FAILED`,
  `IMAGE_NOT_FOUND`, `INTERNAL_ERROR`, `UNAUTHORIZED`, `INVALID_QUERY`
  (reserved for search).

## 7. Structured logging

`lib/logging.ts` — `log/logWarn/logError(event, data)` emit one JSON line:
`{"ts","level","event",...data}`. Queryable in Workers Observability by
`event`.

Wired now: `order_created`, `stripe_webhook_received`,
`stripe_webhook_failed`, `stripe_webhook_duplicate`,
`admin_login_success`, `admin_login_failure`, `admin_logout`,
`rate_limit_triggered`, `rate_limit_unavailable`, `image_proxy_failed`.

Remaining integration points (other owners):

- `app/api/checkout/route.ts`: `log("checkout_session_created", { sessionId, itemCount })`
  after session creation; `logError("checkout_failed", …)` in the catch.
- `/api/search` (when it lands): `log("search_performed", { qLength, resultCount })`
  — log the length, not the query, to keep PII out.
- `lib/email.ts`: `log("order_email_sent" | "order_email_failed", { orderId })`.

## 8. Known gaps / follow-ups

- CSP is report-only and `script-src` still allows `'unsafe-inline'`
  (nonce work is step 2 of the enforcing path above).
- Rate limiting is per-IP only; no per-account or global budget.
- The login lockout fails open if KV is down (deliberate trade-off — the
  constant-time password check is still the real wall).
- Admin session is single-password/single-role; per-user accounts and
  audit trails are a future project.
- `X-Frame-Options`/CSP are set by middleware, so responses served
  entirely from `.open-next/assets` (static files matched before the
  worker runs) don't carry them — acceptable, those are public assets.
