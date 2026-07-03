import sanitizeHtml from "sanitize-html";

const NAMED: Record<string, string> = {
  amp: "&", lt: "<", gt: ">", quot: '"', apos: "'", nbsp: " ",
};

function decodeOnce(s: string): string {
  return s.replace(/&([a-zA-Z]+|#\d+|#x[0-9a-fA-F]+);/g, (m, e) => {
    if (e[0] === "#") {
      const code =
        e[1].toLowerCase() === "x" ? parseInt(e.slice(2), 16) : parseInt(e.slice(1), 10);
      return Number.isFinite(code) ? String.fromCodePoint(code) : m;
    }
    return NAMED[e.toLowerCase()] ?? m;
  });
}

/**
 * OpenCart stored product descriptions as HTML-escaped HTML
 * ("&lt;br&gt;…"). Decode the escaping, then sanitize the resulting
 * markup down to a safe, style-free subset before it is rendered with
 * dangerouslySetInnerHTML. Inline styles/classes are stripped on
 * purpose — legacy markup carried fonts and colors that fight the new
 * design.
 */
export function descriptionToSafeHtml(raw: string): string {
  let decoded = raw;
  for (let i = 0; i < 5; i++) {
    const next = decodeOnce(decoded);
    if (next === decoded) break;
    decoded = next;
  }
  return sanitizeHtml(decoded, {
    allowedTags: [
      "p", "br", "b", "strong", "i", "em", "u", "s",
      "ul", "ol", "li", "h2", "h3", "h4", "blockquote", "a", "span", "div", "table", "thead", "tbody", "tr", "td", "th",
    ],
    allowedAttributes: {
      a: ["href", "target", "rel"],
    },
    transformTags: {
      a: sanitizeHtml.simpleTransform("a", { rel: "nofollow noopener", target: "_blank" }),
    },
    // no styles, no classes, no images, no scripts
  });
}
