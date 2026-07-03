import sanitizeHtml from "sanitize-html";
import { descriptionToSafeHtml } from "./html";

/**
 * Turn a legacy (HTML-escaped) OpenCart product description into plain
 * text suitable for a meta description: decode + sanitize via
 * descriptionToSafeHtml, drop all tags, collapse whitespace, and trim
 * to ~maxLength characters at a word boundary.
 */
export function descriptionToPlainText(raw: string, maxLength = 155): string {
  // Reuse the battle-tested decode + sanitize pipeline, then strip all tags.
  const safe = descriptionToSafeHtml(raw);
  const text = sanitizeHtml(safe.replace(/<(br|\/p|\/li|\/h[2-4]|\/tr|\/div)[^>]*>/gi, " "), {
    allowedTags: [],
    allowedAttributes: {},
  })
    // sanitize-html re-encodes text entities; decode the common ones back.
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/\s+/g, " ")
    .trim();

  if (text.length <= maxLength) return text;
  const cut = text.slice(0, maxLength);
  const lastSpace = cut.lastIndexOf(" ");
  return `${cut.slice(0, lastSpace > 80 ? lastSpace : maxLength).replace(/[\s,;:.!-]+$/, "")}…`;
}
