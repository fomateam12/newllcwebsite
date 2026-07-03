import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ProductCard } from "@/components/product-card";
import {
  countActiveProducts,
  countActiveProductsFiltered,
  getAllCategories,
  getCategoryThumb,
  getProductsFiltered,
  PRODUCT_SORTS,
  subtreeIds,
  type ProductFilter,
  type ProductSort,
} from "@/lib/catalog";
import { getImageUrl } from "@/lib/r2";
import { absoluteUrl } from "@/lib/site";
import { CatalogFilters } from "./catalog-filters";

export const dynamic = "force-dynamic";

const PAGE_SIZE = 24;

type Props = {
  params: Promise<{ slug: string }>;
  searchParams: Promise<{ page?: string; sort?: string; min?: string; max?: string }>;
};

function parsePrice(raw: string | undefined): number | undefined {
  if (raw === undefined || raw === "") return undefined;
  const n = Number.parseFloat(raw);
  return Number.isFinite(n) && n >= 0 ? n : undefined;
}

/** Query string for pagination links, preserving active sort/filters. */
function pageHref(slug: string, page: number, filter: ProductFilter): string {
  const params = new URLSearchParams();
  if (filter.sort && filter.sort !== "newest") params.set("sort", filter.sort);
  if (filter.minPrice !== undefined) params.set("min", String(filter.minPrice));
  if (filter.maxPrice !== undefined) params.set("max", String(filter.maxPrice));
  if (page > 1) params.set("page", String(page));
  const qs = params.toString();
  return `/kategori/${slug}${qs ? `?${qs}` : ""}`;
}

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  const all = await getAllCategories();
  const cat = all.find((c) => c.slug === slug);
  if (!cat) return { title: "Category" };
  const description = `Shop ${cat.name} at FomaFamily — made-to-order personalized gifts, engraved and printed in our workshop. Custom text, colors and photo uploads.`;
  const canonical = absoluteUrl(`/kategori/${cat.slug}`);
  const thumbKey = await getCategoryThumb(subtreeIds(all, cat.id));
  const ogImages = thumbKey
    ? [{ url: absoluteUrl(getImageUrl(thumbKey, 800)), alt: cat.name }]
    : undefined;
  return {
    title: cat.name,
    description,
    alternates: { canonical },
    openGraph: {
      title: cat.name,
      description,
      type: "website",
      url: canonical,
      siteName: "FomaFamily",
      images: ogImages,
    },
    twitter: {
      card: "summary_large_image",
      title: cat.name,
      description,
      images: ogImages?.map((i) => i.url),
    },
  };
}

export default async function CategoryPage({ params, searchParams }: Props) {
  const [{ slug }, sp] = await Promise.all([params, searchParams]);
  const all = await getAllCategories();
  const cat = all.find((c) => c.slug === slug);
  if (!cat) notFound();

  const ids = subtreeIds(all, cat.id);

  const sort: ProductSort = (PRODUCT_SORTS as readonly string[]).includes(sp.sort ?? "")
    ? (sp.sort as ProductSort)
    : "newest";
  const minPrice = parsePrice(sp.min);
  const maxPrice = parsePrice(sp.max);
  const filter: ProductFilter = { sort, minPrice, maxPrice };
  const hasPriceFilter = minPrice !== undefined || maxPrice !== undefined;

  const [total, filteredTotal] = await Promise.all([
    countActiveProducts(ids),
    hasPriceFilter ? countActiveProductsFiltered(ids, filter) : undefined,
  ]);
  const shownTotal = filteredTotal ?? total;

  const page = Math.max(1, parseInt(sp.page ?? "1", 10) || 1);
  const totalPages = Math.max(1, Math.ceil(shownTotal / PAGE_SIZE));
  const products = await getProductsFiltered(ids, filter, PAGE_SIZE, (page - 1) * PAGE_SIZE);

  const parent = cat.parentId ? all.find((c) => c.id === cat.parentId) : null;
  const children = all.filter((c) => c.parentId === cat.id);
  const childrenWithProducts = (
    await Promise.all(
      children.map(async (child) => {
        const n = await countActiveProducts(subtreeIds(all, child.id));
        return n > 0 ? { child, n } : null;
      }),
    )
  ).filter((x): x is NonNullable<typeof x> => x !== null);

  return (
    <div className="mx-auto max-w-6xl px-5 py-8">
      <nav className="mb-2 font-mono text-xs text-ink/50" aria-label="Breadcrumb">
        <ol className="flex flex-wrap items-center gap-1">
          <li>
            <Link href="/" className="hover:text-pine">Home</Link>
          </li>
          {parent && (
            <li>
              <span aria-hidden className="mx-1">/</span>
              <Link href={`/kategori/${parent.slug}`} className="hover:text-pine">
                {parent.name}
              </Link>
            </li>
          )}
          <li aria-current="page">
            <span aria-hidden className="mx-1">/</span>
            <span className="text-ink/80">{cat.name}</span>
          </li>
        </ol>
      </nav>

      <div className="flex flex-wrap items-baseline justify-between gap-2 border-b border-line pb-4">
        <h1 className="font-display text-3xl text-ink">{cat.name}</h1>
        <p className="font-mono text-xs text-ink/50">
          {hasPriceFilter ? (
            <>
              {shownTotal} of {total} {total === 1 ? "item" : "items"}
            </>
          ) : (
            <>
              {total} {total === 1 ? "item" : "items"}
            </>
          )}
        </p>
      </div>

      {childrenWithProducts.length > 0 && (
        <div className="flex flex-wrap gap-2 py-4">
          {childrenWithProducts.map(({ child, n }) => (
            <Link
              key={child.id}
              href={`/kategori/${child.slug}`}
              className="rounded-full border border-line bg-white px-3 py-1 text-sm text-ink/80 transition-colors hover:border-pine hover:text-pine"
            >
              {child.name} <span className="font-mono text-xs text-ink/40">{n}</span>
            </Link>
          ))}
        </div>
      )}

      {total > 0 && (
        <div className="flex items-center justify-between gap-3 border-b border-line py-3">
          <CatalogFilters slug={slug} sort={sort} minPrice={minPrice} maxPrice={maxPrice} />
        </div>
      )}

      {products.length === 0 ? (
        hasPriceFilter || sort !== "newest" ? (
          <p className="py-16 text-center text-ink/50">
            No items match these filters —{" "}
            <Link href={`/kategori/${slug}`} className="text-pine underline">
              clear filters
            </Link>{" "}
            to see everything in {cat.name}.
          </p>
        ) : (
          <p className="py-16 text-center text-ink/50">
            Nothing here yet — browse a subcategory above or head back to the{" "}
            <Link href="/" className="text-pine underline">home page</Link>.
          </p>
        )
      ) : (
        <div className="grid grid-cols-2 gap-4 py-6 sm:grid-cols-3 lg:grid-cols-4">
          {products.map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      )}

      {totalPages > 1 && (
        <nav className="flex items-center justify-center gap-4 py-6 font-mono text-sm" aria-label="Pagination">
          {page > 1 ? (
            <Link href={pageHref(slug, page - 1, filter)} className="text-pine hover:underline">
              ← Previous
            </Link>
          ) : (
            <span className="text-ink/30">← Previous</span>
          )}
          <span className="text-ink/50">
            {page} / {totalPages}
          </span>
          {page < totalPages ? (
            <Link href={pageHref(slug, page + 1, filter)} className="text-pine hover:underline">
              Next →
            </Link>
          ) : (
            <span className="text-ink/30">Next →</span>
          )}
        </nav>
      )}
    </div>
  );
}
