import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ProductCard } from "@/components/product-card";
import { countActiveProducts, getAllCategories, getCategoryThumb, getProducts, subtreeIds } from "@/lib/catalog";
import { getImageUrl } from "@/lib/r2";
import { absoluteUrl } from "@/lib/site";

export const dynamic = "force-dynamic";

const PAGE_SIZE = 24;

type Props = {
  params: Promise<{ slug: string }>;
  searchParams: Promise<{ page?: string }>;
};

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
  const total = await countActiveProducts(ids);

  const page = Math.max(1, parseInt(sp.page ?? "1", 10) || 1);
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  const products = await getProducts(ids, PAGE_SIZE, (page - 1) * PAGE_SIZE);

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
        <Link href="/" className="hover:text-pine">Home</Link>
        {parent && (
          <>
            {" / "}
            <Link href={`/kategori/${parent.slug}`} className="hover:text-pine">
              {parent.name}
            </Link>
          </>
        )}
      </nav>

      <div className="flex flex-wrap items-baseline justify-between gap-2 border-b border-line pb-4">
        <h1 className="font-display text-3xl text-ink">{cat.name}</h1>
        <p className="font-mono text-xs text-ink/50">
          {total} {total === 1 ? "item" : "items"}
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

      {products.length === 0 ? (
        <p className="py-16 text-center text-ink/50">
          Nothing here yet — browse a subcategory above or head back to the{" "}
          <Link href="/" className="text-pine underline">home page</Link>.
        </p>
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
            <Link href={`/kategori/${slug}?page=${page - 1}`} className="text-pine hover:underline">
              ← Previous
            </Link>
          ) : (
            <span className="text-ink/30">← Previous</span>
          )}
          <span className="text-ink/50">
            {page} / {totalPages}
          </span>
          {page < totalPages ? (
            <Link href={`/kategori/${slug}?page=${page + 1}`} className="text-pine hover:underline">
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
