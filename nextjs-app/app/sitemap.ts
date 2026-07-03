import type { MetadataRoute } from "next";
import { asc, eq } from "drizzle-orm";
import { getDb, schema } from "@/lib/db";
import { getSiteUrl } from "@/lib/site";

// Rendered at request time on Workers — D1 bindings only exist inside a
// request context, so the DB queries must not run at build time.
export const dynamic = "force-dynamic";

/** D1 stores datetime('now') as "YYYY-MM-DD HH:MM:SS" (UTC). */
function toDate(d1Datetime: string | null): Date | undefined {
  if (!d1Datetime) return undefined;
  const date = new Date(`${d1Datetime.replace(" ", "T")}Z`);
  return Number.isNaN(date.getTime()) ? undefined : date;
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const base = getSiteUrl();
  const db = getDb();

  const categories = await db
    .select({ slug: schema.categories.slug })
    .from(schema.categories)
    .orderBy(asc(schema.categories.slug));

  const products = await db
    .select({ slug: schema.products.slug, updatedAt: schema.products.updatedAt })
    .from(schema.products)
    .where(eq(schema.products.status, "active"))
    .orderBy(asc(schema.products.slug));

  return [
    {
      url: `${base}/`,
      changeFrequency: "daily",
      priority: 1,
    },
    ...categories.map((c) => ({
      url: `${base}/kategori/${c.slug}`,
      changeFrequency: "weekly" as const,
      priority: 0.7,
    })),
    ...products.map((p) => ({
      url: `${base}/urun/${p.slug}`,
      lastModified: toDate(p.updatedAt),
      changeFrequency: "weekly" as const,
      priority: 0.6,
    })),
  ];
}
