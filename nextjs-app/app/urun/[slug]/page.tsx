import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { AddToCart } from "@/components/add-to-cart";
import { Tag } from "@/components/price-tag";
import { formatPrice, getProductBySlug } from "@/lib/catalog";
import { descriptionToSafeHtml } from "@/lib/html";
import { getImageUrl } from "@/lib/r2";
import { descriptionToPlainText } from "@/lib/seo";
import { absoluteUrl } from "@/lib/site";

export const dynamic = "force-dynamic";

type Props = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: Props): Promise<Metadata> {
  const { slug } = await params;
  const data = await getProductBySlug(slug);
  // Missing or draft products 404 in the page component — keep metadata minimal.
  if (!data || data.product.status !== "active") {
    return { title: data ? data.product.name : "Product" };
  }
  const { product, images } = data;
  const description = product.description
    ? descriptionToPlainText(product.description)
    : `${product.name} — a made-to-order personalized gift from FomaFamily, engraved and printed in our workshop.`;
  const canonical = absoluteUrl(`/urun/${product.slug}`);
  // getProductBySlug orders images primary-first.
  const primary = images[0];
  const ogImages = primary
    ? [{ url: absoluteUrl(getImageUrl(primary.r2Key, 800)), alt: primary.altText ?? product.name }]
    : undefined;
  return {
    title: product.name,
    description,
    alternates: { canonical },
    openGraph: {
      title: product.name,
      description,
      type: "website",
      url: canonical,
      siteName: "FomaFamily",
      images: ogImages,
    },
    twitter: {
      card: "summary_large_image",
      title: product.name,
      description,
      images: ogImages?.map((i) => i.url),
    },
  };
}

const OPTION_TYPE_LABEL: Record<string, string> = {
  text: "Your text",
  color: "Color choice",
  font: "Font choice",
  upload: "Photo upload",
};

export default async function ProductPage({ params }: Props) {
  const { slug } = await params;
  const data = await getProductBySlug(slug);
  if (!data || data.product.status !== "active") notFound();
  const { product, images, options, category } = data;

  const safeDescription = product.description
    ? descriptionToSafeHtml(product.description)
    : "";
  const [hero, ...rest] = images;

  return (
    <div className="mx-auto max-w-6xl px-5 py-8">
      <nav className="mb-4 font-mono text-xs text-ink/50" aria-label="Breadcrumb">
        <Link href="/" className="hover:text-pine">Home</Link>
        {category && (
          <>
            {" / "}
            <Link href={`/kategori/${category.slug}`} className="hover:text-pine">
              {category.name}
            </Link>
          </>
        )}
      </nav>

      <div className="grid gap-8 lg:grid-cols-2">
        {/* Gallery */}
        <div>
          <div className="overflow-hidden rounded-md border border-line bg-white">
            {hero ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={getImageUrl(hero.r2Key, 1200)}
                alt={hero.altText ?? product.name}
                className="aspect-square w-full object-cover"
              />
            ) : (
              <div className="flex aspect-square items-center justify-center text-ink/40">
                No photo yet
              </div>
            )}
          </div>
          {rest.length > 0 && (
            <div className="mt-3 grid grid-cols-5 gap-2 sm:grid-cols-6">
              {rest.slice(0, 11).map((img) => (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  key={img.id}
                  src={getImageUrl(img.r2Key, 200)}
                  alt=""
                  loading="lazy"
                  className="aspect-square w-full rounded-sm border border-line object-cover"
                />
              ))}
              {rest.length > 11 && (
                <div className="flex aspect-square items-center justify-center rounded-sm border border-line bg-line/30 font-mono text-xs text-ink/50">
                  +{rest.length - 11}
                </div>
              )}
            </div>
          )}
        </div>

        {/* Info */}
        <div>
          <h1 className="font-display text-2xl leading-snug text-ink sm:text-3xl">
            {product.name}
          </h1>
          <div className="mt-4 flex flex-wrap items-center gap-3">
            <Tag className="text-base">{formatPrice(product.basePrice)}</Tag>
            {product.sku && (
              <span className="font-mono text-xs text-ink/40">SKU {product.sku}</span>
            )}
          </div>

          <AddToCart
            productId={product.id}
            slug={product.slug}
            name={product.name}
            unitPrice={product.basePrice}
            image={hero ? getImageUrl(hero.r2Key, 200) : null}
          />

          {options.length > 0 && (
            <section className="mt-8">
              <h2 className="mb-3 font-display text-lg text-ink">
                Personalization
              </h2>
              <ul className="space-y-2">
                {options.map((opt) => (
                  <li
                    key={opt.id}
                    className="flex items-baseline justify-between rounded-md border border-line bg-white px-3 py-2"
                  >
                    <span className="text-sm text-ink/80">{opt.label}</span>
                    <Tag tone="pine">
                      {OPTION_TYPE_LABEL[opt.optionType] ?? opt.optionType}
                      {opt.required ? " · required" : ""}
                    </Tag>
                  </li>
                ))}
              </ul>
              <p className="mt-2 text-xs text-ink/50">
                You&apos;ll enter your personalization at checkout — live
                preview is on its way.
              </p>
            </section>
          )}

          {safeDescription && (
            <section className="mt-8 border-t border-line pt-6">
              <h2 className="mb-3 font-display text-lg text-ink">About this item</h2>
              <div
                className="prose-legacy text-sm text-ink/75"
                // Sanitized in lib/html.ts (entity-decode → sanitize-html allowlist)
                dangerouslySetInnerHTML={{ __html: safeDescription }}
              />
            </section>
          )}
        </div>
      </div>
    </div>
  );
}
