"use client";

import Script from "next/script";
import { usePathname } from "next/navigation";
import { useEffect, useRef } from "react";
import { useConsent } from "@/lib/analytics-consent";

/**
 * Meta (Facebook) Pixel skeleton — consent-gated.
 *
 * Renders NOTHING when NEXT_PUBLIC_META_PIXEL_ID is unset or the visitor
 * has not granted consent. The pixel id is an env placeholder only —
 * never hardcode a real pixel id here.
 *
 * Tracks the initial PageView plus one per client-side route change
 * (App Router navigations don't reload the document, so fbq would
 * otherwise see a single PageView per session).
 */

const PIXEL_ID = process.env.NEXT_PUBLIC_META_PIXEL_ID ?? "";

declare global {
  interface Window {
    fbq?: (...args: unknown[]) => void;
  }
}

function pixelSnippet(id: string): string {
  return [
    "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?",
    "n.callMethod.apply(n,arguments):n.queue.push(arguments)};",
    "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';",
    "n.queue=[];t=b.createElement(e);t.async=!0;",
    "t.src=v;s=b.getElementsByTagName(e)[0];",
    "s.parentNode.insertBefore(t,s)}(window,document,'script',",
    "'https://connect.facebook.net/en_US/fbevents.js');",
    `fbq('init','${id}');`,
    "fbq('track','PageView');",
  ].join("");
}

export function MetaPixel() {
  const consent = useConsent();
  const pathname = usePathname();
  const enabled = Boolean(PIXEL_ID) && consent === "granted";
  const firstPathname = useRef(true);

  // PageView on client-side route changes (the snippet covers the first one).
  useEffect(() => {
    if (!enabled) return;
    if (firstPathname.current) {
      firstPathname.current = false;
      return;
    }
    window.fbq?.("track", "PageView");
  }, [enabled, pathname]);

  if (!enabled) return null;
  return (
    <Script
      id="ff-meta-pixel"
      strategy="afterInteractive"
      dangerouslySetInnerHTML={{ __html: pixelSnippet(PIXEL_ID) }}
    />
  );
}
