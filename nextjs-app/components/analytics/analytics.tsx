import { AttributionCapture } from "./attribution-capture";
import { CloudflareAnalytics } from "./cf-analytics";
import { GoogleTagManager } from "./gtm";
import { MetaPixel } from "./meta-pixel";

/**
 * Single mount point for every analytics concern, so app/layout.tsx only
 * needs `<Analytics />` (plus `<ConsentBanner />`).
 *
 * With no NEXT_PUBLIC_* analytics env vars set, this renders nothing.
 * GTM and Meta Pixel additionally require granted consent; Cloudflare
 * Web Analytics is cookieless and consent-exempt.
 */
export function Analytics() {
  return (
    <>
      <GoogleTagManager />
      <MetaPixel />
      <CloudflareAnalytics />
      <AttributionCapture />
    </>
  );
}
