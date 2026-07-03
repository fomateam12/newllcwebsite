import type { Metadata } from "next";
import Link from "next/link";
import { formatAddress, siteConfig } from "@/lib/site-config";
import { absoluteUrl } from "@/lib/site";
import { PageHeader, SectionHeading } from "../page-header";

export async function generateMetadata(): Promise<Metadata> {
  const description = `The terms that govern purchases from ${siteConfig.companyName}: ordering, personalization, payment, shipping, returns, and everything in between.`;
  const canonical = absoluteUrl("/terms");
  return {
    title: "Terms of Service",
    description,
    alternates: { canonical },
    openGraph: {
      title: "Terms of Service",
      description,
      type: "website",
      url: canonical,
      siteName: siteConfig.brand,
    },
  };
}

export default function TermsPage() {
  return (
    <article>
      <PageHeader
        kicker="Terms"
        title="Terms of Service"
        intro="These are the ground rules for using our store and ordering from our workshop. We've kept the legalese as light as we could."
      />
      <p className="mt-6 font-mono text-xs text-ink/50">
        Last updated: July 2026
      </p>

      <SectionHeading>1. Who you&apos;re buying from</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        This website is operated by {siteConfig.companyName} (&quot;
        {siteConfig.brand}&quot;, &quot;we&quot;, &quot;us&quot;), located at{" "}
        {formatAddress()}, United States. By placing an order or using this
        site, you agree to these terms. If you don&apos;t agree, please
        don&apos;t use the site.
      </p>

      <SectionHeading>2. Orders &amp; personalization</SectionHeading>
      <ul className="list-disc space-y-2 pl-5 leading-relaxed text-ink/75">
        <li>
          All products are <strong>made to order</strong>. Production
          typically takes {siteConfig.productionTime} before shipping.
        </li>
        <li>
          We produce exactly the personalization you submit — please
          double-check spelling, dates, and photo quality before checkout.
          You are responsible for the accuracy of the text and images you
          provide.
        </li>
        <li>
          You must own or have permission to use any text, names, logos, or
          photos you submit. Don&apos;t send us content that infringes
          someone else&apos;s rights or that is unlawful; we may refuse or
          cancel such orders.
        </li>
        <li>
          We may cancel any order (with a full refund) if we can&apos;t
          produce it to our standards or if it violates these terms.
        </li>
      </ul>

      <SectionHeading>3. Prices &amp; payment</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        Prices are shown in US dollars and may change at any time before you
        order; the price at checkout is the price you pay. Payments are
        processed securely by <strong>Stripe</strong> — we never see or store
        your full card details. Applicable sales tax is calculated at
        checkout where required.
      </p>

      <SectionHeading>4. Shipping</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        We ship within the United States only. Estimated transit time is{" "}
        {siteConfig.shippingEstimate} after production. Delivery dates are
        estimates, not guarantees — carriers occasionally have other plans.
        See our{" "}
        <Link href="/shipping" className="text-pine underline">
          shipping page
        </Link>{" "}
        for full details. Risk of loss passes to you upon our delivery to the
        carrier, but if a package arrives damaged we&apos;ll make it right
        under our free-redo policy.
      </p>

      <SectionHeading>5. Returns &amp; remakes</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        Personalized items cannot be returned for change of mind, but if the
        error is ours — a typo we introduced, a defect, or shipping damage —
        we will remake the item free of charge or refund you. You have{" "}
        {siteConfig.returnsWindowDays} days from delivery to report an issue.
        Non-personalized items may be returned per our{" "}
        <Link href="/returns" className="text-pine underline">
          returns policy
        </Link>
        , which is part of these terms.
      </p>

      <SectionHeading>6. Intellectual property</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        The site, our product designs, photos, and copy belong to{" "}
        {siteConfig.companyName} or our licensors. You may not copy or reuse
        them commercially without our written permission. You keep all rights
        to the personal content you submit; you grant us a limited license to
        use it solely to produce and support your order.
      </p>

      <SectionHeading>7. Disclaimers &amp; liability</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        We put real care into every piece, but the site and products are
        provided &quot;as is&quot; to the fullest extent permitted by law.
        Handmade and engraved goods can have slight natural variations —
        that&apos;s part of their character, not a defect. To the maximum
        extent permitted by law, our total liability for any claim related to
        an order is limited to the amount you paid for that order. Nothing in
        these terms limits rights you have under applicable law that cannot
        be waived.
      </p>

      <SectionHeading>8. Governing law &amp; disputes</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        These terms are governed by the laws of the State of Georgia, USA,
        without regard to conflict-of-law rules. Before any formal dispute,
        please contact us first — nearly everything can be solved with an
        email and a remake. Any dispute that can&apos;t be resolved
        informally will be handled in the state or federal courts located in
        Georgia.
      </p>

      <SectionHeading>9. Changes &amp; contact</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        We may update these terms from time to time; continued use of the
        site after changes means you accept them. Questions about these
        terms? Email{" "}
        <a href={`mailto:${siteConfig.email}`} className="text-pine underline">
          {siteConfig.email}
        </a>
        , call {siteConfig.phone}, or write to {formatAddress()}.
      </p>
    </article>
  );
}

// <!-- TODO: requires attorney review before launch -->
