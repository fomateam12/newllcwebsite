import Link from "next/link";
import { Tag } from "@/components/price-tag";

const CATEGORIES = [
  { slug: "gifts", name: "Gifts" },
  { slug: "clothing-and-fashion", name: "Clothing & Fashion" },
  { slug: "home-living", name: "Home & Living" },
  { slug: "christmas", name: "Christmas" },
];

export default function NotFound() {
  return (
    <div className="mx-auto max-w-3xl px-5 py-16 sm:py-24">
      <Tag>404 — not found</Tag>
      <h1 className="mt-4 max-w-xl font-display text-3xl font-medium leading-tight text-ink sm:text-4xl">
        This page seems to have left the workshop without a tracking number.
      </h1>
      <p className="mt-4 max-w-xl leading-relaxed text-ink/60">
        The link may be old, or the item may have been retired from the
        catalog. Try heading back to the{" "}
        <Link href="/" className="text-pine underline">
          homepage
        </Link>{" "}
        and browsing from there — or start with one of our favorite corners
        of the shop below.
      </p>

      <h2 className="mb-4 mt-10 font-mono text-xs uppercase tracking-[0.2em] text-amber">
        Browse the shop
      </h2>
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {CATEGORIES.map((cat) => (
          <Link
            key={cat.slug}
            href={`/kategori/${cat.slug}`}
            className="rounded-md border border-line bg-white p-4 transition-shadow hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
          >
            <span className="font-display text-lg leading-tight text-pine-deep">
              {cat.name}
            </span>
          </Link>
        ))}
      </div>

      <p className="mt-10 text-sm text-ink/50">
        Looking for something specific?{" "}
        <Link href="/contact" className="text-pine underline">
          Ask us
        </Link>{" "}
        — if we make it, we&apos;ll find it for you.
      </p>
    </div>
  );
}
