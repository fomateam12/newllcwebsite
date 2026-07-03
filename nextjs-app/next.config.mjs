import { initOpenNextCloudflareForDev } from "@opennextjs/cloudflare";

// Make D1/R2 bindings available in `next dev` via wrangler's local platform.
initOpenNextCloudflareForDev();

/** @type {import('next').NextConfig} */
const nextConfig = {};

export default nextConfig;
