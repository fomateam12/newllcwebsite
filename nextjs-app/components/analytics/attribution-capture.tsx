"use client";

import { useEffect } from "react";
import { captureAttribution } from "@/lib/attribution";

/**
 * Invisible island that records first-touch UTM/click-id attribution into
 * the ff_attrib cookie on page load. Idempotent — lib/attribution.ts only
 * writes when no cookie exists yet.
 */
export function AttributionCapture() {
  useEffect(() => {
    captureAttribution();
  }, []);
  return null;
}
