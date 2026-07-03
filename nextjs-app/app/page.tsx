import Link from "next/link";
import { FREE_SHIPPING_THRESHOLD } from "@/components/layout/site-constants";
import { ProductCard } from "@/components/product-card";
import { Tag } from "@/components/price-tag";
import {
  getAllCategories,
  getBestsellers,
  getCategoryNav,
  getCategoryThumb,
  subtreeIds,
} from "@/lib/catalog";
import { getImageUrl } from "@/lib/r2";

export const dynamic = "force-dynamic";

const FEATURED_CATEGORY_LIMIT = 8;
const BESTSELLER_LIMIT = 8;

const TRUST_ITEMS = [
  {
    title: "Made in our Georgia workshop",
    body: "Every piece is engraved or printed by us in Alpharetta, GA — never dropshipped.",
  },
  {
    title: "Same-day production",
    body: "Most orders go from your screen to our laser or press within one working day.",
  },
  {
    title: `Free shipping over $${FREE_SHIPPING_THRESHOLD}`,
    body: "Flat, honest rates below that — no surprises at checkout.",
  },
] as const;

const HOW_IT_WORKS = [
  {
    step: "1",
    title: "Choose",
    body: "Pick a tumbler, blanket, shirt or keepsake from the catalog.",
  },
  {
    step: "2",
    title: "Personalize",
    body: "Add names, dates, photos or your own words — we set it up for you.",
  },
  {
    step: "3",
    title: "We make it",
    body: "Engraved and printed to order in our workshop, then shipped to your door.",
  },
] as const;

export default async function Home() {
  // getCategoryNav already hides empty subtrees and the "Newww" operator
  // test category (see HIDDEN_CATEGORY_SLUGS in lib/catalog.ts).
  // TODO(merchandising): this grid is ordered by raw product count —
  // replace with a curated/seasonal selection once real merchandising
  // categories (occasions, recipients) carry products.
  const [nav, all, bestsellers] = await Promise.all([
    getCategoryNav(),
    getAllCategories(),
    getBestsellers(BESTSELLER_LIMIT),
  ]);
  const featured = nav.slice(0, FEATURED_CATEGORY_LIMIT);
  const tiles = await Promise.all(
    featured.map(async (node) => ({
      node,
      thumb: await getCategoryThumb(subtreeIds(all, node.id)),
    })),
  );
  const heroCategory = nav[0]; // largest real category (currently Clothing and Fashion)

  return (
    <div className="mx-auto max-w-6xl px-5">
      {/* Seasonal campaign hero — static summer theme for now.
          TODO(merchandising): swap copy/CTA per season or promo. */}
      <section className="border-b border-line py-12 sm:py-16">
        <Tag tone="amber" className="mb-4">Summer 2026 collection</Tag>
        <h1 className="max-w-2xl font-display text-4xl font-medium leading-tight text-ink sm:text-5xl">
          Matching shirts for the beach trip. Tumblers for the cooler.
          Names on all of it.
        </h1>
        <p className="mt-4 max-w-xl text-ink/60">
          Personalized summer keepsakes — engraved tumblers, family matching
          shirts and custom blankets, made to order in our Georgia workshop.
        </p>
        <div className="mt-7 flex flex-wrap items-center gap-4">
          {heroCategory && (
            <Link
              href={`/kategori/${heroCategory.slug}`}
              className="rounded-sm bg-pine px-5 py-2.5 font-medium text-paper transition-colors hover:bg-pine-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pine"
            >
              Shop {heroCategory.name}
            </Link>
          )}
          <Link
            href="/kategori/home-living"
            className="text-sm font-medium text-pine underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
          >
            Browse Home &amp; Living →
          </Link>
        </div>
      </section>

      {/* Trust bar */}
      <section aria-label="Why shop with us" className="border-b border-line py-6">
        <ul className="grid gap-5 sm:grid-cols-3">
          {TRUST_ITEMS.map((item) => (
            <li key={item.title} className="flex gap-3">
              <span
                aria-hidden
                className="mt-1 h-2 w-2 shrink-0 rounded-full border border-dashed border-amber"
              />
              <div>
                <p className="text-sm font-medium text-ink">{item.title}</p>
                <p className="mt-0.5 text-sm leading-snug text-ink/55">{item.body}</p>
              </div>
            </li>
          ))}
        </ul>
      </section>

      {/* Shop by category */}
      <section className="py-10">
        <h2 className="mb-6 font-display text-2xl text-ink">Shop by category</h2>
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {tiles.map(({ node, thumb }) => (
            <Link
              key={node.id}
              href={`/kategori/${node.slug}`}
              className="group overflow-hidden rounded-md border border-line bg-white transition-shadow hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
            >
              <div className="aspect-[4/3] overflow-hidden bg-line/30">
                {thumb ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={getImageUrl(thumb, 400)}
                    alt=""
                    loading="lazy"
                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                  />
                ) : null}
              </div>
              <div className="flex items-baseline justify-between gap-2 p-3">
                <h3 className="font-display text-lg leading-tight text-pine-deep">
                  {node.name}
                </h3>
                <span className="shrink-0 font-mono text-xs text-ink/50">
                  {node.count}
                </span>
              </div>
            </Link>
          ))}
        </div>
      </section>

      {/* Bestsellers — popularity proxied by gallery depth for now.
          TODO(analytics): switch to real sales data (see getBestsellers). */}
      {bestsellers.length > 0 && (
        <section className="border-t border-line py-10">
          {/* stacked on mobile / fixed columns on sm+ so web-font width
              changes can't re-wrap this row (CLS) */}
          <div className="mb-6 grid items-baseline gap-1 sm:grid-cols-[auto_1fr] sm:gap-4">
            <h2 className="font-display text-2xl text-ink">Bestsellers</h2>
            <p className="text-sm text-ink/50 sm:text-right">The pieces our workshop makes most</p>
          </div>
          {/* min-h reserves two title lines so font swap can't shift the grid */}
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 [&_h3]:min-h-[2.75em]">
            {bestsellers.map((p) => (
              <ProductCard key={p.id} product={p} />
            ))}
          </div>
        </section>
      )}

      {/* Design it your way — 3-step value prop */}
      <section className="border-t border-line py-12">
        <h2 className="font-display text-2xl text-ink">Design it your way</h2>
        <p className="mt-2 max-w-xl text-ink/60">
          No design skills needed — tell us what it should say and we take it
          from there.
        </p>
        <ol className="mt-8 grid gap-8 sm:grid-cols-3">
          {HOW_IT_WORKS.map((s) => (
            <li key={s.step} className="relative">
              <span
                aria-hidden
                className="inline-flex h-9 w-9 items-center justify-center rounded-sm border border-dashed border-pine/50 bg-white/60 font-mono text-sm text-pine"
              >
                {s.step}
              </span>
              <h3 className="mt-3 font-display text-lg text-pine-deep">{s.title}</h3>
              <p className="mt-1 text-sm leading-relaxed text-ink/60">{s.body}</p>
            </li>
          ))}
        </ol>
      </section>
    </div>
  );
}
