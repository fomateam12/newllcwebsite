import Link from "next/link";
import { CartButton } from "@/components/cart/cart-button";
import { DesktopNav } from "@/components/nav/desktop-nav";
import { MobileMenu } from "@/components/nav/mobile-menu";
import { SearchBox } from "@/components/nav/search-box";
import { getCategoryNav } from "@/lib/catalog";
import { FREE_SHIPPING_THRESHOLD } from "./site-constants";

/**
 * Sticky site header: announcement strip, logo + search + cart row, and a
 * category nav row (mega-menu on desktop, slide-in tree on mobile).
 * Server component — reads the category tree from D1 per request.
 */
export async function SiteHeader() {
  const nav = await getCategoryNav();

  return (
    <header className="sticky top-0 z-40 border-b border-line bg-paper/95 backdrop-blur supports-[backdrop-filter]:bg-paper/90">
      <p className="border-b border-line bg-pine-deep px-5 py-1.5 text-center font-mono text-xs text-paper">
        Free U.S. shipping over ${FREE_SHIPPING_THRESHOLD} · Made to order in our Georgia workshop
      </p>

      <div className="mx-auto flex max-w-6xl items-center gap-3 px-5 py-3">
        <MobileMenu nav={nav} />
        <Link
          href="/"
          className="shrink-0 font-display text-2xl font-semibold tracking-tight text-pine-deep focus-visible:outline focus-visible:outline-2 focus-visible:outline-pine"
        >
          FomaFamily
        </Link>
        <SearchBox className="ml-auto w-full max-w-xs sm:max-w-sm md:mx-auto md:max-w-md" />
        <div className="shrink-0 md:ml-auto">
          <CartButton />
        </div>
      </div>

      <div className="mx-auto hidden max-w-6xl px-5 md:block">
        <DesktopNav nav={nav} />
      </div>
    </header>
  );
}
