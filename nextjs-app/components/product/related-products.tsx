import { ProductCard } from "@/components/product-card";
import { getProducts, subtreeIds, type Category } from "@/lib/catalog";

/**
 * "You may also like": up to 4 active products from the same category
 * subtree, excluding the current product. Server component — reuses the
 * existing ProductCard.
 */
export async function RelatedProducts({
  categories,
  categoryId,
  currentProductId,
}: {
  categories: Category[];
  categoryId: number | null;
  currentProductId: number;
}) {
  if (categoryId === null) return null;
  // Fetch one extra so the current product can be excluded and 4 remain.
  const pool = await getProducts(subtreeIds(categories, categoryId), 5, 0);
  const related = pool.filter((p) => p.id !== currentProductId).slice(0, 4);
  if (related.length === 0) return null;

  return (
    <section aria-labelledby="related-heading" className="mt-10 border-t border-line pt-8">
      <h2 id="related-heading" className="mb-4 font-display text-lg text-ink">
        You may also like
      </h2>
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {related.map((p) => (
          <ProductCard key={p.id} product={p} />
        ))}
      </div>
    </section>
  );
}
