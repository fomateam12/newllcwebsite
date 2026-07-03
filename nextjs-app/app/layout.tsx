import type { Metadata } from "next";
import { Fraunces } from "next/font/google";
import localFont from "next/font/local";
import Link from "next/link";
import { Analytics } from "@/components/analytics/analytics";
import { ConsentBanner } from "@/components/analytics/consent-banner";
import { CartButton } from "@/components/cart/cart-button";
import { CartProvider } from "@/components/cart/cart-context";
import "./globals.css";

const geistSans = localFont({
  src: "./fonts/GeistVF.woff",
  variable: "--font-geist-sans",
  weight: "100 900",
});
const geistMono = localFont({
  src: "./fonts/GeistMonoVF.woff",
  variable: "--font-geist-mono",
  weight: "100 900",
});
const fraunces = Fraunces({
  subsets: ["latin"],
  variable: "--font-fraunces",
  weight: ["400", "500", "600"],
});

export const metadata: Metadata = {
  title: {
    default: "FomaFamily — Personalized Gifts, Engraved & Printed",
    template: "%s · FomaFamily",
  },
  description:
    "Made-to-order personalized gifts: engraved tumblers, custom blankets, family matching shirts and more.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body
        className={`${geistSans.variable} ${geistMono.variable} ${fraunces.variable} font-sans antialiased flex min-h-screen flex-col`}
      >
        <CartProvider>
          <header className="border-b border-line">
            <div className="mx-auto flex max-w-6xl items-baseline justify-between gap-4 px-5 py-4">
              <Link href="/" className="font-display text-2xl font-semibold tracking-tight text-pine-deep">
                FomaFamily
              </Link>
              <div className="flex items-baseline gap-4">
                <p className="hidden text-sm text-ink/60 sm:block">
                  Made to order · engraved &amp; printed in our workshop
                </p>
                <CartButton />
              </div>
            </div>
          </header>
          <main className="flex-1">{children}</main>
        </CartProvider>
        <footer className="mt-16 border-t border-line">
          <div className="mx-auto flex max-w-6xl flex-wrap items-baseline justify-between gap-2 px-5 py-6 text-sm text-ink/50">
            <p>© {new Date().getFullYear()} Foma Family LLC</p>
            <p className="font-mono text-xs">fomafamilyllc.com</p>
          </div>
        </footer>
        <ConsentBanner />
        <Analytics />
      </body>
    </html>
  );
}
