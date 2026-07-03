"use client";

import Link from "next/link";
import { useCallback, useEffect, useRef, useState } from "react";
import type { CategoryNavNode } from "@/lib/catalog";

/**
 * Desktop mega-menu (hidden below md). Roots with children open a panel
 * of subcategory columns; leaf roots are plain links. Opens on hover or
 * click/Enter, closes on Escape, outside click, or mouse leave.
 */
export function DesktopNav({ nav }: { nav: CategoryNavNode[] }) {
  const [openId, setOpenId] = useState<number | null>(null);
  const rootRef = useRef<HTMLElement>(null);
  const close = useCallback(() => setOpenId(null), []);

  useEffect(() => {
    if (openId === null) return;
    const onPointerDown = (e: PointerEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) close();
    };
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") close();
    };
    document.addEventListener("pointerdown", onPointerDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("pointerdown", onPointerDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [openId, close]);

  return (
    <nav ref={rootRef} aria-label="Categories" className="hidden md:block">
      <ul className="flex flex-wrap items-center gap-1">
        {nav.map((root) =>
          root.children.length === 0 ? (
            <li key={root.id}>
              <Link
                href={`/kategori/${root.slug}`}
                className="inline-block rounded-sm px-3 py-2 text-sm font-medium text-ink/80 hover:bg-pine/5 hover:text-pine-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
              >
                {root.name}
              </Link>
            </li>
          ) : (
            <li
              key={root.id}
              className="relative"
              onMouseEnter={() => setOpenId(root.id)}
              onMouseLeave={() => setOpenId((id) => (id === root.id ? null : id))}
            >
              <button
                type="button"
                aria-expanded={openId === root.id}
                aria-haspopup="true"
                onClick={() => setOpenId((id) => (id === root.id ? null : root.id))}
                className="inline-flex items-center gap-1 rounded-sm px-3 py-2 text-sm font-medium text-ink/80 hover:bg-pine/5 hover:text-pine-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine aria-expanded:bg-pine/5 aria-expanded:text-pine-deep"
              >
                {root.name}
                <svg
                  aria-hidden
                  viewBox="0 0 12 12"
                  className={`h-2.5 w-2.5 transition-transform ${openId === root.id ? "rotate-180" : ""}`}
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="1.5"
                >
                  <path d="M2 4l4 4 4-4" />
                </svg>
              </button>
              {openId === root.id && (
                <div className="absolute left-0 top-full z-50 min-w-64 rounded-md border border-line bg-white p-4 shadow-lg">
                  <Link
                    href={`/kategori/${root.slug}`}
                    onClick={close}
                    className="font-display text-base text-pine-deep hover:underline"
                  >
                    Shop all {root.name}
                    <span className="ml-2 font-mono text-xs text-ink/40">{root.count}</span>
                  </Link>
                  <ul className="mt-3 grid gap-x-8 gap-y-1.5" style={{ gridTemplateColumns: `repeat(${Math.min(3, Math.ceil(root.children.length / 8))}, minmax(10rem, 1fr))` }}>
                    {root.children.map((child) => (
                      <li key={child.id}>
                        <Link
                          href={`/kategori/${child.slug}`}
                          onClick={close}
                          className="block rounded-sm py-1 text-sm text-ink/75 hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
                        >
                          {child.name}
                          <span className="ml-1.5 font-mono text-xs text-ink/40">{child.count}</span>
                        </Link>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </li>
          ),
        )}
      </ul>
    </nav>
  );
}
