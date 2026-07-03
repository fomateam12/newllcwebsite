"use client";

/**
 * Isolated test page for <CustomizationPreview>.
 * Not linked from navigation — visit /preview-test directly.
 *
 * Uses a real seeded image key (migrations/0002_seed_data.sql, product 585:
 * "Retro Style Custom Name Blanket") served via the /api/images proxy, and
 * three mock customization_options rows exercising the documented
 * config_json contract. The live customization_data JSON (what will be
 * attached to cart items) is pretty-printed below the widget.
 */

import { useState } from "react";
import CustomizationPreview, {
  type CustomizationOption,
  type CustomizationValue,
} from "@/components/CustomizationPreview";

const PRODUCT_IMAGE =
  "/api/images/catalog/product_img/35467569/r/il/e6a599/5123270614/il-fullxfull.5123270614-lwlu.jpg?w=800";

const MOCK_OPTIONS: CustomizationOption[] = [
  {
    id: 1,
    optionType: "text",
    label: "Name on the blanket",
    configJson: JSON.stringify({
      maxChars: 16,
      placeholder: "e.g. Charlotte",
      defaultPosition: { xPct: 50, yPct: 62 },
    }),
    required: 1,
  },
  {
    id: 2,
    optionType: "font",
    label: "Lettering style",
    configJson: JSON.stringify({
      fonts: [
        { id: "serif", label: "Classic Serif", stack: "var(--font-fraunces), Georgia, serif" },
        { id: "sans", label: "Modern Sans", stack: "var(--font-geist-sans), system-ui, sans-serif" },
        { id: "script", label: "Script", stack: "'Snell Roundhand', 'Segoe Script', 'Brush Script MT', cursive" },
        { id: "mono", label: "Typewriter", stack: "var(--font-geist-mono), 'Courier New', monospace" },
      ],
    }),
    required: 0,
  },
  {
    id: 3,
    optionType: "color",
    label: "Thread color",
    configJson: JSON.stringify({
      palette: [
        { id: "ink", label: "Ink", value: "#262119" },
        { id: "pine", label: "Pine", value: "#2e5a4e" },
        { id: "amber", label: "Amber", value: "#b8862b" },
        { id: "white", label: "White", value: "#ffffff" },
      ],
    }),
    required: 0,
  },
];

export default function PreviewTestPage() {
  const [state, setState] = useState<CustomizationValue[]>([]);

  return (
    <div className="mx-auto max-w-6xl px-5 py-8">
      <p className="font-mono text-xs text-ink/40">components/CustomizationPreview.tsx · test harness</p>
      <h1 className="mt-1 font-display text-2xl text-ink sm:text-3xl">
        Live customization preview
      </h1>
      <p className="mt-2 max-w-2xl text-sm text-ink/70">
        Type a name, pick a lettering style and thread color, then drag the
        text (mouse or touch) to position it on the blanket. The JSON below is
        the exact <code className="font-mono text-xs">customization_data</code>{" "}
        payload that will be attached to cart items.
      </p>

      <div className="mt-6">
        <CustomizationPreview
          productImage={PRODUCT_IMAGE}
          productImageAlt="Retro style custom name blanket"
          options={MOCK_OPTIONS}
          onChange={setState}
        />
      </div>

      <section className="mt-8">
        <h2 className="mb-2 font-display text-lg text-ink">
          customization_data <span className="font-mono text-xs text-ink/40">(live)</span>
        </h2>
        <pre className="overflow-x-auto rounded-md border border-line bg-white p-4 font-mono text-xs leading-relaxed text-pine-deep">
          {JSON.stringify(state, null, 2)}
        </pre>
      </section>
    </div>
  );
}
