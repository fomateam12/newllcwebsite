import Link from "next/link";
import { formatPrice, type ProductWithThumb } from "@/lib/catalog";
import { getImageUrl } from "@/lib/r2";
import { Tag } from "./price-tag";

export function ProductCard({ product }: { product: ProductWithThumb }) {
  return (
    <Link
      href={`/urun/${product.slug}`}
      className="group flex flex-col overflow-hidden rounded-md border border-line bg-white transition-shadow hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
    >
      <div className="aspect-square overflow-hidden bg-line/30">
        {product.thumb ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={getImageUrl(product.thumb, 400)}
            alt={product.name}
            loading="lazy"
            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center text-sm text-ink/40">
            No photo yet
          </div>
        )}
      </div>
      <div className="flex flex-1 flex-col gap-2 p-3">
        <h3 className="line-clamp-2 text-sm leading-snug text-ink/90">
          {product.name}
        </h3>
        <div className="mt-auto">
          <Tag>{formatPrice(product.basePrice)}</Tag>
        </div>
      </div>
    </Link>
  );
}
