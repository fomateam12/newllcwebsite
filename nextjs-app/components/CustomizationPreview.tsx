"use client";

/**
 * CustomizationPreview — live "what your gift will look like" widget.
 *
 * RENDERING DECISION: plain DOM overlay (absolutely-positioned text on top of
 * the product <img>) instead of Fabric.js or a raw <canvas>.
 *
 *  - Bundle size: Fabric.js is ~90 KB gzipped (~300 KB min) for what is, here,
 *    a single draggable text layer. The DOM approach ships zero extra bytes.
 *  - React 19: Fabric owns its own imperative scene graph, which fights
 *    React's rendering model (refs + manual sync everywhere) and its React
 *    wrappers lag behind React 19. DOM overlay is just JSX — state in, UI out.
 *  - Crispness: canvas rasterizes text at bitmap resolution; DOM text gets
 *    native subpixel anti-aliasing and stays sharp on retina/zoom for free.
 *    It also uses the exact same font rendering as the rest of the page, so
 *    next/font-loaded faces (Fraunces/Geist) "just work".
 *  - Touch: Pointer Events unify mouse + touch/stylus in one code path
 *    (`onPointerDown/Move/Up` + `setPointerCapture` + `touch-action: none`).
 *  - Requirements are modest (text overlay, drag, font, color). If we later
 *    need warping/curved text or raster export, we can swap the render layer
 *    without changing the state contract below — the emitted JSON is
 *    presentation-agnostic (percent-based positions).
 *
 * CONFIG SHAPE (customization_options.config_json — the table is empty today,
 * so this component defines and documents the contract):
 *
 *   option_type = 'text':
 *     { "maxChars": 20, "placeholder": "Your name",
 *       "defaultText": "", "defaultPosition": { "xPct": 50, "yPct": 60 } }
 *   option_type = 'font':
 *     { "fonts": [ { "id": "serif", "label": "Classic Serif",
 *                    "stack": "var(--font-fraunces), Georgia, serif" }, ... ] }
 *   option_type = 'color':
 *     { "palette": [ { "id": "ink", "label": "Ink", "value": "#262119" }, ... ] }
 *   option_type = 'upload': not previewed here (checkout/cart flow owns it);
 *     the option is listed with a note and emits { value: "" }.
 *
 * All fields are optional; sensible defaults below. Positions are percentages
 * of the image box so the same customization_data reproduces on any render
 * size (cart thumbnail, order sheet, production file).
 *
 * OUTPUT (onChange): CustomizationValue[] — one entry per option row. This is
 * the `customization_data` JSON that will be attached to cart items.
 */

import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";

/* ------------------------------------------------------------------ types */

/** Mirrors a customization_options row (camelCase, as lib/schema.ts returns). */
export type CustomizationOption = {
  id: number;
  optionType: "text" | "color" | "font" | "upload";
  label: string;
  configJson: string | null;
  required: number | boolean;
};

export type CustomizationPosition = { xPct: number; yPct: number };

/** One entry per option — the cart-bound customization_data shape. */
export type CustomizationValue = {
  optionId: number;
  type: CustomizationOption["optionType"];
  value: string;
  font?: string;
  color?: string;
  position?: CustomizationPosition;
};

export type CustomizationPreviewProps = {
  /** Product image URL, e.g. getImageUrl(r2Key, 800). */
  productImage: string;
  productImageAlt?: string;
  options: CustomizationOption[];
  onChange?: (values: CustomizationValue[]) => void;
};

/* --------------------------------------------------------------- defaults */

type FontChoice = { id: string; label: string; stack: string };
type ColorChoice = { id: string; label: string; value: string };

const DEFAULT_FONTS: FontChoice[] = [
  { id: "serif", label: "Classic Serif", stack: "var(--font-fraunces), Georgia, serif" },
  { id: "sans", label: "Modern Sans", stack: "var(--font-geist-sans), system-ui, sans-serif" },
  { id: "mono", label: "Typewriter", stack: "var(--font-geist-mono), 'Courier New', monospace" },
  { id: "script", label: "Script", stack: "'Snell Roundhand', 'Segoe Script', 'Brush Script MT', cursive" },
];

