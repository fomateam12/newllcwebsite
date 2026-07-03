import Link from "next/link";
import { getCategoryNav } from "@/lib/catalog";
import { FREE_SHIPPING_THRESHOLD, WORKSHOP_LOCATION } from "./site-constants";

/**
 * Info pages are built on a sibling branch; these hrefs are the agreed
 * contract (/about /shipping /returns /privacy /terms /contact /faq) and
 * may 404 until that branch merges.
 */
const INFO_LINKS = [
  { href: "/about", label: "About us" },
  { href: "/shipping", label: "Shipping" },
  { href: "/returns", label: "Returns" },
  { href: "/faq", label: "FAQ" },
  { href: "/contact", label: "Contact" },
  { href: "/privacy", label: "Privacy policy" },
  { href: "/terms", label: "Terms of service" },
] as const;

export async function SiteFooter() {
  const nav = await getCategoryNav();

  return (
    <footer className="mt-16 border-t border-line bg-white/40">
      <div className="mx-auto grid max-w-6xl gap-10 px-5 py-10 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <p className="font-display text-xl font-semibold text-pine-deep">FomaFamily</p>
          <p className="mt-3 max-w-xs text-sm leading-relaxed text-ink/60">
            Personalized gifts, engraved and printed to order in our own
            workshop — no middlemen, no stock shelves.
          </p>
          <address className="mt-4 text-sm not-italic leading-relaxed text-ink/60">
            <span className="font-medium text-ink/80">Foma Family LLC</span>
            <br />
            {WORKSHOP_LOCATION}
          </address>
        </div>

        <nav aria-label="Footer categories">
          <h2 className="font-mono text-xs uppercase tracking-[0.15em] text-ink/50">Shop</h2>
          <ul className="mt-3 space-y-2">
            {nav.map((root) => (
              <li key={root.id}>
                <Link
                  href={`/kategori/${root.slug}`}
                  className="text-sm text-ink/75 hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
                >
                  {root.name}
                </Link>
              </li>
            ))}
          </ul>
        </nav>

        <nav aria-label="Footer information">
          <h2 className="font-mono text-xs uppercase tracking-[0.15em] text-ink/50">Help &amp; info</h2>
          <ul className="mt-3 space-y-2">
            {INFO_LINKS.map((link) => (
              <li key={link.href}>
                <Link
                  href={link.href}
                  className="text-sm text-ink/75 hover:text-pine focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
                >
                  {link.label}
                </Link>
              </li>
            ))}
          </ul>
        </nav>

        <div>
          <h2 className="font-mono text-xs uppercase tracking-[0.15em] text-ink/50">Our promise</h2>
          <ul className="mt-3 space-y-2 text-sm text-ink/75">
            <li>Made in our Georgia workshop</li>
            <li>Same-day production on most items</li>
            <li>Free U.S. shipping over ${FREE_SHIPPING_THRESHOLD}</li>
          </ul>
        </div>
      </div>

      <div className="border-t border-line">
        <div className="mx-auto flex max-w-6xl flex-wrap items-baseline justify-between gap-2 px-5 py-5 text-sm text-ink/50">
          <p>© {new Date().getFullYear()} Foma Family LLC · {WORKSHOP_LOCATION}</p>
          <p className="font-mono text-xs">fomafamilyllc.com</p>
        </div>
      </div>
    </footer>
  );
}
