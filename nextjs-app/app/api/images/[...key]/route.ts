import { getCloudflareContext } from "@opennextjs/cloudflare";

/**
 * Streams product images from the private R2 bucket (env.IMAGES),
 * optionally resized/re-encoded via the Cloudflare Images binding
 * (?w=200|400|800|1200). Falls back to the original bytes if the
 * transform fails (e.g. Images not enabled on the account).
 */

// Fixed variant set so the query param can't mint unbounded (billable)
// unique transformations.
const ALLOWED_WIDTHS = new Set([200, 400, 800, 1200]);

const IMMUTABLE = "public, max-age=31536000, immutable";

export async function GET(
  req: Request,
  { params }: { params: Promise<{ key: string[] }> },
) {
  const { key } = await params;
  const objectKey = key.map(decodeURIComponent).join("/");

  const url = new URL(req.url);
  const wParam = Number(url.searchParams.get("w"));
  const width = ALLOWED_WIDTHS.has(wParam) ? wParam : null;
  const wantsWebp = (req.headers.get("accept") ?? "").includes("image/webp");
  const format = wantsWebp ? "image/webp" : "image/jpeg";

  const { env, ctx } = getCloudflareContext();

  // Transformed responses are not auto-cached — cache them at the edge
  // ourselves, keyed by canonical URL + negotiated format. `caches` only
  // exists on the Workers runtime; under `next dev` it is undefined.
  const cache =
    typeof caches !== "undefined"
      ? (caches as unknown as { default: Cache }).default
      : null;
  let cacheKey: Request | null = null;
  if (width && cache) {
    const canonical = new URL(url.origin + url.pathname);
    canonical.searchParams.set("w", String(width));
    canonical.searchParams.set("fmt", format);
    cacheKey = new Request(canonical.toString());
    const hit = await cache.match(cacheKey);
    if (hit) return hit;
  }

  const object = await env.IMAGES.get(objectKey);
  if (!object) {
    return new Response("Image not found", {
      status: 404,
      headers: { "cache-control": "public, max-age=60" },
    });
  }

  if (width) {
    try {
      const transformed = (
        await env.IMAGES_API.input(object.body)
          .transform({ width })
          .output({ format, quality: 82 })
      ).response();
      const res = new Response(transformed.body, {
        headers: {
          "content-type": transformed.headers.get("content-type") ?? format,
          "cache-control": IMMUTABLE,
          vary: "Accept",
        },
      });
      if (cacheKey && cache) ctx.waitUntil(cache.put(cacheKey, res.clone()));
      return res;
    } catch {
      // Transform unavailable — re-read (input() consumed the stream)
      // and serve the original below.
      const original = await env.IMAGES.get(objectKey);
      if (original) return originalResponse(original);
      return new Response("Image not found", { status: 404 });
    }
  }

  return originalResponse(object);
}

function originalResponse(object: R2ObjectBody): Response {
  return new Response(object.body, {
    headers: {
      "content-type": object.httpMetadata?.contentType ?? "image/jpeg",
      "cache-control": IMMUTABLE,
      etag: object.httpEtag,
    },
  });
}
