"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useCart } from "@/components/cart/cart-context";
import { CustomizationSummary } from "@/components/cart/customization-summary";
import { Tag } from "@/components/price-tag";
import {
  shippingAddressSchema,
  toFieldErrors,
  US_STATES,
  type AddressFieldErrors,
  type ShippingAddress,
} from "@/lib/cart-checkout";
import { deliveryEstimateLabel } from "@/lib/cart-delivery";
import { PAYMENTS_ENABLED } from "@/lib/flags";
import { formatPrice } from "@/lib/format";

type FormState = Record<keyof ShippingAddress, string>;

const EMPTY_FORM: FormState = {
  name: "",
  email: "",
  phone: "",
  address1: "",
  address2: "",
  city: "",
  state: "",
  zip: "",
};

const inputClass = (hasError: boolean) =>
  `w-full rounded-md border bg-white px-3 py-2 text-sm text-ink outline-none transition-colors ` +
  (hasError
    ? "border-amber focus:border-amber"
    : "border-line focus:border-pine");

function Field({
  label,
  name,
  error,
  children,
  optional = false,
}: {
  label: string;
  name: string;
  error?: string;
  children: React.ReactNode;
  optional?: boolean;
}) {
  return (
    <div>
      <label htmlFor={`co-${name}`} className="mb-1 block text-xs font-medium text-ink/70">
        {label}
        {optional && <span className="ml-1 font-normal text-ink/40">(optional)</span>}
      </label>
      {children}
      {error && (
        <p id={`co-${name}-error`} role="alert" className="mt-1 text-xs text-amber">
          {error}
        </p>
      )}
    </div>
  );
}

