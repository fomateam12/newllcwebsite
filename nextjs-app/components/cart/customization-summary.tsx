import {
  summarizeCustomization,
  type CustomizationLine,
} from "@/lib/cart-customization";

/**
 * Readable personalization summary for a cart line — never raw JSON.
 * Renders nothing when there is no parseable customization.
 */
export function CustomizationSummary({
  data,
  className = "",
}: {
  data: unknown;
  className?: string;
}) {
  const lines: CustomizationLine[] = summarizeCustomization(data);
  if (lines.length === 0) return null;
  return (
    <ul className={`space-y-0.5 text-xs text-ink/60 ${className}`}>
      {lines.map((line, i) => (
        <li key={i} className="flex items-baseline gap-1.5">
          <span className="font-mono uppercase tracking-wide text-ink/40">
            {line.label}
          </span>
          <span className="min-w-0 truncate">{line.detail}</span>
          {line.swatch && (
            <span
              aria-hidden
              className="inline-block h-2.5 w-2.5 flex-none self-center rounded-full border border-line"
              style={{ backgroundColor: line.swatch }}
            />
          )}
        </li>
      ))}
    </ul>
  );
}