const DEFAULT_PALETTE: ColorChoice[] = [
  { id: "ink", label: "Ink", value: "#262119" },
  { id: "pine", label: "Pine", value: "#2e5a4e" },
  { id: "amber", label: "Amber", value: "#b8862b" },
  { id: "white", label: "White", value: "#ffffff" },
  { id: "wine", label: "Wine", value: "#7a2e3f" },
];

const DEFAULT_MAX_CHARS = 30;
const DEFAULT_POSITION: CustomizationPosition = { xPct: 50, yPct: 55 };

function parseConfig(json: string | null): Record<string, unknown> {
  if (!json) return {};
  try {
    const parsed = JSON.parse(json);
    return parsed && typeof parsed === "object" ? (parsed as Record<string, unknown>) : {};
  } catch {
    return {}; // malformed config_json falls back to defaults, never crashes
  }
}

const clampPct = (n: number) => Math.min(97, Math.max(3, n));

/* -------------------------------------------------------------- component */

type TextState = { value: string; position: CustomizationPosition };

export default function CustomizationPreview({
  productImage,
  productImageAlt = "Product preview",
  options,
  onChange,
}: CustomizationPreviewProps) {
  const sorted = useMemo(() => {
    const byType = { text: [] as CustomizationOption[], font: [] as CustomizationOption[], color: [] as CustomizationOption[], upload: [] as CustomizationOption[] };
    for (const o of options) byType[o.optionType]?.push(o);
    return byType;
  }, [options]);

  const fontOption = sorted.font[0];
  const colorOption = sorted.color[0];

  const fonts = useMemo<FontChoice[]>(() => {
    const cfg = parseConfig(fontOption?.configJson ?? null);
    const list = Array.isArray(cfg.fonts) ? (cfg.fonts as FontChoice[]).filter((f) => f?.id && f?.stack) : [];
    return list.length > 0 ? list : DEFAULT_FONTS;
  }, [fontOption]);

  const palette = useMemo<ColorChoice[]>(() => {
    const cfg = parseConfig(colorOption?.configJson ?? null);
    const list = Array.isArray(cfg.palette) ? (cfg.palette as ColorChoice[]).filter((c) => c?.id && c?.value) : [];
    return list.length > 0 ? list : DEFAULT_PALETTE;
  }, [colorOption]);

  const [fontId, setFontId] = useState(() => fonts[0].id);
  const [colorId, setColorId] = useState(() => palette[0].id);
  const [texts, setTexts] = useState<Record<number, TextState>>(() => {
    const initial: Record<number, TextState> = {};
    sorted.text.forEach((opt, i) => {
      const cfg = parseConfig(opt.configJson);
      const pos = (cfg.defaultPosition as CustomizationPosition) ?? {
        xPct: DEFAULT_POSITION.xPct,
        yPct: clampPct(DEFAULT_POSITION.yPct + i * 12), // stagger multiple lines
      };
      initial[opt.id] = {
        value: typeof cfg.defaultText === "string" ? cfg.defaultText : "",
        position: { xPct: clampPct(pos.xPct), yPct: clampPct(pos.yPct) },
      };
    });
    return initial;
  });

  const activeFont = fonts.find((f) => f.id === fontId) ?? fonts[0];
  const activeColor = palette.find((c) => c.id === colorId) ?? palette[0];

  /* ---- emit customization_data on every state change ---- */
  const onChangeRef = useRef(onChange);
  onChangeRef.current = onChange;
  useEffect(() => {
    const values: CustomizationValue[] = options.map((opt) => {
      switch (opt.optionType) {
        case "text": {
          const t = texts[opt.id];
          return {
            optionId: opt.id,
            type: "text" as const,
            value: t?.value ?? "",
            font: activeFont.id,
            color: activeColor.value,
            position: t?.position,
          };
        }
        case "font":
          return { optionId: opt.id, type: "font" as const, value: activeFont.id };
        case "color":
          return { optionId: opt.id, type: "color" as const, value: activeColor.value };
        case "upload":
          return { optionId: opt.id, type: "upload" as const, value: "" };
      }
    });
    onChangeRef.current?.(values);
  }, [options, texts, activeFont, activeColor]);

  /* ---- drag (Pointer Events: one code path for mouse + touch) ---- */
  const stageRef = useRef<HTMLDivElement>(null);
  const dragRef = useRef<{ optionId: number; pointerId: number } | null>(null);

  const moveTo = useCallback((optionId: number, clientX: number, clientY: number) => {
    const stage = stageRef.current;
    if (!stage) return;
    const rect = stage.getBoundingClientRect();
    const xPct = clampPct(((clientX - rect.left) / rect.width) * 100);
    const yPct = clampPct(((clientY - rect.top) / rect.height) * 100);
    setTexts((prev) => ({
      ...prev,
      [optionId]: { ...prev[optionId], position: { xPct: Math.round(xPct * 10) / 10, yPct: Math.round(yPct * 10) / 10 } },
    }));
  }, []);

  const handlePointerDown = (optionId: number) => (e: React.PointerEvent<HTMLDivElement>) => {
    e.preventDefault();
    dragRef.current = { optionId, pointerId: e.pointerId };
    e.currentTarget.setPointerCapture(e.pointerId);
    moveTo(optionId, e.clientX, e.clientY);
  };
  const handlePointerMove = (e: React.PointerEvent<HTMLDivElement>) => {
    const drag = dragRef.current;
    if (!drag || drag.pointerId !== e.pointerId) return;
    moveTo(drag.optionId, e.clientX, e.clientY);
  };
  const endDrag = (e: React.PointerEvent<HTMLDivElement>) => {
    if (dragRef.current?.pointerId === e.pointerId) dragRef.current = null;
  };

  /* Keyboard fallback so drag isn't pointer-only (a11y). */
  const nudge = (optionId: number) => (e: React.KeyboardEvent<HTMLDivElement>) => {
    const step = e.shiftKey ? 5 : 1;
    const delta: Record<string, [number, number]> = {
      ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step],
    };
    const d = delta[e.key];
    if (!d) return;
    e.preventDefault();
    setTexts((prev) => {
      const cur = prev[optionId];
      return {
        ...prev,
        [optionId]: { ...cur, position: { xPct: clampPct(cur.position.xPct + d[0]), yPct: clampPct(cur.position.yPct + d[1]) } },
      };
    });
  };

  const setTextValue = (optionId: number, value: string) =>
    setTexts((prev) => ({ ...prev, [optionId]: { ...prev[optionId], value } }));

  /* ------------------------------------------------------------- render */
  return (
    <div className="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
      {/* Stage: product image + draggable text overlays */}
      <div
        ref={stageRef}
        className="relative select-none overflow-hidden rounded-md border border-line bg-white"
        style={{ containerType: "inline-size" }} // enables cqw font sizing below
      >
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src={productImage}
          alt={productImageAlt}
          draggable={false}
          className="aspect-square w-full object-cover"
        />
        {sorted.text.map((opt) => {
          const t = texts[opt.id];
          if (!t) return null;
          return (
            <div
              key={opt.id}
              role="slider"
              tabIndex={0}
              aria-label={`Position of “${opt.label}” — drag or use arrow keys`}
              aria-valuetext={`${t.position.xPct}% across, ${t.position.yPct}% down`}
              aria-valuenow={Math.round(t.position.xPct)}
              onPointerDown={handlePointerDown(opt.id)}
              onPointerMove={handlePointerMove}
              onPointerUp={endDrag}
              onPointerCancel={endDrag}
              onKeyDown={nudge(opt.id)}
              className="absolute max-w-[90%] cursor-grab whitespace-pre rounded-sm px-1 leading-tight outline-offset-4 active:cursor-grabbing focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber"
              style={{
                left: `${t.position.xPct}%`,
                top: `${t.position.yPct}%`,
                transform: "translate(-50%, -50%)",
                touchAction: "none", // required so touch drags aren't hijacked by scrolling
                fontFamily: activeFont.stack,
                color: activeColor.value,
                fontSize: "clamp(1.1rem, 5cqw, 2rem)",
                textShadow: activeColor.id === "white" ? "0 1px 3px rgba(0,0,0,.45)" : "0 1px 2px rgba(255,255,255,.35)",
              }}
            >
              {t.value || (
                <span className="rounded-sm border border-dashed border-current px-2 py-0.5 text-sm opacity-70">
                  {opt.label}
                </span>
              )}
            </div>
          );
        })}
        <p className="pointer-events-none absolute bottom-2 left-2 rounded-sm bg-white/80 px-2 py-0.5 font-mono text-[11px] text-ink/60">
          Drag text to position it
        </p>
      </div>

      {/* Controls */}
      <div className="space-y-5">
        {sorted.text.map((opt) => {
          const cfg = parseConfig(opt.configJson);
          const maxChars = typeof cfg.maxChars === "number" ? cfg.maxChars : DEFAULT_MAX_CHARS;
          const placeholder = typeof cfg.placeholder === "string" ? cfg.placeholder : "Type your text";
          const t = texts[opt.id];
          return (
            <label key={opt.id} className="block">
              <span className="mb-1.5 flex items-baseline justify-between text-sm text-ink/80">
                <span>
                  {opt.label}
                  {opt.required ? <span className="ml-1 text-amber" title="Required">*</span> : null}
                </span>
                <span className="font-mono text-xs text-ink/40">
                  {t?.value.length ?? 0}/{maxChars}
                </span>
              </span>
              <input
                type="text"
                value={t?.value ?? ""}
                maxLength={maxChars}
                placeholder={placeholder}
                required={Boolean(opt.required)}
                onChange={(e) => setTextValue(opt.id, e.target.value)}
                className="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-ink/35 focus:border-pine focus:outline-none focus:ring-1 focus:ring-pine"
              />
            </label>
          );
        })}

        {fontOption && (
          <fieldset>
            <legend className="mb-1.5 text-sm text-ink/80">
              {fontOption.label}
              {fontOption.required ? <span className="ml-1 text-amber">*</span> : null}
            </legend>
            <div className="flex flex-wrap gap-2">
              {fonts.map((f) => (
                <button
                  key={f.id}
                  type="button"
                  onClick={() => setFontId(f.id)}
                  aria-pressed={f.id === fontId}
                  className={`rounded-sm border px-3 py-1.5 text-sm transition-colors ${
                    f.id === fontId
                      ? "border-pine bg-pine text-white"
                      : "border-line bg-white text-ink/75 hover:border-pine/50"
                  }`}
                  style={{ fontFamily: f.stack }}
                >
                  {f.label}
                </button>
              ))}
            </div>
          </fieldset>
        )}

        {colorOption && (
          <fieldset>
            <legend className="mb-1.5 text-sm text-ink/80">
              {colorOption.label}
              {colorOption.required ? <span className="ml-1 text-amber">*</span> : null}
            </legend>
            <div className="flex flex-wrap gap-2">
              {palette.map((c) => (
                <button
                  key={c.id}
                  type="button"
                  onClick={() => setColorId(c.id)}
                  aria-pressed={c.id === colorId}
                  title={c.label}
                  className={`flex items-center gap-2 rounded-sm border px-2.5 py-1.5 text-sm transition-colors ${
                    c.id === colorId
                      ? "border-pine bg-pine/5 text-ink"
                      : "border-line bg-white text-ink/70 hover:border-pine/50"
                  }`}
                >
                  <span
                    aria-hidden
                    className="h-4 w-4 rounded-full border border-ink/20"
                    style={{ backgroundColor: c.value }}
                  />
                  {c.label}
                </button>
              ))}
            </div>
          </fieldset>
        )}

        {sorted.upload.map((opt) => (
          <p
            key={opt.id}
            className="rounded-md border border-dashed border-line bg-white/60 px-3 py-2 text-sm text-ink/60"
          >
            {opt.label}
            {opt.required ? <span className="ml-1 text-amber">*</span> : null} — you&apos;ll
            upload your photo at checkout.
          </p>
        ))}
      </div>
    </div>
  );
}
