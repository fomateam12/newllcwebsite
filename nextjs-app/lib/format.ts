/** Client-safe formatting helpers (no server-only imports). */
export function formatPrice(price: number): string {
  return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(price);
}
