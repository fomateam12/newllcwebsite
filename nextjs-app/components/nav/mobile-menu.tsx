"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import type { CategoryNavNode } from "@/lib/catalog";

/**
 * Mobile navigation (hidden at md+): hamburger button opening a slide-in
 * panel with the full category tree. Subtrees expand as accordions.
 * Closes on Escape, backdrop tap, or link tap; locks body scroll while open.
 */
export function MobileMenu({ nav }: { nav: CategoryNavNode[] }) {
  const [open, setOpen] = useState(false);
  const [expanded, setExpanded] = useState<Set<number>>(new Set());
  const panelRef = useRef<HTMLDivElement>(null);
  const openerRef = useRef<HTMLButtonElement>(null);

  useEffect(() => {
    if (!open) return;
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        setOpen(false);
        openerRef.current?.focus();
      }
    };
    document.addEventListener("keydown", onKeyDown);
    document.body.style.overflow = "hidden";
    panelRef.current?.focus();
    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.style.overflow = "";
    };
  }, [open]);

  const toggle = (id: number) =>
    setExpanded((prev) => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });

  const renderNode = (node: CategoryNavNode, depth: number) => (
    <li key={node.id}>
      <div className="flex items-stretch">
        <Link
          href={`/kategori/${node.slug}`}
          onClick={() => setOpen(false)}
          className={`flex-1 rounded-sm py-2.5 text-ink/85 hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine ${
            depth === 0 ? "font-medium" : "text-sm"
          }`}
          style={{ paddingLeft: `${depth * 1}rem` }}
        >
          {node.name}
          <span className="ml-2 font-mono text-xs text-ink/40">{node.count}</span>
        </Link>
        {node.children.length > 0 && (
          <button
            type="button"
            onClick={() => toggle(node.id)}
            aria-expanded={expanded.has(node.id)}
            aria-label={`${expanded.has(node.id) ? "Collapse" : "Expand"} ${node.name}`}
            className="flex w-11 items-center justify-center text-ink/50 hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
          >
            <svg
              aria-hidden
              viewBox="0 0 12 12"
              className={`h-3 w-3 transition-transform ${expanded.has(node.id) ? "rotate-180" : ""}`}
              fill="none"
              stroke="currentColor"
              strokeWidth="1.5"
            >
              <path d="M2 4l4 4 4-4" />
            </svg>
          </button>
        )}
      </div>
      {node.children.length > 0 && expanded.has(node.id) && (
        <ul className="border-l border-line" style={{ marginLeft: `${depth * 1 + 0.25}rem` }}>
          {node.children.map((child) => renderNode(child, depth + 1))}
        </ul>
      )}
    </li>
  );

  return (
    <div className="md:hidden">
      <button
        ref={openerRef}
        type="button"
        onClick={() => setOpen(true)}
        aria-label="Open menu"
        aria-expanded={open}
        className="flex h-10 w-10 items-center justify-center rounded-sm text-ink/80 hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
      >
        <svg aria-hidden viewBox="0 0 20 20" className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="1.5">
          <path d="M2 5h16M2 10h16M2 15h16" strokeLinecap="round" />
        </svg>
      </button>

      {open && (
        <div className="fixed inset-0 z-50">
          <button
            type="button"
            aria-label="Close menu"
            onClick={() => setOpen(false)}
            className="absolute inset-0 bg-ink/40"
            tabIndex={-1}
          />
          <div
            ref={panelRef}
            role="dialog"
            aria-modal="true"
            aria-label="Site menu"
            tabIndex={-1}
            className="absolute inset-y-0 left-0 flex w-80 max-w-[85vw] flex-col bg-paper shadow-xl outline-none"
          >
            <div className="flex items-center justify-between border-b border-line px-4 py-3">
              <span className="font-display text-lg text-pine-deep">Browse</span>
              <button
                type="button"
                onClick={() => {
                  setOpen(false);
                  openerRef.current?.focus();
                }}
                aria-label="Close menu"
                className="flex h-10 w-10 items-center justify-center rounded-sm text-ink/70 hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
              >
                <svg aria-hidden viewBox="0 0 16 16" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.5">
                  <path d="M3 3l10 10M13 3L3 13" strokeLinecap="round" />
                </svg>
              </button>
            </div>
            <nav aria-label="Categories" className="flex-1 overflow-y-auto px-4 py-2">
              <ul>{nav.map((root) => renderNode(root, 0))}</ul>
            </nav>
            <div className="border-t border-line px-4 py-3 text-xs text-ink/50">
              Made to order in our Georgia workshop
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
