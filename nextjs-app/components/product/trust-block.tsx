import Link from "next/link";

/**
 * Static trust & expectation block: production time, returns pointer,
 * secure-payment marks. Server-rendered, zero JS, inline SVG only.
 */
export function TrustBlock() {
  return (
    <div className="mt-6 space-y-2.5 rounded-md border border-line bg-white/60 px-4 py-3.5 text-sm text-ink/75">
      <p className="flex items-center gap-2.5">
        <svg aria-hidden viewBox="0 0 24 24" className="h-4 w-4 flex-none text-pine" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <rect x="1.5" y="6" width="14" height="11" rx="1.5" />
          <path d="M15.5 9.5h3.6l3.4 3.7V17h-7" />
          <circle cx="6" cy="17.5" r="1.8" />
          <circle cx="18" cy="17.5" r="1.8" />
        </svg>
        <span>
          <span className="font-medium text-ink">Ships in 1–2 business days</span>
          {" — "}each piece is engraved or printed to order in our workshop.
        </span>
      </p>
      <p className="flex items-center gap-2.5">
        <svg aria-hidden viewBox="0 0 24 24" className="h-4 w-4 flex-none text-pine" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M3 12a9 9 0 1 0 3-6.7" />
          <path d="M3 4v5h5" />
        </svg>
        <span>
          Something not right? See our{" "}
          <Link href="/returns" className="text-pine underline underline-offset-2 hover:text-pine-deep">
            returns policy
          </Link>
          .
        </span>
      </p>
      <p className="flex items-center gap-2.5 border-t border-dashed border-line pt-2.5">
        <svg aria-hidden viewBox="0 0 24 24" className="h-4 w-4 flex-none text-pine" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <rect x="4" y="10" width="16" height="10" rx="1.5" />
          <path d="M8 10V7a4 4 0 0 1 8 0v3" />
        </svg>
        <span className="flex flex-wrap items-center gap-x-2 gap-y-1">
          Secure checkout via Stripe
          <span aria-hidden className="inline-flex items-center gap-1.5">
            {["VISA", "MC", "AMEX", "DISC"].map((label) => (
              <span
                key={label}
                className="rounded-[3px] border border-line bg-white px-1.5 py-0.5 font-mono text-[9px] tracking-wide text-ink/60"
              >
                {label}
              </span>
            ))}
          </span>
          <span className="sr-only">Visa, Mastercard, American Express and Discover accepted.</span>
        </span>
      </p>
    </div>
  );
}
