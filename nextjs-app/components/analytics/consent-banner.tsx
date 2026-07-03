import { ConsentBannerClient } from "./consent-banner-client";
import { readConsentFromRequest } from "./consent-server";

/**
 * Server wrapper for the consent banner: reads the ff_consent cookie from
 * the request so the banner is already present (new visitors) or absent
 * (returning visitors) in the SSR HTML — no flash, curl-testable.
 */
export async function ConsentBanner() {
  const initialConsent = await readConsentFromRequest();
  return <ConsentBannerClient initialConsent={initialConsent} />;
}
