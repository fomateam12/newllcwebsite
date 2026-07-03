/// <reference types="@cloudflare/workers-types" />

interface CloudflareEnv {
  DB: D1Database;
  IMAGES: R2Bucket;
  IMAGES_API: ImagesBinding;
  RATE_LIMIT: KVNamespace;
}
