"use client";

import { useRouter } from "next/navigation";
import { useEffect, useId, useRef, useState } from "react";
import type { ProductSort } from "@/lib/catalog";

const SORT_OPTIONS: { value: ProductSort; label: string }[] = [
  { value: "newest", label: "Newest" },
  { value: "price-asc", label: "Price: low to high" },
  { value: "price-desc", label: "Price: high to low" },
  { value: "name", label: "Name A–Z" },
];

type Props = {
  slug: string;
  sort: ProductSort;
  minPrice?: number;
  maxPrice?: number;
};

function buildHref(slug: string, sort: ProductSort, min: string, max: string): string {
  const params = new URLSearchParams();
  if (sort !== "newest") params.set("sort", sort);
  if (min !== "") params.set("min", min);
  if (max !== "") params.set("max", max);
  const qs = params.toString();
  return `/kategori/${slug}${qs ? `?${qs}` : ""}`;
}

/**
 * Server-driven sort + price-range controls. Everything resolves to URL
 * query params (?sort=&min=&max=) so results stay shareable/crawlable.
 * Inline on desktop; behind a "Filter & sort" button opening a bottom
 * sheet on mobile.
 */
export function CatalogFilters({ slug, sort, minPrice, maxPrice }: Props) {
  const router = useRouter();
  const [sheetOpen, setSheetOpen] = useState(false);
  const openerRef = useRef<HTMLButtonElement>(null);
  const sheetRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!sheetOpen) return;
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        setSheetOpen(false);
        openerRef.current?.focus();
      }
    };
    document.addEventListener("keydown", onKeyDown);
    document.body.style.overflow = "hidden";
    sheetRef.current?.focus();
    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.style.overflow = "";
    };
  }, [sheetOpen]);

  const apply = (nextSort: ProductSort, min: string, max: string) => {
    setSheetOpen(false);
    router.push(buildHref(slug, nextSort, min, max));
  };

  const activeFilterCount = (minPrice !== undefined ? 1 : 0) + (maxPrice !== undefined ? 1 : 0);

  return (
    <>
      {/* Desktop: inline controls */}
      <div className="hidden sm:block">
        <FilterForm slug={slug} sort={sort} minPrice={minPrice} maxPrice={maxPrice} onApply={apply} layout="row" />
      </div>

      {/* Mobile: bottom sheet */}
      <div className="sm:hidden">
        <button
          ref={openerRef}
          type="button"
          onClick={() => setSheetOpen(true)}
          aria-expanded={sheetOpen}
          className="inline-flex items-center gap-2 rounded-sm border border-line bg-white px-3 py-2 text-sm font-medium text-ink/80 hover:border-pine hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
        >
          <svg aria-hidden viewBox="0 0 16 16" className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.5">
            <path d="M1 3h14M4 8h8M6 13h4" strokeLinecap="round" />
          </svg>
          Filter &amp; sort
          {activeFilterCount > 0 && (
            <span className="font-mono text-xs text-amber">({activeFilterCount})</span>
          )}
        </button>

        {sheetOpen && (
          <div className="fixed inset-0 z-50">
            <button
              type="button"
              aria-label="Close filters"
              onClick={() => setSheetOpen(false)}
              className="absolute inset-0 bg-ink/40"
              tabIndex={-1}
            />
            <div
              ref={sheetRef}
              role="dialog"
              aria-modal="true"
              aria-label="Filter and sort"
              tabIndex={-1}
              className="absolute inset-x-0 bottom-0 rounded-t-xl border-t border-line bg-paper p-5 pb-8 shadow-xl outline-none"
            >
              <div className="mb-4 flex items-center justify-between">
                <h2 className="font-display text-lg text-ink">Filter &amp; sort</h2>
                <button
                  type="button"
                  onClick={() => {
                    setSheetOpen(false);
                    openerRef.current?.focus();
                  }}
                  aria-label="Close filters"
                  className="flex h-10 w-10 items-center justify-center rounded-sm text-ink/70 hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
                >
                  <svg aria-hidden viewBox="0 0 16 16" className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="1.5">
                    <path d="M3 3l10 10M13 3L3 13" strokeLinecap="round" />
                  </svg>
                </button>
              </div>
              <FilterForm slug={slug} sort={sort} minPrice={minPrice} maxPrice={maxPrice} onApply={apply} layout="stack" />
            </div>
          </div>
        )}
      </div>
    </>
  );
}

