"use client";

/**
 * Shares live customization state between the "Make it yours" section
 * (CustomizationPreview) and the buy box, so the two can live in
 * different parts of the server-rendered page layout while the
 * personalization JSON still flows into the cart item.
 */

import { createContext, useContext, useMemo, useState } from "react";
import type { CustomizationValue } from "@/components/CustomizationPreview";

type PurchaseContextValue = {
  /** latest CustomizationPreview output; null until the widget mounts */
  customization: CustomizationValue[] | null;
  setCustomization: (values: CustomizationValue[]) => void;
};

const PurchaseContext = createContext<PurchaseContextValue | null>(null);

export function ProductPurchaseProvider({ children }: { children: React.ReactNode }) {
  const [customization, setCustomization] = useState<CustomizationValue[] | null>(null);
  const value = useMemo(() => ({ customization, setCustomization }), [customization]);
  return <PurchaseContext.Provider value={value}>{children}</PurchaseContext.Provider>;
}

export function useProductPurchase(): PurchaseContextValue {
  const ctx = useContext(PurchaseContext);
  if (!ctx) {
    throw new Error("useProductPurchase must be used inside <ProductPurchaseProvider>");
  }
  return ctx;
}
