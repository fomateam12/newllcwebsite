import { and, asc, count, desc, eq, gte, inArray, lte, sql, type SQL } from "drizzle-orm";
import { getDb, schema } from "./db";

export type Category = typeof schema.categories.$inferSelect;
export type Product = typeof schema.products.$inferSelect;
export type ProductImage = typeof schema.productImages.$inferSelect;

/** All categories once per request; the table is small (~57 rows). */
export async function getAllCategories(): Promise<Category[]> {
  const db = getDb();
  return db.select().from(schema.categories).orderBy(asc(schema.categories.sortOrder), asc(schema.categories.name));
}

/** ids of `root` plus every descendant category. */
export function subtreeIds(all: Category[], rootId: number): number[] {
  const kids = new Map<number | null, number[]>();
  for (const c of all) {
    const arr = kids.get(c.parentId) ?? [];
    arr.push(c.id);
    kids.set(c.parentId, arr);
  }
  const out: number[] = [];
  const stack = [rootId];
  while (stack.length) {
    const id = stack.pop()!;
    out.push(id);
    for (const k of kids.get(id) ?? []) stack.push(k);
  }
  return out;
}

export async function countActiveProducts(categoryIds: number[]): Promise<number> {
  const db = getDb();
  const [row] = await db
    .select({ n: count() })
    .from(schema.products)
    .where(and(inArray(schema.products.categoryId, categoryIds), eq(schema.products.status, "active")));
  return row?.n ?? 0;
}

export type ProductWithThumb = Product & { thumb: string | null };

/** Active products in the given categories, newest first, with primary image. */
export async function getProducts(
  categoryIds: number[],
  limit: number,
  offset: number,
): Promise<ProductWithThumb[]> {
  const db = getDb();
  const products = await db
    .select()
    .from(schema.products)
    .where(and(inArray(schema.products.categoryId, categoryIds), eq(schema.products.status, "active")))
    .orderBy(desc(schema.products.id))
    .limit(limit)
    .offset(offset);
  if (products.length === 0) return [];
  const imgs = await db
    .select()
    .from(schema.productImages)
    .where(
      and(
        inArray(schema.productImages.productId, products.map((p) => p.id)),
        eq(schema.productImages.isPrimary, 1),
      ),
    );
  const thumbByProduct = new Map(imgs.map((i) => [i.productId, i.r2Key]));
  return products.map((p) => ({ ...p, thumb: thumbByProduct.get(p.id) ?? null }));
}

/** One representative product photo for a category subtree (for tiles). */
export async function getCategoryThumb(categoryIds: number[]): Promise<string | null> {
  const db = getDb();
  const rows = await db
    .select({ key: schema.productImages.r2Key })
    .from(schema.productImages)
    .innerJoin(schema.products, eq(schema.products.id, schema.productImages.productId))
    .where(
      and(
        inArray(schema.products.categoryId, categoryIds),
        eq(schema.products.status, "active"),
        eq(schema.productImages.isPrimary, 1),
      ),
    )
    .orderBy(desc(schema.products.id))
    .limit(1);
  return rows[0]?.key ?? null;
}

export async function getProductBySlug(slug: string) {
  const db = getDb();
  const [product] = await db.select().from(schema.products).where(eq(schema.products.slug, slug)).limit(1);
  if (!product) return null;
  const images = await db
    .select()
    .from(schema.productImages)
    .where(eq(schema.productImages.productId, product.id))
    .orderBy(desc(schema.productImages.isPrimary), asc(schema.productImages.sortOrder));
  const options = await db
    .select()
    .from(schema.customizationOptions)
    .where(eq(schema.customizationOptions.productId, product.id))
    .orderBy(asc(schema.customizationOptions.sortOrder));
  const category = product.categoryId
    ? (await db.select().from(schema.categories).where(eq(schema.categories.id, product.categoryId)).limit(1))[0] ?? null
    : null;
  return { product, images, options, category };
}

/* ------------------------------------------------------------------ */
/* Navigation tree                                                     */
/* ------------------------------------------------------------------ */

