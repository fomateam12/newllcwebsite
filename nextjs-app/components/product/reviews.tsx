/**
 * Reviews skeleton — static empty state.
 *
 * The reviews table does NOT exist in any database yet; its DDL lives in
 * migrations/0004_reviews.sql (unapplied by design). This component
 * intentionally performs no queries.
 *
 * TODO(wiring):
 *  1. Apply migrations/0004_reviews.sql (local first, then remote).
 *  2. Add a `reviews` table definition to lib/schema.ts mirroring the DDL.
 *  3. Fetch approved reviews in the page (WHERE product_id = ? AND
 *     status = 'approved' ORDER BY created_at DESC) and pass them in.
 *  4. Render stars/aggregate + extend the product JSON-LD with
 *     aggregateRating once real data exists (never fake ratings).
 *  5. Add a moderated "Write a review" form (status defaults to 'pending').
 */
export function Reviews({ productName }: { productName: string }) {
  return (
    <section aria-labelledby="reviews-heading" className="mt-10 border-t border-line pt-8">
      <h2 id="reviews-heading" className="mb-4 font-display text-lg text-ink">
        Reviews
      </h2>
      <div className="rounded-md border border-dashed border-line bg-white/60 px-5 py-8 text-center">
        <div aria-hidden className="mb-2 font-mono text-lg tracking-[0.35em] text-ink/25">
          ☆☆☆☆☆
        </div>
        <p className="text-sm text-ink/60">
          No reviews yet — be the first to tell us how your {productName} turned out.
        </p>
      </div>
    </section>
  );
}
