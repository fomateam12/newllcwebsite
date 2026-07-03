"use client";

import { useEffect } from "react";
import { useConsent, type ConsentValue } from "@/lib/analytics-consent";

/**
 * Google Tag Manager — consent-gated.
 *
 * - Renders NOTHING when NEXT_PUBLIC_GTM_ID is unset (default in
 *   .env.example) or when the visitor has not granted consent, so a
 *   fresh page carries zero Google scripts.
 * - The bootstrap first sets Google Consent Mode v2 defaults (all
 *   denied — required so any tag inside the container starts from
 *   denied), then immediately updates to granted (this code only ever
 *   runs post-acceptance), then loads gtm.js. Single-script ordering
 *   guarantees consent state exists before the container boots.
 *
 * Why a plain inline <script> instead of next/script(afterInteractive):
 * App Router next/script injects afterInteractive scripts client-side
 * post-hydration, so the tag would never appear in server HTML. Because
 * this component is consent-gated we want the SSR path (returning
 * visitor with ff_consent=granted) to carry the bootstrap in the initial
 * HTML — parse-time execution, curl-verifiable. The useEffect fallback
 * covers the just-clicked-Accept path; the __ffGtmBooted window flag
 * makes the two paths mutually exclusive.
 */

const GTM_ID = process.env.NEXT_PUBLIC_GTM_ID ?? "";

const DENIED =
  "{'ad_storage':'denied','ad_user_data':'denied','ad_personalization':'denied','analytics_storage':'denied','wait_for_update':500}";
const GRANTED =
  "{'ad_storage':'granted','ad_user_data':'granted','ad_personalization':'granted','analytics_storage':'granted'}";

function inlineBootstrap(id: string): string {
  return [
    "(function(w,d){if(w.__ffGtmBooted)return;w.__ffGtmBooted=true;",
    "w.dataLayer=w.dataLayer||[];function g(){w.dataLayer.push(arguments);}",
    // Consent Mode v2: defaults must be pushed before the container loads.
    `g('consent','default',${DENIED});`,
    // Only rendered after the visitor accepted the banner.
    `g('consent','update',${GRANTED});`,
    "w.dataLayer.push({'gtm.start':new Date().getTime(),event:'gtm.js'});",
    "var j=d.createElement('script');j.async=true;",
    `j.src='https://www.googletagmanager.com/gtm.js?id='+${JSON.stringify(encodeURIComponent(id))};`,
    "d.head.appendChild(j);})(window,document);",
  ].join("");
}

/** Same bootstrap for the client path (visitor just clicked Accept). */
function bootstrapGtm(id: string): void {
  const w = window as unknown as {
    __ffGtmBooted?: boolean;
    dataLayer?: unknown[];
  };
  if (w.__ffGtmBooted) return;
  w.__ffGtmBooted = true;
  w.dataLayer = w.dataLayer || [];
  const dl = w.dataLayer;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  function gtag(..._args: unknown[]) {
    // GTM requires the Arguments object, not an array.
    // eslint-disable-next-line prefer-rest-params
    dl.push(arguments);
  }
  gtag("consent", "default", JSON.parse(DENIED.replace(/'/g, '"')));
  gtag("consent", "update", JSON.parse(GRANTED.replace(/'/g, '"')));
  dl.push({ "gtm.start": new Date().getTime(), event: "gtm.js" });
  const script = document.createElement("script");
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(id)}`;
  document.head.appendChild(script);
}

export function GoogleTagManager({
  initialConsent = null,
}: {
  /** consent cookie value read server-side, so SSR HTML already includes
   *  (or omits) the script for returning visitors */
  initialConsent?: ConsentValue | null;
}) {
  const consent = useConsent(initialConsent);
  const active = Boolean(GTM_ID) && consent === "granted";

  useEffect(() => {
    if (active) bootstrapGtm(GTM_ID);
  }, [active]);

  if (!active) return null;
  return (
    <script
      id="ff-gtm"
      dangerouslySetInnerHTML={{ __html: inlineBootstrap(GTM_ID) }}
    />
  );
}
