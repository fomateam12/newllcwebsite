import type { Metadata } from "next";
import Link from "next/link";
import { Tag } from "@/components/price-tag";
import { siteConfig } from "@/lib/site-config";
import { absoluteUrl } from "@/lib/site";
import { PageHeader, SectionHeading } from "../page-header";

export async function generateMetadata(): Promise<Metadata> {
  const description = `Personalized items are made just for you, so they can't be restocked — but if we get it wrong, we remake it free. ${siteConfig.returnsWindowDays} days to report any issue.`;
  const canonical = absoluteUrl("/returns");
  return {
    title: "Returns & Free Redo Policy",
    description,
    alternates: { canonical },
    openGraph: {
      title: "Returns & Free Redo Policy",
      description,
      type: "website",
      url: canonical,
      siteName: siteConfig.brand,
    },
  };
}

export default function ReturnsPage() {
  return (
    <article>
      <PageHeader
        kicker="Returns"
        title="Our promise: if we get it wrong, we make it right"
        intro="Personalized gifts can't go back on a shelf — your name is on them. So instead of a returns bin, we have a simple rule: our mistake, our cost. Always."
      />

      <SectionHeading>The free redo</SectionHeading>
      <div className="rounded-md border border-line bg-white p-5">
        <Tag>Free redo</Tag>
        <p className="mt-3 leading-relaxed text-ink/75">
          If your order arrives with a mistake that&apos;s ours — a typo we
          introduced, a crooked engraving, a print defect, or damage from
          shipping — we will <strong>remake it free of charge</strong> or
          refund you, whichever you prefer. No return shipping, no restocking
          fee, no debate. Send us a photo, and a new one goes on the laser or
          the press.
        </p>
      </div>
      <p className="mt-3 text-sm text-ink/60">
        You have {siteConfig.returnsWindowDays} days from delivery to report a
        problem. A quick photo of the item (and the packaging, if it arrived
        damaged) is all we need to get started —{" "}
        <Link href="/contact" className="text-pine underline">
          contact us here
        </Link>{" "}
        or email{" "}
        <a href={`mailto:${siteConfig.email}`} className="text-pine underline">
          {siteConfig.email}
        </a>
        .
      </p>

      <SectionHeading>Personalized items</SectionHeading>
      <div className="space-y-4 leading-relaxed text-ink/75">
        <p>
          Because every personalized piece is engraved or printed to your
          order, we can&apos;t accept returns for change of mind, a
          typo in the personalization you submitted, or an ordering mistake
          (wrong size or color selected). There&apos;s simply no one else we
          could sell a blanket with your family&apos;s names on it to — and
          honestly, we wouldn&apos;t want to.
        </p>
        <p>
          That said, we&apos;re human and we like happy customers. If
          something went sideways with your order — even if the mistake was
          on your end — write to us anyway. We can&apos;t promise a refund,
          but we can often offer a discounted remake, and we&apos;ll always
          try to find something fair.
        </p>
        <p className="text-sm text-ink/60">
          Tip: double-check spelling and dates before you check out. We
          engrave exactly what you type, including that extra
          &quot;n&quot; in &quot;Jonhathan&quot;.
        </p>
      </div>

      <SectionHeading>Non-personalized items</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        Items without personalization can be returned within{" "}
        {siteConfig.returnsWindowDays} days of delivery for a refund, as long
        as they&apos;re unused and in their original condition. Contact us
        first so we can send return instructions; return shipping for
        change-of-mind returns is the buyer&apos;s responsibility. Once the
        item is back with us, we&apos;ll refund the purchase price to your
        original payment method.
      </p>

      <SectionHeading>How refunds work</SectionHeading>
      <ul className="list-disc space-y-2 pl-5 leading-relaxed text-ink/75">
        <li>
          Approved refunds go back to your original payment method, processed
          through Stripe.
        </li>
        <li>
          Refunds are issued within a few business days of approval; your
          bank may take a few more days to post it.
        </li>
        <li>
          If we remake an order under the free redo, there&apos;s nothing to
          send back — keep or donate the original piece.
        </li>
      </ul>

      <SectionHeading>Questions?</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        Unsure whether your situation is covered? Just ask. Reach us through
        the{" "}
        <Link href="/contact" className="text-pine underline">
          contact page
        </Link>{" "}
        or at{" "}
        <a href={`mailto:${siteConfig.email}`} className="text-pine underline">
          {siteConfig.email}
        </a>{" "}
        — a real person from the workshop reads every message.
      </p>
    </article>
  );
}
