import { defineCloudflareConfig } from "@opennextjs/cloudflare";

// Default config: no incremental cache (ISR/data cache) yet.
// Add R2/KV incremental cache here when caching is needed.
export default defineCloudflareConfig();
