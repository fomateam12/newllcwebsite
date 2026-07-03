/** Consistent kicker + title + intro block for content pages. */
export function PageHeader({
  kicker,
  title,
  intro,
}: {
  kicker: string;
  title: string;
  intro?: string;
}) {
  return (
    <header className="border-b border-line pb-8">
      <p className="mb-3 font-mono text-xs uppercase tracking-[0.2em] text-amber">
        {kicker}
      </p>
      <h1 className="font-display text-3xl font-medium leading-tight text-ink sm:text-4xl">
        {title}
      </h1>
      {intro && <p className="mt-4 max-w-xl text-ink/60">{intro}</p>}
    </header>
  );
}

/** Section heading used across the content pages. */
export function SectionHeading({ children }: { children: React.ReactNode }) {
  return (
    <h2 className="mb-3 mt-10 font-display text-xl text-ink">{children}</h2>
  );
}