function FilterForm({
  slug,
  sort,
  minPrice,
  maxPrice,
  onApply,
  layout,
}: Props & {
  onApply: (sort: ProductSort, min: string, max: string) => void;
  layout: "row" | "stack";
}) {
  const id = useId();
  const [localSort, setLocalSort] = useState<ProductSort>(sort);
  const [min, setMin] = useState(minPrice !== undefined ? String(minPrice) : "");
  const [max, setMax] = useState(maxPrice !== undefined ? String(maxPrice) : "");
  const row = layout === "row";
  const hasActiveInput = min !== "" || max !== "" || localSort !== "newest";

  return (
    <form
      action={`/kategori/${slug}`}
      method="get"
      onSubmit={(e) => {
        e.preventDefault();
        onApply(localSort, min, max);
      }}
      className={row ? "flex flex-wrap items-end gap-3" : "flex flex-col gap-4"}
    >
      <div className={row ? "" : "flex flex-col"}>
        <label htmlFor={`${id}-sort`} className="mb-1 block font-mono text-xs uppercase tracking-wide text-ink/50">
          Sort by
        </label>
        <select
          id={`${id}-sort`}
          name="sort"
          value={localSort}
          onChange={(e) => {
            const next = e.target.value as ProductSort;
            setLocalSort(next);
            // Sort changes apply immediately (desktop pattern); on the
            // sheet the Apply button confirms everything at once.
            if (row) onApply(next, min, max);
          }}
          className="rounded-sm border border-line bg-white px-2.5 py-1.5 text-sm text-ink focus:border-pine focus:outline-none focus:ring-1 focus:ring-pine"
        >
          {SORT_OPTIONS.map((o) => (
            <option key={o.value} value={o.value}>
              {o.label}
            </option>
          ))}
        </select>
      </div>

      <fieldset className={row ? "flex items-end gap-2" : "flex flex-col"}>
        <legend className="mb-1 block font-mono text-xs uppercase tracking-wide text-ink/50">
          Price ($)
        </legend>
        <div className="flex items-center gap-2">
          <label className="sr-only" htmlFor={`${id}-min`}>
            Minimum price
          </label>
          <input
            id={`${id}-min`}
            name="min"
            type="number"
            inputMode="decimal"
            min={0}
            step="any"
            placeholder="Min"
            value={min}
            onChange={(e) => setMin(e.target.value)}
            className="w-20 rounded-sm border border-line bg-white px-2.5 py-1.5 text-sm text-ink placeholder:text-ink/40 focus:border-pine focus:outline-none focus:ring-1 focus:ring-pine"
          />
          <span aria-hidden className="text-ink/40">–</span>
          <label className="sr-only" htmlFor={`${id}-max`}>
            Maximum price
          </label>
          <input
            id={`${id}-max`}
            name="max"
            type="number"
            inputMode="decimal"
            min={0}
            step="any"
            placeholder="Max"
            value={max}
            onChange={(e) => setMax(e.target.value)}
            className="w-20 rounded-sm border border-line bg-white px-2.5 py-1.5 text-sm text-ink placeholder:text-ink/40 focus:border-pine focus:outline-none focus:ring-1 focus:ring-pine"
          />
        </div>
      </fieldset>

      <div className={row ? "flex items-center gap-3" : "flex items-center gap-4"}>
        <button
          type="submit"
          className="rounded-sm bg-pine px-4 py-1.5 text-sm font-medium text-paper transition-colors hover:bg-pine-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine"
        >
          Apply
        </button>
        {hasActiveInput && (
          <button
            type="button"
            onClick={() => {
              setLocalSort("newest");
              setMin("");
              setMax("");
              onApply("newest", "", "");
            }}
            className="text-sm text-ink/60 underline-offset-4 hover:text-pine hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
          >
            Clear
          </button>
        )}
      </div>
    </form>
  );
}
