import { z } from "zod";
import { ORDER_STATUSES } from "@/lib/order-status";

/**
 * Zod schemas for every externally supplied payload. Parse at the edge of
 * each handler with `schema.safeParse(...)` and reject with a 400 +
 * `apiError(...)` (lib/security.ts) on failure — never let unvalidated
 * input reach the DB or Stripe.
 */

/* eslint-disable no-control-regex */
const CONTROL_CHARS = /[\u0000-\u001f\u007f]/;
/* eslint-enable no-control-regex */

/**
 * Search query — for the upcoming /api/search route (built on another
 * branch; integration instructions in SECURITY-INTEGRATION.md).
 * 2–64 chars, trimmed, no control characters.
 */
export const searchQuerySchema = z.object({
  q: z
    .string()
    .trim()
    .min(2, "Query must be at least 2 characters")
    .max(64, "Query must be at most 64 characters")
    .refine((s) => !CONTROL_CHARS.test(s), "Query contains control characters"),
});
export type SearchQuery = z.infer<typeof searchQuerySchema>;

/**
 * Checkout body — mirrors the hand-rolled parseItems() in
 * app/api/checkout/route.ts (same MAX_ITEMS/MAX_QTY limits). NOT wired
 * into that route (it is owned elsewhere tonight); the one-line
 * integration is documented in SECURITY-INTEGRATION.md.
 */
const checkoutItemSchema = z.object({
  productId: z.number().int().min(1),
  quantity: z.number().int().min(1).max(99),
  customizationData: z.unknown().optional(),
});

export const checkoutBodySchema = z.union([
  z.array(checkoutItemSchema).min(1).max(30),
  z.object({ items: z.array(checkoutItemSchema).min(1).max(30) }),
]);
export type CheckoutBody = z.infer<typeof checkoutBodySchema>;

/**
 * Admin order status update — wired into
 * app/admin/orders/[id]/actions.ts. Status enum comes straight from
 * lib/order-status.ts so the two can never drift.
 */
export const orderStatusUpdateSchema = z.object({
  orderId: z.coerce.number().int().min(1),
  status: z.enum(ORDER_STATUSES),
});
export type OrderStatusUpdate = z.infer<typeof orderStatusUpdateSchema>;

/** Admin login form. Bounded to keep pathological inputs off the hasher. */
export const adminLoginSchema = z.object({
  password: z.string().min(1).max(256),
});
