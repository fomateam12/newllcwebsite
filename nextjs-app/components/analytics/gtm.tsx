"use client";

import Script from "next/script";
import { useConsent } from "@/lib/analytics-consent";

/**
 * Google Tag Manager — consent-gated.
 *
 * - Renders NOTHING when NEXT_PUBLIC_GTM_ID is unset (default in
 *   .env.example) or when the visitor has not granted consent, so a
 *   fresh page carries zero Google scripts.
 * - When consent is granted the inline snippet first sets Google
 *   Consent Mode v2 defaults (all denied — required so any tag inside
 *   the container starts from denied), then immediately updates to
 *   granted, then loads gtm.js. The ordering inside one inline script
 *   guarantees consent state exists before the container boots.
 */

const GTM_ID = process.env.NEXT_PUBLIC_GTM_ID ?? "";

/** Consent Mode v2 signals covered by the ff_consent choice. */
const CONSENT_SIGNALS =
  "{'ad_storage':'%v','ad_user_data':'%v','ad_personalization':'%v','analytics_storage':'%v'}";

function gtmSnippet(id: string): string {
  return [
    "window.dataLayer=window.dataLayer||[];",
    "function gtag(){dataLayer.push(arguments);}",
    // Consent Mode v2: defaults must be set before the container loads.
    `gtag('consent','default',${CONSENT_SIGNALS.replace(/%v/g, "denied")});`,
    // This script only renders after the visitor accepted the banner.
    `gtag('consent','update',${CONSENT_SIGNALS.replace(/%v/g, "granted")});`,
    "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});",
    "var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';",
    "j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;",
    `f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','${id}');`,
  ].join("");
}

export function GoogleTagManager() {
  const consent = useConsent();
  if (!GTM_ID || consent !== "granted") return null;
  return (
    <Script
      id="ff-gtm"
      strategy="afterInteractive"
      dangerouslySetInnerHTML={{ __html: gtmSnippet(GTM_ID) }}
    />
  );
}
