/**
 * Single source of truth for company facts used across content pages
 * (/about, /shipping, /returns, /privacy, /terms, /contact, /faq).
 *
 * Anything marked TODO is a placeholder — confirm the real value with
 * the operator before launch.
 */

export const siteConfig = {
  /** Legal entity name — used in Privacy, Terms, and the footer copyright. */
  companyName: "Foma Family LLC",

  /** Customer-facing brand name. */
  brand: "FomaFamily",

  /** Workshop / return address. */
  address: {
    street: "390 Winkler Dr Suite D",
    city: "Alpharetta",
    state: "GA",
    zip: "30004",
  },

  /** TODO: confirm the real customer-service phone number before launch. */
  phone: "(555) 555-0100",

  /**
   * Customer-service email.
   * TODO: confirm this inbox exists and is monitored before launch.
   */
  email: "hello@fomafamilyllc.com",

  /** Social profiles. TODO: replace with real handles before launch. */
  social: {
    etsy: "https://www.etsy.com/shop/FomaFamily", // TODO: confirm shop URL
    instagram: "https://www.instagram.com/fomafamily", // TODO: confirm handle
    facebook: "https://www.facebook.com/fomafamily", // TODO: confirm page
  },

  /** How long we take to make an order before it ships. */
  productionTime: "1-2 business days",

  /** Typical carrier transit time once an order ships. */
  shippingEstimate: "3-5 business days",

  /** Days a customer has to report a problem with an order. */
  returnsWindowDays: 30,

  /**
   * Order subtotal (USD) that qualifies for free shipping.
   * TODO: placeholder — confirm the real threshold (or remove the offer).
   */
  freeShippingThreshold: 75,
} as const;

export type SiteConfig = typeof siteConfig;

/** "390 Winkler Dr Suite D, Alpharetta, GA 30004" */
export function formatAddress(): string {
  const a = siteConfig.address;
  return `${a.street}, ${a.city}, ${a.state} ${a.zip}`;
}
