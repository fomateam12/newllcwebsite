/** Canonical order lifecycle statuses, in workflow order. */
export const ORDER_STATUSES = [
  "pending",
  "paid",
  "processing",
  "shipped",
  "delivered",
  "cancelled",
] as const;

export type OrderStatus = (typeof ORDER_STATUSES)[number];

export function isOrderStatus(value: string): value is OrderStatus {
  return (ORDER_STATUSES as readonly string[]).includes(value);
}

/** Tailwind classes for a small status pill, per status. */
export function statusPillClasses(status: string): string {
  switch (status) {
    case "pending":
      return "border-amber/60 bg-amber/10 text-amber";
    case "paid":
    case "processing":
      return "border-pine/50 bg-pine/10 text-pine";
    case "shipped":
    case "delivered":
      return "border-pine-deep/50 bg-pine-deep/10 text-pine-deep";
    case "cancelled":
      return "border-ink/30 bg-ink/5 text-ink/50";
    default:
      return "border-line bg-white text-ink/60";
  }
}
