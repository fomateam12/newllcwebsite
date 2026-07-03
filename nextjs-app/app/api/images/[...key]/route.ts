import { getCloudflareContext } from "@opennextjs/cloudflare";

/**
 * Streams product images from the private R2 bucket (env.IMAGES).
 * Keys mirror the OpenCart paths stored in product_images.r2_key,
 * e.g. /api/images/catalog/product_img/…/il-fullxfull.…jpg
 */
export async function GET(
  _req: Request,
  { params }: { params: Promise<{ key: string[] }> },
) {
  const { key } = await params;
  const objectKey = key.map(decodeURIComponent).join("/");

  const { env } = getCloudflareContext();
  const object = await env.IMAGES.get(objectKey);

  if (!object) {
    return new Response("Image not found", {
      status: 404,
      headers: { "cache-control": "public, max-age=60" },
    });
  }

  return new Response(object.body, {
    headers: {
      "content-type": object.httpMetadata?.contentType ?? "image/jpeg",
      // Filenames are content-addressed by the legacy catalog and never
      // rewritten, so aggressive immutable caching is safe.
      "cache-control": "public, max-age=31536000, immutable",
      etag: object.httpEtag,
    },
  });
}
