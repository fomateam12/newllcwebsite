-- 0004_reviews.sql — product reviews skeleton.
--
-- NOT APPLIED ANYWHERE YET (intentionally): the UI ships a static
-- "No reviews yet" empty state that performs no queries. Apply this
-- migration (local first: `npx wrangler d1 execute fomawebsitedatabase
-- --file=migrations/0004_reviews.sql --local`) when wiring real reviews,
-- and add the matching table to lib/schema.ts at the same time.
--
-- Moderation model: rows land as status='pending'; only 'approved' rows
-- are ever rendered publicly.

CREATE TABLE IF NOT EXISTS reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  product_id INTEGER NOT NULL REFERENCES products(id),
  rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title TEXT,
  body TEXT,
  author_name TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  status TEXT NOT NULL DEFAULT 'pending'
);

CREATE INDEX IF NOT EXISTS idx_reviews_product_id ON reviews(product_id);
CREATE INDEX IF NOT EXISTS idx_reviews_status ON reviews(status);
