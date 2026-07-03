-- DRAFT — not applied; future server-side cart.
--
-- Today the cart lives in a compact client cookie (lib/cart-cookie.ts),
-- which caps payload size at ~4 KB and keeps personalization text trimmed.
-- This table is the planned replacement: the cookie shrinks to a single
-- opaque token and the full cart payload moves server-side (D1), which
-- also unlocks abandoned-cart emails (see TODO(abandoned-cart) markers in
-- components/cart/cart-context.tsx and app/checkout/checkout-client.tsx).
--
-- Intentionally kept OUT of the numbered migration sequence. When it ships,
-- copy it to migrations/000N_carts.sql and apply via wrangler d1 execute.

CREATE TABLE IF NOT EXISTS carts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    -- random 128-bit token stored in the visitor's cookie; the only lookup key
    cookie_token TEXT NOT NULL UNIQUE,
    -- full cart JSON: [{ productId, quantity, customizationData? }]
    payload_json TEXT NOT NULL DEFAULT '[]',
    -- captured at checkout; enables the abandoned-cart Resend reminder
    email TEXT,
    -- set once the reminder is sent so it never fires twice
    reminder_sent_at TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_carts_updated_at ON carts (updated_at);
CREATE INDEX IF NOT EXISTS idx_carts_email ON carts (email);
