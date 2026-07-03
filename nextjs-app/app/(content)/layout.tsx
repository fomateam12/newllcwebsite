/**
 * Shared shell for the content pages (/about, /shipping, /returns,
 * /privacy, /terms, /contact, /faq): a narrow reading column on paper,
 * matching the storefront's warm-artisan look.
 */
export default function ContentLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <div className="mx-auto max-w-3xl px-5 py-10 sm:py-14">{children}</div>
  );
}
