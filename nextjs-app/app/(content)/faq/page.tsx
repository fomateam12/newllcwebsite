import type { Metadata } from "next";
import Link from "next/link";
import { siteConfig } from "@/lib/site-config";
import { absoluteUrl } from "@/lib/site";
import { PageHeader } from "../page-header";

export async function generateMetadata(): Promise<Metadata> {
  const description =
    "Answers about personalization, proofs, shipping times, our free-redo policy, bulk orders, and how to care for engraved steel and printed textiles.";
  const canonical = absoluteUrl("/faq");
  return {
    title: "Frequently Asked Questions",
    description,
    alternates: { canonical },
    openGraph: {
      title: "Frequently Asked Questions",
      description,
      type: "website",
      url: canonical,
      siteName: siteConfig.brand,
    },
  };
}

type Faq = { q: string; a: React.ReactNode };
type FaqSection = { heading: string; items: Faq[] };

const SECTIONS: FaqSection[] = [
  {
    heading: "Personalization",
    items: [
      {
        q: "How does personalization work?",
        a: (
          <>
            Pick a product, then add your text — names, dates, a short message
            — or upload a photo where the product allows it. We take exactly
            what you submit and engrave or print it in our workshop. Every
            piece is made after you order it; nothing is pre-made.
          </>
        ),
      },
      {
        q: "Will I see a proof or preview before you make it?",
        a: (
          <>
            For photo products and complex layouts, we&apos;ll email you a
            digital proof before anything touches the laser or the press —
            just reply to approve or request changes. For simple name and date
            engravings we usually go straight to production so your order
            ships faster. If you&apos;d like a proof on any order, say so in
            the order notes or{" "}
            <Link href="/contact" className="text-pine underline">
              contact us
            </Link>{" "}
            right after checkout.
          </>
        ),
      },
      {
        q: "Are there character limits on engraving text?",
        a: (
          <>
            Yes — the limit depends on the item and is shown on each
            product&apos;s personalization fields. As a rule of thumb, short
            reads better: a name and a date engraves beautifully; a paragraph
            does not. If you&apos;re unsure whether your text will fit, ask us
            and we&apos;ll check it against the actual engraving area.
          </>
        ),
      },
      {
        q: "What if I made a typo in my personalization?",
        a: (
          <>
            Contact us immediately — if production hasn&apos;t started
            (usually within a few hours of ordering), we&apos;ll fix it free.
            Once an item is engraved or printed with the text you submitted,
            we can&apos;t undo it, but we can usually offer a discounted
            remake. If the typo was <em>ours</em>, the remake is always free.
          </>
        ),
      },
    ],
  },
  {
    heading: "Shipping",
    items: [
      {
        q: "How long until my order arrives?",
        a: (
          <>
            Production takes {siteConfig.productionTime}, then shipping takes
            about {siteConfig.shippingEstimate}. Most orders arrive within a
            week of checkout. Full details on the{" "}
            <Link href="/shipping" className="text-pine underline">
              shipping page
            </Link>
            .
          </>
        ),
      },
      {
        q: "Do you ship internationally?",
        a: (
          <>
            Not yet — we currently ship to US addresses only, straight from
            our workshop in {siteConfig.address.city},{" "}
            {siteConfig.address.state}.
          </>
        ),
      },
      {
        q: "Will I get tracking?",
        a: (
          <>
            Always. A tracking number is emailed to you the day your order
            ships. If it never arrives, check spam, then{" "}
            <Link href="/contact" className="text-pine underline">
              write to us
            </Link>
            .
          </>
        ),
      },
    ],
  },
  {
    heading: "Returns & free redo",
    items: [
      {
        q: "Can I return a personalized item?",
        a: (
          <>
            Personalized items can&apos;t be returned for change of mind —
            they were made just for you. But if the mistake is ours (a typo we
            introduced, a defect, shipping damage), we remake it{" "}
            <strong>free</strong> or refund you. You have{" "}
            {siteConfig.returnsWindowDays} days from delivery to report an
            issue. The whole policy is on the{" "}
            <Link href="/returns" className="text-pine underline">
              returns page
            </Link>
            .
          </>
        ),
      },
      {
        q: "What is the “free redo” exactly?",
        a: (
          <>
            Our promise that our mistake never costs you money. Send a photo
            of the problem within {siteConfig.returnsWindowDays} days and we
            remake the piece at no charge — no return shipping, no restocking
            fee, no forms. You don&apos;t even have to send the original back.
          </>
        ),
      },
    ],
  },
  {
    heading: "Bulk & business orders",
    items: [
      {
        q: "Do you take bulk orders (weddings, corporate gifts, teams)?",
        a: (
          <>
            Yes — engraved tumblers for a wedding party, printed shirts for a
            family reunion, corporate gifts with a logo. Volume pricing is
            coming to the site; until then,{" "}
            <Link href="/contact" className="text-pine underline">
              contact us with the quantity and item
            </Link>{" "}
            and we&apos;ll send a quote, usually within a business day.
          </>
        ),
      },
    ],
  },
  {
    heading: "Care instructions",
    items: [
      {
        q: "How do I care for engraved tumblers and bottles?",
        a: (
          <>
            Hand-wash with warm soapy water and a soft sponge — no dishwasher.
            The engraving itself is cut into the steel and won&apos;t fade,
            but dishwasher heat can damage the vacuum insulation and the
            powder coat around it. Avoid abrasive scrubbers and long soaks.
          </>
        ),
      },
      {
        q: "How do I wash printed blankets, towels, and shirts?",
        a: (
          <>
            Machine-wash cold, inside out, on a gentle cycle with mild
            detergent. Tumble dry low or hang dry. Skip bleach and fabric
            softener, and don&apos;t iron directly over the printed area — if
            you must iron, do it inside out on low. Treated kindly, the print
            outlasts the argument about whose blanket it is.
          </>
        ),
      },
    ],
  },
];

export default function FaqPage() {
  return (
    <article>
      <PageHeader
        kicker="Questions"
        title="Frequently asked questions"
        intro="The things people ask us most, answered by the people who actually run the laser. Can't find yours? Write to us — a person replies."
      />

      {SECTIONS.map((section) => (
        <section key={section.heading} className="mt-10">
          <h2 className="mb-4 font-display text-xl text-ink">
            {section.heading}
          </h2>
          <div className="space-y-3">
            {section.items.map((item) => (
              <details
                key={item.q}
                className="group rounded-md border border-line bg-white open:shadow-sm"
              >
                <summary className="flex cursor-pointer list-none items-baseline justify-between gap-4 px-4 py-3 font-display text-base text-pine-deep marker:content-none [&::-webkit-details-marker]:hidden">
                  {item.q}
                  <span
                    aria-hidden
                    className="shrink-0 font-mono text-sm text-amber transition-transform group-open:rotate-45"
                  >
                    +
                  </span>
                </summary>
                <div className="border-t border-dashed border-line px-4 py-3 text-sm leading-relaxed text-ink/75">
                  {item.a}
                </div>
              </details>
            ))}
          </div>
        </section>
      ))}

      <p className="mt-12 rounded-md border border-dashed border-amber/60 bg-white/60 px-4 py-3 text-sm text-ink/75">
        Still curious about something?{" "}
        <Link href="/contact" className="text-pine underline">
          Ask us directly
        </Link>{" "}
        or email{" "}
        <a href={`mailto:${siteConfig.email}`} className="text-pine underline">
          {siteConfig.email}
        </a>
        . We answer within one business day.
      </p>
    </article>
  );
}
