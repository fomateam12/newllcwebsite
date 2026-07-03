/**
 * Delivery estimate helpers.
 *
 * Model: made-to-order production takes 1–2 business days, then US shipping
 * takes 3–5 business days. Weekends are skipped for both legs (our workshop
 * and the carriers don't move packages on Sat/Sun). Holidays are NOT modeled
 * yet — acceptable for an estimate labeled "Estimated".
 */

export const PRODUCTION_DAYS = { min: 1, max: 2 } as const;
export const SHIPPING_DAYS = { min: 3, max: 5 } as const;

export type DeliveryEstimate = {
  /** earliest arrival */
  start: Date;
  /** latest arrival */
  end: Date;
};

function isWeekend(d: Date): boolean {
  const day = d.getDay();
  return day === 0 || day === 6;
}

/**
 * Add N business days to a date, skipping Saturdays and Sundays.
 * If `from` falls on a weekend, counting starts from the next Monday.
 */
export function addBusinessDays(from: Date, days: number): Date {
  const d = new Date(from);
  d.setHours(12, 0, 0, 0); // noon — immune to DST edge cases
  let remaining = days;
  while (remaining > 0) {
    d.setDate(d.getDate() + 1);
    if (!isWeekend(d)) remaining--;
  }
  // An order placed on a weekend can't start production until Monday.
  while (isWeekend(d)) d.setDate(d.getDate() + 1);
  return d;
}

/** Compute the estimated delivery window for an order placed `now`. */
export function getDeliveryEstimate(now: Date = new Date()): DeliveryEstimate {
  return {
    start: addBusinessDays(now, PRODUCTION_DAYS.min + SHIPPING_DAYS.min),
    end: addBusinessDays(now, PRODUCTION_DAYS.max + SHIPPING_DAYS.max),
  };
}

const fmt = new Intl.DateTimeFormat("en-US", { month: "short", day: "numeric" });

/** "Jul 10 – Jul 15" (adds the year only when it differs from today's). */
export function formatDeliveryRange(estimate: DeliveryEstimate): string {
  const withYear = new Intl.DateTimeFormat("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
  const crossesYear = estimate.end.getFullYear() !== new Date().getFullYear();
  const f = crossesYear ? withYear : fmt;
  return `${f.format(estimate.start)} – ${f.format(estimate.end)}`;
}

/** Convenience: the full display string used on cart + checkout. */
export function deliveryEstimateLabel(now: Date = new Date()): string {
  return `Estimated delivery: ${formatDeliveryRange(getDeliveryEstimate(now))}`;
}