export function CheckoutClient() {
  const { items, subtotal } = useCart();
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [errors, setErrors] = useState<AddressFieldErrors>({});
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);

  // Client-only date math — keeps SSR HTML clock-independent.
  const [deliveryLabel, setDeliveryLabel] = useState<string | null>(null);
  const [hydrated, setHydrated] = useState(false);
  useEffect(() => {
    setDeliveryLabel(deliveryEstimateLabel());
    setHydrated(true);
  }, []);

  function setField(name: keyof ShippingAddress, value: string) {
    setForm((f) => ({ ...f, [name]: value }));
    // Clear the field's error as soon as the visitor starts fixing it.
    setErrors((e) => (e[name] ? { ...e, [name]: undefined } : e));
  }

  function validateField(name: keyof ShippingAddress) {
    const result = shippingAddressSchema.safeParse(form);
    if (!result.success) {
      const fieldErrors = toFieldErrors(result.error);
      setErrors((e) => ({ ...e, [name]: fieldErrors[name] }));
    }
  }

  /**
   * Fully-written submit path, gated by PAYMENTS_ENABLED (lib/flags.ts):
   * while the Stripe account is pending real keys nothing fires, but the
   * moment the flag flips this posts the cart + validated address to
   * /api/checkout and follows the returned Stripe Checkout URL.
   */
  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setSubmitError(null);

    const result = shippingAddressSchema.safeParse(form);
    if (!result.success) {
      setErrors(toFieldErrors(result.error));
      return;
    }
    setErrors({});

    // TODO(abandoned-cart): the visitor has now given us a verified email —
    // this is the second attach point (with cart-context's persist effect)
    // for abandoned-cart groundwork. Snapshot {email: result.data.email,
    // cart items} to the future server-side cart (migrations/drafts/carts.sql)
    // so a scheduled job can send the Resend reminder via lib/email.ts if
    // the visitor never completes payment.

    if (!PAYMENTS_ENABLED) return; // hard gate — no request while payments are off

    setSubmitting(true);
    try {
      const res = await fetch("/api/checkout", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          items: items.map(({ productId, quantity, customizationData }) => ({
            productId,
            quantity,
            ...(customizationData !== undefined ? { customizationData } : {}),
          })),
          address: result.data,
        }),
      });
      const data = (await res.json().catch(() => null)) as
        | { url?: string; error?: string }
        | null;
      if (!res.ok || !data?.url) {
        setSubmitError(data?.error ?? "Checkout is unavailable right now. Please try again.");
        setSubmitting(false);
        return;
      }
      window.location.href = data.url;
    } catch {
      setSubmitError("Checkout is unavailable right now. Please try again.");
      setSubmitting(false);
    }
  }

  if (hydrated && items.length === 0) {
    return (
      <div className="rounded-md border border-line bg-white p-8 text-center">
        <p className="text-ink/60">Your cart is empty — nothing to check out yet.</p>
        <Link
          href="/sepet"
          className="mt-4 inline-block font-mono text-sm text-pine underline underline-offset-4 hover:text-pine-deep"
        >
          Back to your cart →
        </Link>
      </div>
    );
  }

  return (
    <div className="grid gap-8 lg:grid-cols-[1fr_minmax(0,380px)]">
      {/* ---------------------------------------------- shipping address */}
      <form onSubmit={handleSubmit} noValidate className="rounded-md border border-line bg-white p-6">
        <h2 className="font-display text-lg text-ink">Shipping address</h2>
        <p className="mt-1 text-xs text-ink/50">We currently ship within the United States.</p>

        <div className="mt-5 grid gap-4">
          <Field label="Full name" name="name" error={errors.name}>
            <input
              id="co-name"
              autoComplete="name"
              value={form.name}
              onChange={(e) => setField("name", e.target.value)}
              onBlur={() => validateField("name")}
              aria-invalid={!!errors.name}
              aria-describedby={errors.name ? "co-name-error" : undefined}
              className={inputClass(!!errors.name)}
            />
          </Field>

          <div className="grid gap-4 sm:grid-cols-2">
            <Field label="Email" name="email" error={errors.email}>
              <input
                id="co-email"
                type="email"
                autoComplete="email"
                value={form.email}
                onChange={(e) => setField("email", e.target.value)}
                onBlur={() => validateField("email")}
                aria-invalid={!!errors.email}
                aria-describedby={errors.email ? "co-email-error" : undefined}
                className={inputClass(!!errors.email)}
              />
            </Field>
            <Field label="Phone" name="phone" error={errors.phone}>
              <input
                id="co-phone"
                type="tel"
                autoComplete="tel"
                value={form.phone}
                onChange={(e) => setField("phone", e.target.value)}
                onBlur={() => validateField("phone")}
                aria-invalid={!!errors.phone}
                aria-describedby={errors.phone ? "co-phone-error" : undefined}
                className={inputClass(!!errors.phone)}
              />
            </Field>
          </div>

          <Field label="Street address" name="address1" error={errors.address1}>
            <input
              id="co-address1"
              autoComplete="address-line1"
              value={form.address1}
              onChange={(e) => setField("address1", e.target.value)}
              onBlur={() => validateField("address1")}
              aria-invalid={!!errors.address1}
              aria-describedby={errors.address1 ? "co-address1-error" : undefined}
              className={inputClass(!!errors.address1)}
            />
          </Field>

          <Field label="Apartment, suite, etc." name="address2" error={errors.address2} optional>
            <input
              id="co-address2"
              autoComplete="address-line2"
              value={form.address2}
              onChange={(e) => setField("address2", e.target.value)}
              className={inputClass(!!errors.address2)}
            />
          </Field>

          <div className="grid gap-4 sm:grid-cols-[1fr_auto_auto]">
            <Field label="City" name="city" error={errors.city}>
              <input
                id="co-city"
                autoComplete="address-level2"
                value={form.city}
                onChange={(e) => setField("city", e.target.value)}
                onBlur={() => validateField("city")}
                aria-invalid={!!errors.city}
                aria-describedby={errors.city ? "co-city-error" : undefined}
                className={inputClass(!!errors.city)}
              />
            </Field>
            <Field label="State" name="state" error={errors.state}>
              <select
                id="co-state"
                autoComplete="address-level1"
                value={form.state}
                onChange={(e) => setField("state", e.target.value)}
                onBlur={() => validateField("state")}
                aria-invalid={!!errors.state}
                aria-describedby={errors.state ? "co-state-error" : undefined}
                className={`${inputClass(!!errors.state)} min-w-36`}
              >
                <option value="">Select…</option>
                {US_STATES.map(([code, name]) => (
                  <option key={code} value={code}>
                    {name}
                  </option>
                ))}
              </select>
            </Field>
            <Field label="ZIP" name="zip" error={errors.zip}>
              <input
                id="co-zip"
                inputMode="numeric"
                autoComplete="postal-code"
                value={form.zip}
                onChange={(e) => setField("zip", e.target.value)}
                onBlur={() => validateField("zip")}
                aria-invalid={!!errors.zip}
                aria-describedby={errors.zip ? "co-zip-error" : undefined}
                className={`${inputClass(!!errors.zip)} w-28`}
              />
            </Field>
          </div>
        </div>

        <div className="mt-6 border-t border-dashed border-line pt-5">
          {!PAYMENTS_ENABLED && (
            <p className="mb-3 rounded-md border border-dashed border-amber/60 bg-white/60 px-3 py-2 text-sm text-ink/70">
              <span className="font-medium text-amber">Payment setup in progress</span> — we&apos;re
              putting the finishing touches on secure card payments. Your cart is saved and
              will be right here when we open.
            </p>
          )}
          <button
            type="submit"
            disabled={!PAYMENTS_ENABLED || submitting}
            className="w-full rounded-md bg-pine px-6 py-3 font-medium text-white transition-colors hover:bg-pine-deep disabled:cursor-not-allowed disabled:opacity-50"
          >
            {submitting ? "Heading to payment…" : "Continue to payment"}
          </button>
          {submitError && (
            <p role="alert" className="mt-3 text-sm text-amber">
              {submitError}
            </p>
          )}
        </div>
      </form>

      {/* ------------------------------------------------- order summary */}
      <aside className="h-fit rounded-md border border-line bg-white p-6">
        <h2 className="font-display text-lg text-ink">Order summary</h2>

        <ul className="mt-4 divide-y divide-line/60">
          {items.map((item) => (
            <li key={item.productId} className="flex items-start gap-3 py-3 first:pt-0">
              <div className="h-12 w-12 flex-none overflow-hidden rounded-sm border border-line bg-line/30">
                {item.image ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img src={item.image} alt="" className="h-full w-full object-cover" />
                ) : (
                  <div className="flex h-full w-full items-center justify-center text-[9px] text-ink/40">
                    No photo
                  </div>
                )}
              </div>
              <div className="min-w-0 flex-1">
                <p className="line-clamp-2 text-sm text-ink/90">{item.name}</p>
                <CustomizationSummary data={item.customizationData} className="mt-1" />
                <p className="mt-0.5 font-mono text-xs text-ink/50">
                  Qty {item.quantity}
                </p>
              </div>
              <span className="font-mono text-sm text-ink">
                {formatPrice(item.unitPrice * item.quantity)}
              </span>
            </li>
          ))}
        </ul>

        <dl className="mt-4 space-y-1.5 border-t border-dashed border-line pt-4 text-sm">
          <div className="flex justify-between">
            <dt className="text-ink/70">Subtotal</dt>
            <dd className="font-mono text-ink">{formatPrice(subtotal)}</dd>
          </div>
          <div className="flex justify-between">
            <dt className="text-ink/70">Estimated shipping</dt>
            <dd className="font-mono text-ink/50">Calculated at payment</dd>
          </div>
          <div className="flex justify-between">
            <dt className="text-ink/70">Taxes</dt>
            <dd className="font-mono text-ink/50">Calculated at payment</dd>
          </div>
          <div className="flex justify-between border-t border-line pt-2 text-base">
            <dt className="text-ink">Total (before shipping &amp; tax)</dt>
            <dd className="font-mono text-ink">{formatPrice(subtotal)}</dd>
          </div>
        </dl>

        {deliveryLabel && (
          <div className="mt-4">
            <Tag tone="pine">{deliveryLabel}</Tag>
          </div>
        )}
        <p className="mt-3 text-xs text-ink/50">
          Every piece is made to order in our workshop — production takes 1–2
          business days before it ships.
        </p>
      </aside>
    </div>
  );
}
