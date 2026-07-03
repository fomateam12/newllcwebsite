"use client";

/**
 * "Make it yours" section body: mounts CustomizationPreview and streams
 * its onChange JSON into ProductPurchaseProvider so the buy box attaches
 * it to the cart item as customizationData.
 */

import CustomizationPreview, {
  type CustomizationOption,
} from "@/components/CustomizationPreview";
import { useProductPurchase } from "./purchase-provider";

type Props = {
  /** getImageUrl(r2Key, 800) resolved server-side */
  productImage: string;
  productImageAlt: string;
  options: CustomizationOption[];
};

export function CustomizePanel({ productImage, productImageAlt, options }: Props) {
  const { setCustomization } = useProductPurchase();
  return (
    <CustomizationPreview
      productImage={productImage}
      productImageAlt={productImageAlt}
      options={options}
      onChange={setCustomization}
    />
  );
}
