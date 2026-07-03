import type { Metadata } from "next";
import Link from "next/link";
import { Tag } from "@/components/price-tag";
import { siteConfig } from "@/lib/site-config";
import { absoluteUrl } from "@/lib/site";
import { PageHeader, SectionHeading } from "../page-header";

export async function generateMetadata(): Promise<Metadata> {
  const description =
    "FomaFamily is a family-run workshop in Alpharetta, Georgia. We laser-engrave tumblers and lighters, and print custom blankets, towels, and shirts — one order at a time.";
  const canonical = absoluteUrl("/about");
  return {
    title: "About Us",
    description,
    alternates: { canonical },
    openGraph: {
      title: "About FomaFamily",
      description,
      type: "website",
      url: canonical,
      siteName: siteConfig.brand,
    },
  };
}

export default function AboutPage() {
  return (
    <article>
      <PageHeader
        kicker="Our story"
        title="A family workshop in Alpharetta, Georgia"
        intro="Every order that leaves our shop was engraved or printed by one of us — no warehouse, no middleman, no shortcuts."
      />

      <div className="space-y-4 pt-8 leading-relaxed text-ink/75">
        <p>
          {siteConfig.brand} started the way a lot of good things do: at a
          kitchen table, with one laser engraver and more ambition than floor
          space. We&apos;re a family — parents, kids, and the occasional
          drafted cousin — and we&apos;ve been making personalized gifts
          together ever since.
        </p>
        <p>
          For years we sold on Etsy, where thousands of orders taught us what
          actually matters: a name spelled exactly right, an engraving deep
          enough to last, a blanket that survives the hundredth wash. This
          store is our own front door — same family, same workshop, same hands
          on every piece.
        </p>
      </div>

      <SectionHeading>What we make</SectionHeading>
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="rounded-md border border-line bg-white p-4">
          <Tag tone="pine">Laser engraving</Tag>
          <p className="mt-3 text-sm leading-relaxed text-ink/75">
            Tumblers, water bottles, and lighters, engraved in-house on our
            own machines. The mark is cut into the surface itself — it
            won&apos;t peel, fade, or wash off, because there&apos;s nothing
            to peel.
          </p>
        </div>
        <div className="rounded-md border border-line bg-white p-4">
          <Tag tone="pine">Custom print</Tag>
          <p className="mt-3 text-sm leading-relaxed text-ink/75">
            Blankets, towels, shirts, and sweatshirts printed with your names,
            photos, and dates. We press every piece ourselves and check the
            colors before it goes in the box.
          </p>
        </div>
      </div>

      <SectionHeading>How an order happens</SectionHeading>
      <ol className="space-y-3">
        {[
          ["You personalize", "Pick a piece, add your names, dates, or photo."],
          [
            "We make it",
            `Your order goes on the laser or the press within ${siteConfig.productionTime} — made for you, not pulled from a shelf.`,
          ],
          [
            "We check it, then ship it",
            "One of us inspects every piece before it's wrapped and handed to the carrier, with tracking sent to your inbox.",
          ],
        ].map(([title, body], i) => (
          <li key={title} className="flex gap-4 rounded-md border border-line bg-white px-4 py-3">
            <span className="font-mono text-sm text-amber">0{i + 1}</span>
            <div>
              <p className="font-display text-ink">{title}</p>
              <p className="mt-1 text-sm leading-relaxed text-ink/70">{body}</p>
            </div>
          </li>
        ))}
      </ol>

      <SectionHeading>Why we do this</SectionHeading>
      <div className="space-y-4 leading-relaxed text-ink/75">
        <p>
          A personalized gift is a small promise: someone thought about you
          specifically. We take that seriously. It&apos;s why we proofread
          every name twice, why we&apos;ll remake an order for free if we get
          it wrong, and why the workshop smells faintly of engraved steel and
          fresh coffee most mornings.
        </p>
        <p>
          If you&apos;re ever in {siteConfig.address.city},{" "}
          {siteConfig.address.state}, you&apos;re driving past the place where
          your gift was made. And if you have a question, an idea, or a
          slightly impossible request —{" "}
          <Link href="/contact" className="text-pine underline">
            write to us
          </Link>
          . A person answers, usually the same one who runs the engraver.
        </p>
      </div>
    </article>
  );
}
