"use client";

import { usePathname } from "next/navigation";
import { useEffect, useRef } from "react";
import { useConsent, type ConsentValue } from "@/lib/analytics-consent";

/**
 * Meta (Facebook) Pixel skeleton — consent-gated.
 *
 * Renders NOTHING when NEXT_PUBLIC_META_PIXEL_ID is unset or the visitor
 * has not granted consent. The pixel id is an env placeholder only —
 * never hardcode a real pixel id here.
 *
 * Like gtm.tsx, the bootstrap is a plain inline <script> (not
 * next/script) so the SSR path for already-consented visitors carries it
 * in the initial HTML; the effect covers the just-clicked-Accept path.
 * The standard fbq stub's `if(f.fbq)return` guard makes both idempotent.
 *
 * Tracks the initial PageView plus one per client-side route change
 * (App Router navigations don't reload the document, so fbq would
 * otherwise see a single PageView per session).
 */

const PIXEL_ID = process.env.NEXT_PUBLIC_META_PIXEL_ID ?? "";

type Fbq = {
  (...args: unknown[]): void;
  callMethod?: (...args: unknown[]) => void;
  queue: unknown[];
  push: unknown;
  loaded: boolean;
  version: string;
};

declare global {
  interface Window {
    fbq?: Fbq;
    _fbq?: Fbq;
  }
}

function inlineBootstrap(id: string): string {
  return [
    "!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?",
    "n.callMethod.apply(n,arguments):n.queue.push(arguments)};",
    "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';",
    "n.queue=[];t=b.createElement(e);t.async=!0;",
    "t.src=v;s=b.getElementsByTagName(e)[0];",
    "s.parentNode.insertBefore(t,s)}(window,document,'script',",
    "'https://connect.facebook.net/en_US/fbevents.js');",
    `fbq('init',${JSON.stringify(id)});`,
    "fbq('track','PageView');",
  ].join("");
}

/** TS equivalent of the fbq stub, for the just-clicked-Accept path. */
function bootstrapPixel(id: string): void {
  if (window.fbq) return; // inline script (or a previous call) already ran
  const fbq = function (...args: unknown[]) {
    if (fbq.callMethod) fbq.callMethod(...args);
    else fbq.queue.push(args);
  } as Fbq;
  fbq.queue = [];
  fbq.push = fbq;
  fbq.loaded = true;
  fbq.version = "2.0";
  window.fbq = fbq;
  if (!window._fbq) window._fbq = fbq;
  const script = document.createElement("script");
  script.async = true;
  script.src = "https://connect.facebook.net/en_US/fbevents.js";
  document.head.appendChild(script);
  fbq("init", id);
  fbq("track", "PageView");
}

export function MetaPixel({
  initialConsent = null,
}: {
  initialConsent?: ConsentValue | null;
}) {
  const consent = useConsent(initialConsent);
  const pathname = usePathname();
  const enabled = Boolean(PIXEL_ID) && consent === "granted";
  const firstPathname = useRef(true);

  useEffect(() => {
    if (enabled) bootstrapPixel(PIXEL_ID);
  }, [enabled]);

  // PageView on client-side route changes (the bootstrap covers the first).
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
    <script
      id="ff-meta-pixel"
      dangerouslySetInnerHTML={{ __html: inlineBootstrap(PIXEL_ID) }}
    />
  );
}
