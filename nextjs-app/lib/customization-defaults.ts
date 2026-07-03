/**
 * Category-derived default personalization options.
 *
 * The customization_options D1 table exists but is EMPTY today, so the
 * product page synthesizes a sensible option set from the product's
 * category (walking UP the parent tree until a rule matches). The shapes
 * mirror customization_options rows exactly (see CustomizationOption in
 * components/CustomizationPreview.tsx, whose file header documents the
 * config_json contract), so swapping in real DB rows later is a no-op
 * for the UI.
 *
 * TODO: replace with real customization_options rows per product/category
 * once merchandising decides per-product option sets; when
 * getProductBySlug returns options.length > 0 the page already prefers
 * the DB rows and ignores these defaults.
 *
 * Synthetic option ids are NEGATIVE so they can never collide with real
 * customization_options.id values inside cart customizationData.
 *
 * Client-safe: pure data + functions, no server-only imports.
 */

import type { CustomizationOption } from "@/components/CustomizationPreview";

export type CategoryNode = {
  id: number;
  slug: string;
  name: string;
  parentId: number | null;
};

/* ------------------------------------------------------------- option kits */

const engravableKit: CustomizationOption[] = [
  {
    id: -1,
    optionType: "text",
    label: "Engraving text",
    configJson: JSON.stringify({
      maxChars: 20,
      placeholder: "Name or short message",
      defaultPosition: { xPct: 50, yPct: 55 },
    }),
    required: 0,
  },
  { id: -2, optionType: "font", label: "Engraving font", configJson: null, required: 0 },
];

const softGoodsKit: CustomizationOption[] = [
  {
    id: -1,
    optionType: "text",
    label: "Embroidered name",
    configJson: JSON.stringify({
      maxChars: 15,
      placeholder: "Name to stitch",
      defaultPosition: { xPct: 50, yPct: 70 },
    }),
    required: 0,
  },
  {
    id: -3,
    optionType: "upload",
    label: "Your photo (woven / printed designs)",
    configJson: null,
    required: 0,
  },
];

const apparelKit: CustomizationOption[] = [
  {
    id: -1,
    optionType: "text",
    label: "Custom print text",
    configJson: JSON.stringify({
      maxChars: 25,
      placeholder: "Text for the print",
      defaultPosition: { xPct: 50, yPct: 40 },
    }),
    required: 0,
  },
];

const fallbackKit: CustomizationOption[] = [
  {
    id: -1,
    optionType: "text",
    label: "Personalization text",
    configJson: JSON.stringify({
      maxChars: 30,
      placeholder: "Add a name, date or message",
    }),
    required: 0,
  },
];

/* -------------------------------------------------- category slug → kit map */

/**
 * First match wins while walking up the category tree from the product's
 * own category. Slugs are the seeded category slugs (migrations/0002).
 */
const KIT_BY_CATEGORY_SLUG: Array<{ slugs: string[]; kit: CustomizationOption[] }> = [
  {
    // engraved drinkware & metal goods → text + font + draggable position
    slugs: ["tumbler", "water-bottle", "lighter", "beverage-holder", "knives"],
    kit: engravableKit,
  },
  {
    // blankets & towels → text + photo-upload placeholder
    slugs: [
      "blankets",
      "woven-blanket",
      "picnic-blanket",
      "baby-swaddle",
      "beach-towels",
    ],
    kit: softGoodsKit,
  },
  {
    // printed apparel → text
    slugs: [
      "t-shirt",
      "sweatshirts-hoodies",
      "custom-logo-shirt",
      "flower-shirt",
      "clothing-and-fashion",
    ],
    kit: apparelKit,
  },
];

function kitForSlug(slug: string): CustomizationOption[] | null {
  for (const rule of KIT_BY_CATEGORY_SLUG) {
    if (rule.slugs.includes(slug)) return rule.kit;
  }
  return null;
}

/**
 * Default options for a product in `categoryId`, walking up the parent
 * tree until a rule matches; falls back to a single text option.
 * `categories` is the full (small, ~57-row) categories table.
 */
export function getDefaultCustomizationOptions(
  categories: CategoryNode[],
  categoryId: number | null,
): CustomizationOption[] {
  const byId = new Map(categories.map((c) => [c.id, c]));
  let current = categoryId !== null ? byId.get(categoryId) : undefined;
  let hops = 0;
  while (current && hops < 10 /* cycle guard */) {
    const kit = kitForSlug(current.slug);
    if (kit) return kit;
    current = current.parentId !== null ? byId.get(current.parentId) : undefined;
    hops++;
  }
  return fallbackKit;
}
