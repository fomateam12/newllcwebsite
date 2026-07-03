"use client";

import { useRouter } from "next/navigation";
import { useCallback, useEffect, useId, useRef, useState } from "react";

type Hit = { id: number; name: string; slug: string; thumb: string | null };

const DEBOUNCE_MS = 250;

/**
 * Header search with debounced autocomplete against /api/search.
 * Keyboard: ArrowUp/Down move through hits, Enter opens the active hit,
 * Escape closes. Closes on outside click / focus loss.
 */
export function SearchBox({ className = "" }: { className?: string }) {
  const router = useRouter();
  const listId = useId();
  const rootRef = useRef<HTMLDivElement>(null);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const lastIssued = useRef(0);

  const [query, setQuery] = useState("");
  const [hits, setHits] = useState<Hit[]>([]);
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);

  const close = useCallback(() => {
    setOpen(false);
    setActive(-1);
  }, []);

  // Outside click closes the dropdown.
  useEffect(() => {
    if (!open) return;
    const onPointerDown = (e: PointerEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) close();
    };
    document.addEventListener("pointerdown", onPointerDown);
    return () => document.removeEventListener("pointerdown", onPointerDown);
  }, [open, close]);

  const search = useCallback((term: string) => {
    if (timer.current) clearTimeout(timer.current);
    const trimmed = term.trim();
    if (trimmed.length < 2) {
      setHits([]);
      setOpen(false);
      return;
    }
    timer.current = setTimeout(async () => {
      const issued = ++lastIssued.current;
      try {
        const res = await fetch(`/api/search?q=${encodeURIComponent(trimmed)}`);
        if (!res.ok) return;
        const data = (await res.json()) as { results: Hit[] };
        if (issued !== lastIssued.current) return; // stale response
        setHits(data.results);
        setOpen(true);
        setActive(-1);
      } catch {
        // network hiccup — keep whatever is on screen
      }
    }, DEBOUNCE_MS);
  }, []);

  const go = useCallback(
    (hit: Hit) => {
      close();
      setQuery("");
      router.push(`/urun/${hit.slug}`);
    },
    [close, router],
  );

  const onKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Escape") {
      close();
      return;
    }
    if (!open || hits.length === 0) return;
    if (e.key === "ArrowDown") {
      e.preventDefault();
      setActive((a) => (a + 1) % hits.length);
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      setActive((a) => (a <= 0 ? hits.length - 1 : a - 1));
    } else if (e.key === "Enter" && active >= 0) {
      e.preventDefault();
      go(hits[active]);
    }
  };

  return (
    <div ref={rootRef} className={`relative ${className}`}>
      <label className="sr-only" htmlFor={`${listId}-input`}>
        Search products
      </label>
      <input
        id={`${listId}-input`}
        type="search"
        autoComplete="off"
        placeholder="Search gifts, tumblers, blankets…"
        value={query}
        onChange={(e) => {
          setQuery(e.target.value);
          search(e.target.value);
        }}
        onFocus={() => {
          if (hits.length > 0 && query.trim().length >= 2) setOpen(true);
        }}
        onKeyDown={onKeyDown}
        role="combobox"
        aria-expanded={open}
        aria-controls={listId}
        aria-activedescendant={active >= 0 ? `${listId}-opt-${active}` : undefined}
        aria-autocomplete="list"
        className="w-full rounded-sm border border-line bg-white px-3 py-1.5 text-sm text-ink placeholder:text-ink/40 focus:border-pine focus:outline-none focus:ring-1 focus:ring-pine"
      />
      {open && (
        <ul
          id={listId}
          role="listbox"
          aria-label="Search results"
          className="absolute left-0 right-0 top-full z-50 mt-1 max-h-96 overflow-auto rounded-md border border-line bg-white py-1 shadow-lg"
        >
          {hits.length === 0 ? (
            <li className="px-3 py-2 text-sm text-ink/50">No matches — try another word.</li>
          ) : (
            hits.map((hit, i) => (
              <li
                key={hit.id}
                id={`${listId}-opt-${i}`}
                role="option"
                aria-selected={i === active}
                onPointerDown={(e) => {
                  // pointerdown (not click) so it beats the outside-click closer
                  e.preventDefault();
                  go(hit);
                }}
                onPointerMove={() => setActive(i)}
                className={`flex cursor-pointer items-center gap-3 px-3 py-2 text-sm ${
                  i === active ? "bg-pine/10 text-pine-deep" : "text-ink/90"
                }`}
              >
                <span className="h-9 w-9 shrink-0 overflow-hidden rounded-sm border border-line bg-line/30">
                  {hit.thumb ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img src={hit.thumb} alt="" className="h-full w-full object-cover" loading="lazy" />
                  ) : null}
                </span>
                <span className="line-clamp-2 leading-snug">{hit.name}</span>
              </li>
            ))
          )}
        </ul>
      )}
    </div>
  );
}
