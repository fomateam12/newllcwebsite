/**
 * Turn a cart item's raw customizationData JSON into a human-readable
 * summary for the cart / mini-cart / checkout order summary.
 *
 * Canonical shape is CustomizationValue[] emitted by
 * components/CustomizationPreview.tsx:
 *
 *   { optionId, type: "text"|"font"|"color"|"upload",
 *     value, font?, color?, position?: { xPct, yPct } }
 *
 * We stay tolerant: unknown shapes fall back to generic key/value lines
 * instead of dumping raw JSON at the customer.
 */

export type CustomizationLine = {
  /** short label, e.g. "Text", "Font", "Color" */
  label: string;
  /** readable detail, e.g. `"Emma" · Classic Serif` */
  detail: string;
  /** hex color to render as a swatch, when the line is a color choice */
  swatch?: string;
};

type ValueEntry = {
  optionId?: number;
  type?: string;
  value?: unknown;
  font?: unknown;
  color?: unknown;
  position?: { xPct?: unknown; yPct?: unknown } | null;
};

function titleCase(s: string): string {
  return s.replace(/[-_]+/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

function isHexColor(v: unknown): v is string {
  return typeof v === "string" && /^#[0-9a-f]{3,8}$/i.test(v);
}

function describePosition(pos: ValueEntry["position"]): string | null {
  if (!pos || typeof pos !== "object") return null;
  const x = Number(pos.xPct);
  const y = Number(pos.yPct);
  if (!Number.isFinite(x) || !Number.isFinite(y)) return null;
  const horiz = x < 34 ? "left" : x > 66 ? "right" : "center";
  const vert = y < 34 ? "top" : y > 66 ? "bottom" : "middle";
  return vert === "middle" && horiz === "center" ? "centered" : `${vert} ${horiz}`;
}

function summarizeEntry(entry: ValueEntry): CustomizationLine | null {
  const value = typeof entry.value === "string" ? entry.value : "";
  switch (entry.type) {
    case "text": {
      if (!value) return null;
      const extras: string[] = [];
      if (typeof entry.font === "string" && entry.font) {
        extras.push(titleCase(entry.font));
      }
      const pos = describePosition(entry.position ?? null);
      if (pos) extras.push(pos);
      return {
        label: "Text",
        detail: `“${value}”${extras.length ? ` · ${extras.join(" · ")}` : ""}`,
        ...(isHexColor(entry.color) ? { swatch: entry.color } : {}),
      };
    }
    case "font":
      return value ? { label: "Font", detail: titleCase(value) } : null;
    case "color": {
      const hex = isHexColor(entry.color) ? entry.color : isHexColor(value) ? value : undefined;
      const name = value && !isHexColor(value) ? titleCase(value) : hex ?? "";
      if (!name && !hex) return null;
      return { label: "Color", detail: name || hex!, ...(hex ? { swatch: hex } : {}) };
    }
    case "upload":
      return { label: "Photo", detail: value ? "Uploaded" : "To be provided" };
    default: {
      if (!value) return null;
      return { label: entry.type ? titleCase(entry.type) : "Option", detail: value };
    }
  }
}

/** Best-effort summary; returns [] when there is nothing readable to show. */
export function summarizeCustomization(data: unknown): CustomizationLine[] {
  if (data == null) return [];

  // Canonical: array of CustomizationValue entries.
  if (Array.isArray(data)) {
    return data
      .filter((e): e is ValueEntry => typeof e === "object" && e !== null)
      .map(summarizeEntry)
      .filter((line): line is CustomizationLine => line !== null);
  }

  // Fallback: plain object → key/value lines (skip noisy values).
  if (typeof data === "object") {
    const lines: CustomizationLine[] = [];
    for (const [key, value] of Object.entries(data as Record<string, unknown>)) {
      if (typeof value === "string" && value) {
        lines.push({
          label: titleCase(key),
          detail: value,
          ...(isHexColor(value) ? { swatch: value } : {}),
        });
      } else if (typeof value === "number" || typeof value === "boolean") {
        lines.push({ label: titleCase(key), detail: String(value) });
      }
      if (lines.length >= 6) break;
    }
    return lines;
  }

  if (typeof data === "string" && data) {
    return [{ label: "Note", detail: data }];
  }
  return [];
}