/**
 * Categories hidden from all customer-facing navigation and grids.
 * TODO(merchandising): "Newww" is an operator TEST category created while
 * trialing the admin — remove it from the DB (or rename/curate it) and
 * drop this deny-list once real merchandising categories are in place.
 */
export const HIDDEN_CATEGORY_SLUGS: ReadonlySet<string> = new Set(["newww"]);

export type CategoryNavNode = {
  id: number;
  name: string;
  slug: string;
  /** Active products in this category's whole subtree. */
  count: number;
  children: CategoryNavNode[];
};

/** Active-product count per direct categoryId (single GROUP BY query). */
export async function getActiveProductCountsByCategory(): Promise<Map<number, number>> {
  const db = getDb();
  const rows = await db
    .select({ categoryId: schema.products.categoryId, n: count() })
    .from(schema.products)
    .where(eq(schema.products.status, "active"))
    .groupBy(schema.products.categoryId);
  const map = new Map<number, number>();
  for (const r of rows) if (r.categoryId !== null) map.set(r.categoryId, r.n);
  return map;
}

/**
 * Customer-facing category tree: roots and children with at least one
 * active product somewhere in their subtree. Empty categories and the
 * HIDDEN_CATEGORY_SLUGS deny-list are pruned. Two queries total.
 */
export async function getCategoryNav(): Promise<CategoryNavNode[]> {
  const [all, direct] = await Promise.all([getAllCategories(), getActiveProductCountsByCategory()]);
  const kids = new Map<number | null, Category[]>();
  for (const c of all) {
    const arr = kids.get(c.parentId) ?? [];
    arr.push(c);
    kids.set(c.parentId, arr);
  }
  const build = (cat: Category): CategoryNavNode | null => {
    if (HIDDEN_CATEGORY_SLUGS.has(cat.slug)) return null;
    const children = (kids.get(cat.id) ?? [])
      .map(build)
      .filter((n): n is CategoryNavNode => n !== null);
    const count = (direct.get(cat.id) ?? 0) + children.reduce((s, c) => s + c.count, 0);
    if (count === 0) return null;
    return { id: cat.id, name: cat.name, slug: cat.slug, count, children };
  };
  return (kids.get(null) ?? [])
    .map(build)
    .filter((n): n is CategoryNavNode => n !== null)
    .sort((a, b) => b.count - a.count);
}

/* ------------------------------------------------------------------ */
/* Bestsellers                                                         */
/* ------------------------------------------------------------------ */

/**
 * "Bestsellers" for the homepage. We have no sales analytics yet, so we
 * proxy popularity with gallery depth (products the operator photographed
 * most thoroughly are the ones they actually sell).
 * TODO(analytics): replace with real sales data (order_items aggregation)
 * once enough orders have flowed through Stripe checkout.
 */
export async function getBestsellers(limit: number): Promise<ProductWithThumb[]> {
  const db = getDb();
  const rows = await db
    .select({ product: schema.products, imageCount: count(schema.productImages.id) })
    .from(schema.products)
    .innerJoin(schema.productImages, eq(schema.productImages.productId, schema.products.id))
    .where(eq(schema.products.status, "active"))
    .groupBy(schema.products.id)
    .orderBy(desc(count(schema.productImages.id)), desc(schema.products.id))
    .limit(limit);
  if (rows.length === 0) return [];
  const imgs = await db
    .select()
    .from(schema.productImages)
    .where(
      and(
        inArray(schema.productImages.productId, rows.map((r) => r.product.id)),
        eq(schema.productImages.isPrimary, 1),
      ),
    );
  const thumbByProduct = new Map(imgs.map((i) => [i.productId, i.r2Key]));
  return rows.map((r) => ({ ...r.product, thumb: thumbByProduct.get(r.product.id) ?? null }));
}

/* ------------------------------------------------------------------ */
/* Filtered listing (category pages)                                   */
/* ------------------------------------------------------------------ */

