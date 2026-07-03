import { AttributionCapture } from "./attribution-capture";
import { CloudflareAnalytics } from "./cf-analytics";
import { readConsentFromRequest } from "./consent-server";
import { GoogleTagManager } from "./gtm";
import { MetaPixel } from "./meta-pixel";

/**
 * Single mount point for every analytics concern, so app/layout.tsx only
 * needs `<Analytics />` (plus `<ConsentBanner />`).
 *
 * With no NEXT_PUBLIC_* analytics env vars set, this renders nothing.
 * GTM and Meta Pixel additionally require granted consent (the cookie is
 * read server-side so consented visitors get the tags straight in the SSR
 * HTML); Cloudflare Web Analytics is cookieless and consent-exempt.
 */
export async function Analytics() {
  const initialConsent = await readConsentFromRequest();
  return (
    <>
      <GoogleTagManager initialConsent={initialConsent} />
      <MetaPixel initialConsent={initialConsent} />
      <CloudflareAnalytics />
      <AttributionCapture />
    </>
  );
}
