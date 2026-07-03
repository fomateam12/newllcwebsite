import Link from "next/link";
import { countActiveProducts, getAllCategories, getCategoryThumb, subtreeIds } from "@/lib/catalog";
import { getImageUrl } from "@/lib/r2";

export const dynamic = "force-dynamic";

export default async function Home() {
  const all = await getAllCategories();
  const roots = all.filter((c) => c.parentId === null);

  const tiles = (
    await Promise.all(
      roots.map(async (cat) => {
        const ids = subtreeIds(all, cat.id);
        const productCount = await countActiveProducts(ids);
        if (productCount === 0) return null;
        const thumb = await getCategoryThumb(ids);
        return { cat, productCount, thumb };
      }),
    )
  ).filter((t): t is NonNullable<typeof t> => t !== null);
  tiles.sort((a, b) => b.productCount - a.productCount);

  return (
    <div className="mx-auto max-w-6xl px-5">
      <section className="border-b border-line py-12 sm:py-16">
        <p className="mb-3 font-mono text-xs uppercase tracking-[0.2em] text-amber">
          Personalized, made to order
        </p>
        <h1 className="max-w-2xl font-display text-4xl font-medium leading-tight text-ink sm:text-5xl">
          Gifts with your name on them — literally.
        </h1>
        <p className="mt-4 max-w-xl text-ink/60">
          Engraved tumblers, custom blankets, family matching shirts. Every
          piece is printed or laser-engraved for you in our workshop.
        </p>
      </section>

      <section className="py-10">
        <h2 className="mb-6 font-display text-2xl text-ink">Shop by category</h2>
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {tiles.map(({ cat, productCount, thumb }) => (
            <Link
              key={cat.id}
              href={`/kategori/${cat.slug}`}
              className="group overflow-hidden rounded-md border border-line bg-white transition-shadow hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
            >
              <div className="aspect-[4/3] overflow-hidden bg-line/30">
                {thumb ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={getImageUrl(thumb)}
                    alt=""
                    loading="lazy"
                    className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                  />
                ) : null}
              </div>
              <div className="flex items-baseline justify-between gap-2 p-3">
                <h3 className="font-display text-lg leading-tight text-pine-deep">
                  {cat.name}
                </h3>
                <span className="shrink-0 font-mono text-xs text-ink/50">
                  {productCount}
                </span>
              </div>
            </Link>
          ))}
        </div>
      </section>
    </div>
  );
}
