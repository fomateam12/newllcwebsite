import type { Metadata } from "next";
import Link from "next/link";
import { Tag } from "@/components/price-tag";
import { siteConfig } from "@/lib/site-config";
import { absoluteUrl } from "@/lib/site";
import { PageHeader, SectionHeading } from "../page-header";

export async function generateMetadata(): Promise<Metadata> {
  const description = `Made-to-order production in ${siteConfig.productionTime}, then ${siteConfig.shippingEstimate} in transit. US-wide shipping with tracking on every order.`;
  const canonical = absoluteUrl("/shipping");
  return {
    title: "Shipping & Production Times",
    description,
    alternates: { canonical },
    openGraph: {
      title: "Shipping & Production Times",
      description,
      type: "website",
      url: canonical,
      siteName: siteConfig.brand,
    },
  };
}

export default function ShippingPage() {
  return (
    <article>
      <PageHeader
        kicker="Shipping"
        title="From our workshop to your door"
        intro="Everything we sell is made to order, so your timeline has two parts: the days we spend making it, and the days the carrier spends carrying it."
      />

      <SectionHeading>What to expect</SectionHeading>
      <div className="overflow-x-auto rounded-md border border-line bg-white">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-line font-mono text-xs uppercase tracking-wider text-ink/50">
              <th scope="col" className="px-4 py-3 font-medium">Stage</th>
              <th scope="col" className="px-4 py-3 font-medium">Time</th>
              <th scope="col" className="px-4 py-3 font-medium">Notes</th>
            </tr>
          </thead>
          <tbody className="text-ink/75">
            <tr className="border-b border-line">
              <td className="px-4 py-3 font-display text-base text-ink">
                Production
              </td>
              <td className="px-4 py-3 whitespace-nowrap">
                <Tag>{siteConfig.productionTime}</Tag>
              </td>
              <td className="px-4 py-3 leading-relaxed">
                We engrave or print your order after you place it — nothing
                sits pre-made on a shelf.
              </td>
            </tr>
            <tr className="border-b border-line">
              <td className="px-4 py-3 font-display text-base text-ink">
                Shipping
              </td>
              <td className="px-4 py-3 whitespace-nowrap">
                <Tag>{siteConfig.shippingEstimate}</Tag>
              </td>
              <td className="px-4 py-3 leading-relaxed">
                Carrier transit time via USPS or UPS, depending on size and
                destination.
              </td>
            </tr>
            <tr>
              <td className="px-4 py-3 font-display text-base text-ink">
                Rush orders
              </td>
              <td className="px-4 py-3 whitespace-nowrap">
                <Tag tone="pine">Ask us</Tag>
              </td>
              <td className="px-4 py-3 leading-relaxed">
                Need it sooner?{" "}
                <Link href="/contact" className="text-pine underline">
                  Contact us
                </Link>{" "}
                before ordering and we&apos;ll tell you honestly what&apos;s
                possible.
                {/* TODO: define a formal rush-order option and pricing. */}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <p className="mt-3 text-sm text-ink/60">
        Rule of thumb: most orders arrive within about a week of checkout.
        During peak gifting seasons (Christmas, Mother&apos;s and Father&apos;s
        Day) production can run a day or two longer — we&apos;ll note it on
        the site when it does.
      </p>

      <SectionHeading>Where we ship</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        We currently ship to addresses in the <strong>United States</strong>{" "}
        only, from our workshop in {siteConfig.address.city},{" "}
        {siteConfig.address.state}. International shipping isn&apos;t
        available yet — if you&apos;re outside the US and really want
        something,{" "}
        <Link href="/contact" className="text-pine underline">
          write to us
        </Link>{" "}
        and we&apos;ll see what we can do.
      </p>

      <SectionHeading>Tracking</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        Every order ships with tracking. As soon as your package is labeled,
        we email the tracking number to the address on your order — usually
        the same day it leaves the workshop. If the tracking hasn&apos;t moved
        for a few days or an email never arrived, check your spam folder
        first, then{" "}
        <Link href="/contact" className="text-pine underline">
          get in touch
        </Link>{" "}
        and we&apos;ll chase it down.
      </p>

      <SectionHeading>Damaged in transit?</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        If your order arrives damaged, take a photo of the item and the
        packaging and send it to us within {siteConfig.returnsWindowDays}{" "}
        days. We&apos;ll remake it free of charge — see our{" "}
        <Link href="/returns" className="text-pine underline">
          returns &amp; free-redo policy
        </Link>{" "}
        for the details.
      </p>
    </article>
  );
}
