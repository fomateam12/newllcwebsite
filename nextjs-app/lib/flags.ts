/**
 * Feature flags.
 *
 * NEXT_PUBLIC_* vars are inlined at build time, so these are safe to read
 * from both server and client components.
 */

/**
 * Gates the "Continue to payment" action on /checkout.
 *
 * Default OFF: the Stripe account is still waiting for real keys
 * (see STRIPE-SETUP.md). Flip by setting NEXT_PUBLIC_PAYMENTS_ENABLED=true
 * in the build environment — no code change needed.
 */
export const PAYMENTS_ENABLED =
  process.env.NEXT_PUBLIC_PAYMENTS_ENABLED === "true";
