import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { Tag } from "@/components/price-tag";
import { BuyBox } from "@/components/product/buy-box";
import { CustomizePanel } from "@/components/product/customize-panel";
import { Gallery, type GalleryImage } from "@/components/product/gallery";
import { ProductPurchaseProvider } from "@/components/product/purchase-provider";
import { RelatedProducts } from "@/components/product/related-products";
import { Reviews } from "@/components/product/reviews";
import { TrustBlock } from "@/components/product/trust-block";
import { formatPrice, getAllCategories, getProductBySlug } from "@/lib/catalog";
import { getDefaultCustomizationOptions } from "@/lib/customization-defaults";
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

export default async function ProductPage({ params }: Props) {
  const { slug } = await params;
  const [data, categories] = await Promise.all([getProductBySlug(slug), getAllCategories()]);
  if (!data || data.product.status !== "active") notFound();
  const { product, images, options, category } = data;

  const safeDescription = product.description
    ? descriptionToSafeHtml(product.description)
    : "";

  // Resolve image URLs server-side so client components stay free of
  // Cloudflare-context imports.
  const galleryImages: GalleryImage[] = images.map((img) => ({
    id: img.id,
    thumb: getImageUrl(img.r2Key, 200),
    main: getImageUrl(img.r2Key, 800),
    zoom: getImageUrl(img.r2Key, 1200),
    alt: img.altText ?? product.name,
  }));
  const hero = images[0];

  // Real customization_options rows win; the table is empty today, so
  // category-derived defaults (lib/customization-defaults.ts) fill in.
  const customizationOptions =
    options.length > 0 ? options : getDefaultCustomizationOptions(categories, product.categoryId);
  // The live preview needs a product photo to draw on.
  const hasPersonalization = customizationOptions.length > 0 && Boolean(hero);

  const plainDescription = product.description
    ? descriptionToPlainText(product.description, 5000)
    : `${product.name} — a made-to-order personalized gift from FomaFamily.`;
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: product.name,
    image: images.map((img) => absoluteUrl(getImageUrl(img.r2Key, 800))),
    description: plainDescription,
    ...(product.sku ? { sku: product.sku } : {}),
    offers: {
      "@type": "Offer",
      url: absoluteUrl(`/urun/${product.slug}`),
      price: product.basePrice.toFixed(2),
      priceCurrency: "USD",
      availability: "https://schema.org/InStock",
    },
  };

  return (
    <div className="mx-auto max-w-6xl px-5 py-8">
      <script
        type="application/ld+json"
        // JSON.stringify output with `<` escaped — safe to inline.
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd).replace(/</g, "\\u003c") }}
      />

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

      <ProductPurchaseProvider>
        <div className="grid gap-8 lg:grid-cols-2">
          {/* Gallery */}
          <Gallery images={galleryImages} productName={product.name} />

          {/* Info + buy box */}
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

            <BuyBox
              productId={product.id}
              slug={product.slug}
              name={product.name}
              basePrice={product.basePrice}
              image={hero ? getImageUrl(hero.r2Key, 200) : null}
              hasPersonalization={hasPersonalization}
            />

            <TrustBlock />
          </div>
        </div>

        {/* Personalization — live preview feeds the buy box via context */}
        {hasPersonalization && hero && (
          <section
            id="personalize"
            aria-labelledby="personalize-heading"
            className="mt-10 scroll-mt-24 border-t border-line pt-8"
          >
            <div className="mb-4 flex flex-wrap items-baseline gap-3">
              <h2 id="personalize-heading" className="font-display text-lg text-ink">
                Make it yours
              </h2>
              <Tag tone="pine">Free personalization</Tag>
            </div>
            <CustomizePanel
              productImage={getImageUrl(hero.r2Key, 800)}
              productImageAlt={hero.altText ?? product.name}
              options={customizationOptions}
            />
            <p className="mt-3 text-xs text-ink/50">
              Your personalization is saved with the item when you add it to the cart.
            </p>
          </section>
        )}
      </ProductPurchaseProvider>

      {safeDescription && (
        <section className="mt-10 border-t border-line pt-8">
          <h2 className="mb-3 font-display text-lg text-ink">About this item</h2>
          <div
            className="prose-legacy max-w-3xl text-sm text-ink/75"
            // Sanitized in lib/html.ts (entity-decode → sanitize-html allowlist)
            dangerouslySetInnerHTML={{ __html: safeDescription }}
          />
        </section>
      )}

      <Reviews productName={product.name} />

      <RelatedProducts
        categories={categories}
        categoryId={product.categoryId}
        currentProductId={product.id}
      />
    </div>
  );
}
