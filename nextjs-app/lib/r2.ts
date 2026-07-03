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
 * Resolve a public URL for an R2 key.
 * Uses NEXT_PUBLIC_R2_PUBLIC_URL (custom domain or r2.dev public bucket URL)
 * when set; falls back to a same-origin /api/images/[key] route (to be
 * implemented) so the app still works without a public bucket.
 */
export function getImageUrl(key: string): string {
  const base = process.env.NEXT_PUBLIC_R2_PUBLIC_URL;
  if (base) {
    return `${base.replace(/\/$/, "")}/${key}`;
  }
  return `/api/images/${encodeURIComponent(key)}`;
}

/** Fetch an object directly from the bucket (for serving via a route handler). */
export async function getImage(key: string): Promise<R2ObjectBody | null> {
  return getBucket().get(key);
}
