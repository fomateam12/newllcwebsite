import { and, asc, count, desc, eq, inArray } from "drizzle-orm";
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

export function formatPrice(price: number): string {
  return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(price);
}
