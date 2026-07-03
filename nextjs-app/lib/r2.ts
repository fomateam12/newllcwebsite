import { getCloudflareContext } from "@opennextjs/cloudflare";

function getBucket(): R2Bucket {
  const { env } = getCloudflareContext();
  return env.IMAGES;
}

/**
 * Upload an image to the R2 bucket under the given key.
 * Returns the key so callers can persist it (e.g. product_images.r2_key).
 */
export async function uploadImage(
  key: string,
  body: ReadableStream | ArrayBuffer | Blob,
  contentType = "image/jpeg",
): Promise<string> {
  await getBucket().put(key, body, {
    httpMetadata: { contentType },
  });
  return key;
}

/**
 * Resolve a URL for an R2 key. Default: the same-origin
 * /api/images/[...key] proxy route (bucket stays private, immutable
 * cache headers). NEXT_PUBLIC_R2_PUBLIC_URL can override with a public
 * bucket/custom domain later without touching call sites.
 */
export function getImageUrl(key: string): string {
  const base = process.env.NEXT_PUBLIC_R2_PUBLIC_URL;
  if (base) {
    return `${base.replace(/\/$/, "")}/${key}`;
  }
  return `/api/images/${key.split("/").map(encodeURIComponent).join("/")}`;
}

/** Fetch an object directly from the bucket (for serving via a route handler). */
export async function getImage(key: string): Promise<R2ObjectBody | null> {
  return getBucket().get(key);
}
