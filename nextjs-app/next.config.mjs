import { setupDevPlatform } from "@cloudflare/next-on-pages/next-dev";

// Make D1/R2 bindings available in `next dev` via wrangler's local platform.
if (process.env.NODE_ENV === "development") {
  await setupDevPlatform();
}

/** @type {import('next').NextConfig} */
const nextConfig = {};

export default nextConfig;
