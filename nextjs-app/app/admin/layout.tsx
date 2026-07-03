import type { Metadata } from "next";
import { cookies } from "next/headers";
import Link from "next/link";
import { SESSION_COOKIE } from "@/lib/security";
import { logout } from "./login/actions";

export const metadata: Metadata = {
  title: "Admin",
  robots: { index: false, follow: false },
};

export default async function AdminLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  // Presence check only (middleware already verified the signature) —
  // used to hide the nav/logout chrome on the login page.
  const hasSession = (await cookies()).has(SESSION_COOKIE);

  return (
    <div className="mx-auto max-w-6xl px-5 py-8">
      <div className="mb-6 flex flex-wrap items-baseline justify-between gap-3 border-b border-line pb-4">
        <h1 className="font-display text-xl font-semibold text-pine-deep">
          Workshop admin
        </h1>
        {hasSession ? (
          <nav className="flex items-baseline gap-4 font-mono text-xs" aria-label="Admin">
            <Link href="/admin" className="text-ink/60 hover:text-pine">
              Dashboard
            </Link>
            <Link href="/admin/orders" className="text-ink/60 hover:text-pine">
              Orders
            </Link>
            <form action={logout}>
              <button
                type="submit"
                className="rounded-sm border border-line px-2 py-1 text-ink/60 transition-colors hover:border-pine hover:text-pine"
              >
                Sign out
              </button>
            </form>
          </nav>
        ) : null}
      </div>
      {children}
    </div>
  );
}
