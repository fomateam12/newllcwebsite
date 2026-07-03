/**
 * The site's signature element: prices and small badges are set in a
 * "gift tag" — dashed (stitched) border with a punched hole, echoing
 * the hang-tags on engraved keepsakes.
 */
export function Tag({
  children,
  tone = "amber",
  className = "",
}: {
  children: React.ReactNode;
  tone?: "amber" | "pine";
  className?: string;
}) {
  const toneClasses =
    tone === "amber"
      ? "border-amber/60 text-amber"
      : "border-pine/50 text-pine";
  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-sm border border-dashed bg-white/60 px-2 py-0.5 font-mono text-sm ${toneClasses} ${className}`}
    >
      <span aria-hidden className="h-1.5 w-1.5 rounded-full border border-current" />
      {children}
    </span>
  );
}
