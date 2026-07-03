import type { Metadata } from "next";
import { Fraunces } from "next/font/google";
import localFont from "next/font/local";
import { CartProvider } from "@/components/cart/cart-context";
import { SiteFooter } from "@/components/layout/SiteFooter";
import { SiteHeader } from "@/components/layout/SiteHeader";
import "./globals.css";

// Header/footer read the live category tree from D1, and Cloudflare
// bindings only exist inside a request — so no route below can be
// prerendered at build time.
export const dynamic = "force-dynamic";

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
          <SiteHeader />
          <main className="flex-1">{children}</main>
        </CartProvider>
        <SiteFooter />
      </body>
    </html>
  );
}
