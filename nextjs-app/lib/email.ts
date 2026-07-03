import { Resend } from "resend";
import { formatPrice } from "./catalog";
import { getSiteUrl } from "./site";

/**
 * Order-confirmation email via Resend.
 *
 * NOT wired to any flow yet — exported for the checkout branch to call
 * once orders are created. If RESEND_API_KEY is unset the function
 * no-ops with a console.warn and never throws, so a missing key can
 * never break order placement.
 */

export type OrderConfirmationItem = {
  name: string;
  quantity: number;
  /** Per-unit price in USD. */
  unitPrice: number;
  /** Human-readable summary of the buyer's personalization, e.g. "Text: Emma · Font: Script". */
  customization?: string | null;
};

export type OrderConfirmationAddress = {
  name?: string | null;
  line1: string;
  line2?: string | null;
  city: string;
  state?: string | null;
  postalCode: string;
  country: string;
};

export type OrderConfirmation = {
  /** Recipient email address. */
  to: string;
  customerName?: string | null;
  orderNumber: string | number;
  items: OrderConfirmationItem[];
  subtotal: number;
  total: number;
  shippingAddress?: OrderConfirmationAddress | null;
};

export type SendResult =
  | { sent: true; id: string | null }
  | { sent: false; reason: string };

/** Escape user-supplied strings before interpolating into HTML. */
function esc(value: string): string {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function addressHtml(a: OrderConfirmationAddress): string {
  const lines = [a.name, a.line1, a.line2, [a.city, a.state, a.postalCode].filter(Boolean).join(", "), a.country]
    .filter((l): l is string => Boolean(l && l.trim()))
    .map((l) => esc(l));
  return lines.join("<br />");
}

export function renderOrderConfirmationHtml(order: OrderConfirmation): string {
  const siteUrl = getSiteUrl();
  const greetingName = order.customerName?.trim();
  const rows = order.items
    .map(
      (item) => `
        <tr>
          <td style="padding:10px 0;border-bottom:1px solid #e7e2d9;">
            <div style="font-weight:600;color:#1f2a24;">${esc(item.name)}</div>
            ${item.customization ? `<div style="font-size:13px;color:#6b7268;margin-top:2px;">${esc(item.customization)}</div>` : ""}
          </td>
          <td style="padding:10px 0;border-bottom:1px solid #e7e2d9;text-align:center;color:#1f2a24;white-space:nowrap;">× ${item.quantity}</td>
          <td style="padding:10px 0;border-bottom:1px solid #e7e2d9;text-align:right;color:#1f2a24;white-space:nowrap;">${esc(formatPrice(item.unitPrice * item.quantity))}</td>
        </tr>`,
    )
    .join("");

  return `
  <div style="background:#faf7f2;padding:24px 12px;font-family:Georgia,'Times New Roman',serif;color:#1f2a24;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e7e2d9;border-radius:8px;overflow:hidden;">
      <div style="padding:24px 28px;border-bottom:1px solid #e7e2d9;">
        <div style="font-size:22px;font-weight:700;color:#1e4636;">FomaFamily</div>
        <div style="font-size:13px;color:#6b7268;margin-top:2px;">Made to order · engraved &amp; printed in our workshop</div>
      </div>
      <div style="padding:28px;">
        <h1 style="margin:0 0 8px;font-size:20px;color:#1f2a24;">Thank you for your order${greetingName ? `, ${esc(greetingName)}` : ""}!</h1>
        <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#4a5148;">
          We've received order <strong>#${esc(String(order.orderNumber))}</strong> and our workshop is getting
          ready to make it just for you. Personalized pieces are made to order,
          so we'll email you again as soon as yours ships.
        </p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
          <thead>
            <tr>
              <th align="left" style="padding:0 0 8px;border-bottom:2px solid #1e4636;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#6b7268;">Item</th>
              <th align="center" style="padding:0 0 8px;border-bottom:2px solid #1e4636;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#6b7268;">Qty</th>
              <th align="right" style="padding:0 0 8px;border-bottom:2px solid #1e4636;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#6b7268;">Price</th>
            </tr>
          </thead>
          <tbody>${rows}
          </tbody>
        </table>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;margin-top:12px;">
          <tr>
            <td style="padding:4px 0;color:#6b7268;">Subtotal</td>
            <td style="padding:4px 0;text-align:right;color:#1f2a24;">${esc(formatPrice(order.subtotal))}</td>
          </tr>
          <tr>
            <td style="padding:8px 0;font-weight:700;color:#1f2a24;border-top:1px solid #e7e2d9;">Total</td>
            <td style="padding:8px 0;text-align:right;font-weight:700;color:#1e4636;border-top:1px solid #e7e2d9;">${esc(formatPrice(order.total))}</td>
          </tr>
        </table>
        ${
          order.shippingAddress
            ? `<div style="margin-top:24px;padding:16px;background:#faf7f2;border:1px solid #e7e2d9;border-radius:6px;">
                <div style="font-size:12px;text-transform:uppercase;letter-spacing:0.05em;color:#6b7268;margin-bottom:6px;">Shipping to</div>
                <div style="font-size:14px;line-height:1.5;color:#1f2a24;">${addressHtml(order.shippingAddress)}</div>
              </div>`
            : ""
        }
        <p style="margin:24px 0 0;font-size:14px;line-height:1.6;color:#4a5148;">
          Questions about your order? Just reply to this email — a real
          person in our family workshop will get back to you.
        </p>
        <p style="margin:16px 0 0;font-size:14px;line-height:1.6;color:#4a5148;">
          Warmly,<br />
          <strong>The FomaFamily team</strong>
        </p>
      </div>
      <div style="padding:16px 28px;border-top:1px solid #e7e2d9;font-size:12px;color:#6b7268;">
        Foma Family LLC · <a href="${siteUrl}" style="color:#1e4636;">${esc(siteUrl.replace(/^https?:\/\//, ""))}</a>
      </div>
    </div>
  </div>`;
}

const DEFAULT_FROM = "FomaFamily <orders@fomafamilyllc.com>";

/**
 * Send the order-confirmation email. No-ops (console.warn) when
 * RESEND_API_KEY is unset; catches and logs send failures instead of
 * throwing so email problems can never break order placement.
 */
export async function sendOrderConfirmation(order: OrderConfirmation): Promise<SendResult> {
  const apiKey = process.env.RESEND_API_KEY;
  if (!apiKey) {
    console.warn(
      `[email] RESEND_API_KEY is not set — skipping order confirmation for order #${order.orderNumber}`,
    );
    return { sent: false, reason: "missing_api_key" };
  }

  try {
    const resend = new Resend(apiKey);
    const { data, error } = await resend.emails.send({
      from: process.env.RESEND_FROM_EMAIL || DEFAULT_FROM,
      to: order.to,
      subject: `Your FomaFamily order #${order.orderNumber} is confirmed`,
      html: renderOrderConfirmationHtml(order),
    });
    if (error) {
      console.error(`[email] Resend error for order #${order.orderNumber}:`, error);
      return { sent: false, reason: error.message ?? "resend_error" };
    }
    return { sent: true, id: data?.id ?? null };
  } catch (err) {
    console.error(`[email] Failed to send confirmation for order #${order.orderNumber}:`, err);
    return { sent: false, reason: err instanceof Error ? err.message : "unknown_error" };
  }
}
