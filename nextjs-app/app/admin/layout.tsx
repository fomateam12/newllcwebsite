import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Admin",
  robots: { index: false, follow: false },
};

export default function AdminLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <div className="mx-auto max-w-6xl px-5 py-8">
      <div className="mb-6 flex flex-wrap items-baseline justify-between gap-3 border-b border-line pb-4">
        <h1 className="font-display text-xl font-semibold text-pine-deep">
          Workshop admin
        </h1>
        <nav className="flex gap-4 font-mono text-xs" aria-label="Admin">
          <Link href="/admin" className="text-ink/60 hover:text-pine">
            Dashboard
          </Link>
          <Link href="/admin/orders" className="text-ink/60 hover:text-pine">
            Orders
          </Link>
        </nav>
      </div>
      {children}
    </div>
  );
}
