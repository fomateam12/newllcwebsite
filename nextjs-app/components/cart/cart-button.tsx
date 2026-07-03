"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { useCart } from "./cart-context";
import { MiniCart } from "./mini-cart";

/**
 * Header cart control: live item count + mini-cart dropdown.
 *
 * Hovering opens the preview (with a small close delay so the pointer can
 * travel into it); clicking/tapping or pressing Enter toggles it, which is
 * also the touch-device path. Escape and outside clicks close it. The
 * dropdown itself links to /sepet and /checkout.
 */
export function CartButton() {
  const { count } = useCart();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const closeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const cancelClose = useCallback(() => {
    if (closeTimer.current) {
      clearTimeout(closeTimer.current);
      closeTimer.current = null;
    }
  }, []);

  const scheduleClose = useCallback(() => {
    cancelClose();
    closeTimer.current = setTimeout(() => setOpen(false), 180);
  }, [cancelClose]);

  // Close on outside click / Escape.
  useEffect(() => {
    if (!open) return;
    function onPointerDown(e: PointerEvent) {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    }
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") setOpen(false);
    }
    document.addEventListener("pointerdown", onPointerDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("pointerdown", onPointerDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [open]);

  useEffect(() => cancelClose, [cancelClose]);

  return (
    <div
      ref={rootRef}
      className="relative"
      onMouseEnter={() => {
        cancelClose();
        setOpen(true);
      }}
      onMouseLeave={scheduleClose}
    >
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
        aria-haspopup="dialog"
        aria-label={`Cart, ${count} item${count === 1 ? "" : "s"}`}
        className="inline-flex items-center gap-1.5 rounded-sm border border-dashed border-pine/50 bg-white/60 px-2 py-0.5 font-mono text-sm text-pine hover:border-pine hover:text-pine-deep"
      >
        <span aria-hidden className="h-1.5 w-1.5 rounded-full border border-current" />
        Cart
        {count > 0 && <span aria-hidden>({count})</span>}
      </button>
      {open && <MiniCart onNavigate={() => setOpen(false)} />}
    </div>
  );
}
