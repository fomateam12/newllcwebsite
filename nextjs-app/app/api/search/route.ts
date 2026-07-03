import { NextResponse } from "next/server";
import { searchActiveProducts } from "@/lib/catalog";
import { getImageUrl } from "@/lib/r2";

export const dynamic = "force-dynamic";

const MAX_QUERY_LENGTH = 80;
const RESULT_LIMIT = 8;

/**
 * GET /api/search?q=<term>
 * Autocomplete over active product names. Returns up to 8 hits:
 * { results: [{ id, name, slug, thumb }] } where thumb is a ready-to-use
 * image URL (200px variant) or null.
 */
export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const q = (searchParams.get("q") ?? "").trim().slice(0, MAX_QUERY_LENGTH);
  if (q.length < 2) {
    return NextResponse.json({ results: [] });
  }
  const hits = await searchActiveProducts(q, RESULT_LIMIT);
  return NextResponse.json(
    {
      results: hits.map((h) => ({
        id: h.id,
        name: h.name,
        slug: h.slug,
        thumb: h.thumb ? getImageUrl(h.thumb, 200) : null,
      })),
    },
    { headers: { "Cache-Control": "public, max-age=60" } },
  );
}
