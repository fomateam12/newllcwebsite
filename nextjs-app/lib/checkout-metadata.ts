/**
 * Order items travel from checkout to the webhook inside Stripe session
 * metadata. Stripe limits: 50 keys, 500 chars per value. We serialize
 * the items to JSON and split the string across `items_0..items_N`
 * chunks (< 500 chars each), with `items_chunks` recording the count.
 * Customization data is trimmed before serializing so a single huge
 * value can never push us over the key budget.
 */

export type MetadataOrderItem = {
  /** product id (server-verified) */
  p: number;
  /** quantity */
  q: number;
  /** unit price in cents (server-side price, never from the client) */
  u: number;
  /** customization data as a JSON string, optional */
  c?: string;
};

const CHUNK_SIZE = 490; // < Stripe's 500-char value limit
const MAX_CHUNKS = 40; // < Stripe's 50-key limit, leaves room for other keys
const CUSTOMIZATION_MAX = 400; // per-item cap on serialized customization

function trimCustomization(c: string | undefined, max: number): string | undefined {
  if (!c) return undefined;
  return c.length > max ? c.slice(0, max) : c;
}

/** Serialize items and split into metadata chunks. Never throws on size:
 * progressively shrinks/drops customization data until it fits. */
export function encodeItemsToMetadata(
  items: MetadataOrderItem[],
): Record<string, string> {
  const attempts: Array<(i: MetadataOrderItem) => MetadataOrderItem> = [
    (i) => ({ ...i, c: trimCustomization(i.c, CUSTOMIZATION_MAX) }),
    (i) => ({ ...i, c: trimCustomization(i.c, 100) }),
    ({ p, q, u }) => ({ p, q, u }), // last resort: drop customization
  ];

  for (const shape of attempts) {
    const json = JSON.stringify(items.map(shape));
    const chunkCount = Math.ceil(json.length / CHUNK_SIZE) || 1;
    if (chunkCount > MAX_CHUNKS) continue;
    const metadata: Record<string, string> = {
      items_chunks: String(chunkCount),
    };
    for (let i = 0; i < chunkCount; i++) {
      metadata[`items_${i}`] = json.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
    }
    return metadata;
  }
  // Unreachable in practice (bare items for a bounded cart always fit),
  // but fail loud rather than store a corrupt order.
  throw new Error("Order items too large for Stripe metadata");
}

/** Reassemble and parse items from session metadata. Returns null if the
 * metadata is missing or malformed (webhook logs and skips). */
export function decodeItemsFromMetadata(
  metadata: Record<string, string> | null | undefined,
): MetadataOrderItem[] | null {
  if (!metadata) return null;
  const chunkCount = Number(metadata.items_chunks);
  if (!Number.isInteger(chunkCount) || chunkCount < 1) return null;
  let json = "";
  for (let i = 0; i < chunkCount; i++) {
    const chunk = metadata[`items_${i}`];
    if (typeof chunk !== "string") return null;
    json += chunk;
  }
  try {
    const parsed = JSON.parse(json);
    if (!Array.isArray(parsed)) return null;
    const items: MetadataOrderItem[] = [];
    for (const raw of parsed) {
      if (
        typeof raw !== "object" || raw === null ||
        !Number.isInteger(raw.p) ||
        !Number.isInteger(raw.q) || raw.q < 1 ||
        typeof raw.u !== "number"
      ) {
        return null;
      }
      items.push({
        p: raw.p,
        q: raw.q,
        u: raw.u,
        ...(typeof raw.c === "string" ? { c: raw.c } : {}),
      });
    }
    return items.length > 0 ? items : null;
  } catch {
    return null;
  }
}