export const PRODUCT_SORTS = ["newest", "price-asc", "price-desc", "name"] as const;
export type ProductSort = (typeof PRODUCT_SORTS)[number];

export type ProductFilter = {
  sort?: ProductSort;
  /** Inclusive bounds, in dollars (basePrice is stored in dollars). */
  minPrice?: number;
  maxPrice?: number;
};

function filterConditions(categoryIds: number[], filter: ProductFilter): SQL[] {
  const conds: SQL[] = [
    inArray(schema.products.categoryId, categoryIds),
    eq(schema.products.status, "active"),
  ];
  if (filter.minPrice !== undefined) conds.push(gte(schema.products.basePrice, filter.minPrice));
  if (filter.maxPrice !== undefined) conds.push(lte(schema.products.basePrice, filter.maxPrice));
  return conds;
}

export async function countActiveProductsFiltered(
  categoryIds: number[],
  filter: ProductFilter,
): Promise<number> {
  const db = getDb();
  const [row] = await db
    .select({ n: count() })
    .from(schema.products)
    .where(and(...filterConditions(categoryIds, filter)));
  return row?.n ?? 0;
}

/** getProducts with sort + price-range support; same thumb enrichment. */
export async function getProductsFiltered(
  categoryIds: number[],
  filter: ProductFilter,
  limit: number,
  offset: number,
): Promise<ProductWithThumb[]> {
  const db = getDb();
  const order =
    filter.sort === "price-asc"
      ? [asc(schema.products.basePrice), desc(schema.products.id)]
      : filter.sort === "price-desc"
        ? [desc(schema.products.basePrice), desc(schema.products.id)]
        : filter.sort === "name"
          ? [asc(schema.products.name)]
          : [desc(schema.products.id)]; // "newest" (default)
  const products = await db
    .select()
    .from(schema.products)
    .where(and(...filterConditions(categoryIds, filter)))
    .orderBy(...order)
    .limit(limit)
    .offset(offset);
  if (products.length === 0) return [];
  const imgs = await db
    .select()
    .from(schema.productImages)
    .where(
      and(
        inArray(schema.productImages.productId, products.map((p) => p.id)),
        eq(schema.productImages.isPrimary, 1),
      ),
    );
  const thumbByProduct = new Map(imgs.map((i) => [i.productId, i.r2Key]));
  return products.map((p) => ({ ...p, thumb: thumbByProduct.get(p.id) ?? null }));
}

/* ------------------------------------------------------------------ */
/* Search                                                              */
/* ------------------------------------------------------------------ */

export type SearchHit = { id: number; name: string; slug: string; thumb: string | null };

/**
 * Case-insensitive substring search over active product names.
 * LIKE wildcards in user input are escaped so they match literally.
 */
export async function searchActiveProducts(q: string, limit: number): Promise<SearchHit[]> {
  const db = getDb();
  const escaped = q.replace(/[\\%_]/g, (m) => `\\${m}`);
  const pattern = `%${escaped}%`;
  const products = await db
    .select({ id: schema.products.id, name: schema.products.name, slug: schema.products.slug })
    .from(schema.products)
    .where(
      and(
        eq(schema.products.status, "active"),
        sql`${schema.products.name} LIKE ${pattern} ESCAPE '\\'`,
      ),
    )
    .orderBy(asc(schema.products.name))
    .limit(limit);
  if (products.length === 0) return [];
  const imgs = await db
    .select({ productId: schema.productImages.productId, r2Key: schema.productImages.r2Key })
    .from(schema.productImages)
    .where(
      and(
        inArray(schema.productImages.productId, products.map((p) => p.id)),
        eq(schema.productImages.isPrimary, 1),
      ),
    );
  const thumbByProduct = new Map(imgs.map((i) => [i.productId, i.r2Key]));
  return products.map((p) => ({ ...p, thumb: thumbByProduct.get(p.id) ?? null }));
}

// Moved to lib/format.ts (client-safe, no Cloudflare context import);
// re-exported here so existing server imports keep working.
export { formatPrice } from "./format";
