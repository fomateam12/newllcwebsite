"use client";

/**
 * Product gallery — thumbnail rail + main image with zoom-on-hover and
 * touch swipe. URLs are resolved server-side (lib/r2 getImageUrl) and
 * passed in as plain strings so this client bundle never imports the
 * Cloudflare context.
 *
 * - Desktop: vertical thumb rail on the left (DiscountMugs-style), hover
 *   magnifies using the 1200px source (backgroundPosition follows cursor).
 * - Mobile: horizontal thumb strip below, swipe left/right via Pointer
 *   Events (one code path for touch + pen).
 * - Keyboard: the stage is focusable; ←/→ move between images; thumbs are
 *   real buttons announcing the current slide.
 */

import { useRef, useState } from "react";

export type GalleryImage = {
  id: number;
  /** w=200 thumb URL */
  thumb: string;
  /** w=800 main URL */
  main: string;
  /** w=1200 zoom source URL */
  zoom: string;
  alt: string;
};

const SWIPE_THRESHOLD_PX = 40;
const ZOOM_SCALE = 2.25;

export function Gallery({ images, productName }: { images: GalleryImage[]; productName: string }) {
  const [index, setIndex] = useState(0);
  const [zoom, setZoom] = useState<{ xPct: number; yPct: number } | null>(null);
  const swipeStart = useRef<{ x: number; y: number; pointerId: number } | null>(null);
  const stageRef = useRef<HTMLDivElement>(null);

  if (images.length === 0) {
    return (
      <div className="flex aspect-square items-center justify-center rounded-md border border-line bg-white text-ink/40">
        No photo yet
      </div>
    );
  }

  const active = images[Math.min(index, images.length - 1)];
  const go = (next: number) => {
    setIndex((next + images.length) % images.length);
    setZoom(null);
  };

  /* ---- zoom (mouse/pen hover only; touch is reserved for swiping) ---- */
  const handleZoomMove = (e: React.PointerEvent<HTMLDivElement>) => {
    if (e.pointerType === "touch") return;
    const rect = e.currentTarget.getBoundingClientRect();
    setZoom({
      xPct: Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100)),
      yPct: Math.min(100, Math.max(0, ((e.clientY - rect.top) / rect.height) * 100)),
    });
  };

  /* ---- swipe ---- */
  const handlePointerDown = (e: React.PointerEvent<HTMLDivElement>) => {
    if (e.pointerType !== "touch" || images.length < 2) return;
    swipeStart.current = { x: e.clientX, y: e.clientY, pointerId: e.pointerId };
  };
  const handlePointerUp = (e: React.PointerEvent<HTMLDivElement>) => {
    const start = swipeStart.current;
    swipeStart.current = null;
    if (!start || start.pointerId !== e.pointerId) return;
    const dx = e.clientX - start.x;
    const dy = e.clientY - start.y;
    if (Math.abs(dx) < SWIPE_THRESHOLD_PX || Math.abs(dx) < Math.abs(dy)) return;
    go(dx < 0 ? index + 1 : index - 1);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLDivElement>) => {
    if (e.key === "ArrowRight") {
      e.preventDefault();
      go(index + 1);
    } else if (e.key === "ArrowLeft") {
      e.preventDefault();
      go(index - 1);
    }
  };

  return (
    <div className="flex flex-col-reverse gap-3 lg:flex-row">
      {/* Thumbnail rail */}
      {images.length > 1 && (
        <div
          className="flex gap-2 overflow-x-auto lg:max-h-[34rem] lg:w-20 lg:flex-none lg:flex-col lg:overflow-y-auto lg:overflow-x-hidden"
          role="tablist"
          aria-label={`${productName} photos`}
        >
          {images.map((img, i) => (
            <button
              key={img.id}
              type="button"
              role="tab"
              aria-selected={i === index}
              aria-label={`Photo ${i + 1} of ${images.length}`}
              onClick={() => go(i)}
              className={`h-16 w-16 flex-none overflow-hidden rounded-sm border transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine ${
                i === index ? "border-pine ring-1 ring-pine" : "border-line opacity-80 hover:border-pine/50 hover:opacity-100"
              }`}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={img.thumb} alt="" loading="lazy" className="h-full w-full object-cover" />
            </button>
          ))}
        </div>
      )}

      {/* Stage */}
      <div className="min-w-0 flex-1">
        <div
          ref={stageRef}
          tabIndex={0}
          role="group"
          aria-roledescription="image carousel"
          aria-label={`${active.alt} — photo ${index + 1} of ${images.length}. Use left and right arrow keys to browse.`}
          onKeyDown={handleKeyDown}
          onPointerDown={handlePointerDown}
          onPointerUp={handlePointerUp}
          onPointerMove={handleZoomMove}
          onPointerLeave={() => setZoom(null)}
          className="relative cursor-zoom-in select-none overflow-hidden rounded-md border border-line bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine"
          style={{ touchAction: "pan-y" }} // vertical scroll stays native; horizontal is ours
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={active.main}
            alt={active.alt}
            draggable={false}
            className="aspect-square w-full object-cover"
          />
          {/* Magnifier overlay (mouse hover) */}
          {zoom && (
            <div
              aria-hidden
              className="pointer-events-none absolute inset-0"
              style={{
                backgroundImage: `url("${active.zoom}")`,
                backgroundSize: `${ZOOM_SCALE * 100}%`,
                backgroundPosition: `${zoom.xPct}% ${zoom.yPct}%`,
                backgroundRepeat: "no-repeat",
              }}
            />
          )}
          {images.length > 1 && (
            <>
              <button
                type="button"
                aria-label="Previous photo"
                onClick={() => go(index - 1)}
                className="absolute left-2 top-1/2 -translate-y-1/2 rounded-full border border-line bg-white/85 px-2.5 py-1 font-mono text-sm text-ink/70 shadow-sm hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
              >
                ←
              </button>
              <button
                type="button"
                aria-label="Next photo"
                onClick={() => go(index + 1)}
                className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full border border-line bg-white/85 px-2.5 py-1 font-mono text-sm text-ink/70 shadow-sm hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
              >
                →
              </button>
              <span className="absolute bottom-2 right-2 rounded-sm bg-white/80 px-2 py-0.5 font-mono text-[11px] text-ink/60">
                {index + 1}/{images.length}
              </span>
            </>
          )}
        </div>
        <p aria-live="polite" className="sr-only">
          Photo {index + 1} of {images.length}
        </p>
      </div>
    </div>
  );
}
