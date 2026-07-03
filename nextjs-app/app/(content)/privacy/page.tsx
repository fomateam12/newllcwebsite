import type { Metadata } from "next";
import Link from "next/link";
import { formatAddress, siteConfig } from "@/lib/site-config";
import { absoluteUrl } from "@/lib/site";
import { PageHeader, SectionHeading } from "../page-header";

export async function generateMetadata(): Promise<Metadata> {
  const description = `How ${siteConfig.companyName} collects, uses, and protects your information. We collect only what we need to make and ship your order — and we never sell your data.`;
  const canonical = absoluteUrl("/privacy");
  return {
    title: "Privacy Policy",
    description,
    alternates: { canonical },
    openGraph: {
      title: "Privacy Policy",
      description,
      type: "website",
      url: canonical,
      siteName: siteConfig.brand,
    },
  };
}

export default function PrivacyPage() {
  return (
    <article>
      <PageHeader
        kicker="Privacy"
        title="Privacy Policy"
        intro="The short version: we collect what we need to make and ship your order, we protect it, and we never sell it. The longer version follows."
      />
      <p className="mt-6 font-mono text-xs text-ink/50">
        Last updated: July 2026
      </p>

      <SectionHeading>Who we are</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        This website is operated by {siteConfig.companyName} (&quot;
        {siteConfig.brand}&quot;, &quot;we&quot;, &quot;us&quot;), a
        family-run business located at {formatAddress()}, United States. This
        policy describes how we handle personal information collected through
        our website and in connection with your orders.
      </p>

      <SectionHeading>Information we collect</SectionHeading>
      <ul className="list-disc space-y-2 pl-5 leading-relaxed text-ink/75">
        <li>
          <strong>Order information</strong> — your name, email address,
          shipping address, phone number (if provided), and the details of
          what you ordered, including any personalization text or photos you
          submit for engraving or printing.
        </li>
        <li>
          <strong>Payment information</strong> — payments are processed by{" "}
          <strong>Stripe</strong>. Your card number never touches our
          servers; we receive only a confirmation of payment and the last
          details needed to fulfill your order. Stripe&apos;s handling of
          your data is governed by its own privacy policy.
        </li>
        <li>
          <strong>Messages</strong> — if you contact us, we keep your name,
          email address, and message so we can reply and keep a record of the
          conversation.
        </li>
        <li>
          <strong>Analytics</strong> — with your consent where required, we
          collect anonymized usage data (pages visited, device type) to
          understand how the store is used and improve it.
        </li>
      </ul>

      <SectionHeading>How we use it</SectionHeading>
      <ul className="list-disc space-y-2 pl-5 leading-relaxed text-ink/75">
        <li>To make, personalize, ship, and support your orders.</li>
        <li>
          To send transactional email — order confirmations, shipping
          notifications with tracking, and replies to your messages.
        </li>
        <li>To prevent fraud and comply with legal obligations.</li>
        <li>To improve the store based on aggregated, anonymized usage.</li>
      </ul>
      <p className="mt-3 leading-relaxed text-ink/75">
        We do <strong>not</strong> sell, rent, or trade your personal
        information to anyone. We share it only with the service providers
        who make the store run: our payment processor (Stripe), shipping
        carriers (to deliver your package), our email provider (to send order
        emails), and our hosting infrastructure.
      </p>

      <SectionHeading>Personalization content</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        Names, dates, messages, and photos you submit for personalization are
        used solely to produce your order. We may keep them for a reasonable
        period afterward so we can offer a free redo if something goes wrong.
        We never use your photos or personal text for marketing without your
        explicit permission.
      </p>

      <SectionHeading>Cookies</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        We use essential cookies to keep your cart working and, with consent
        where required, analytics cookies as described above. You can control
        cookies through your browser settings; blocking essential cookies may
        prevent checkout from working.
      </p>

      <SectionHeading>Data retention &amp; security</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        We keep order records as long as needed for accounting, tax, and
        warranty purposes, then delete or anonymize them. Data is transmitted
        over encrypted connections (HTTPS) and stored with access limited to
        the family members who need it to fulfill orders.
      </p>

      <SectionHeading>Your rights</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        You may request a copy of the personal information we hold about you,
        ask us to correct it, or ask us to delete it (subject to records we
        must keep by law). Residents of certain states, including California,
        may have additional rights under applicable law. To exercise any of
        these rights, email{" "}
        <a href={`mailto:${siteConfig.email}`} className="text-pine underline">
          {siteConfig.email}
        </a>{" "}
        and we&apos;ll respond within a reasonable time.
      </p>

      <SectionHeading>Children</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        Our store is intended for adults. We do not knowingly collect
        personal information from children under 13. If you believe a child
        has provided us information, contact us and we will delete it.
      </p>

      <SectionHeading>Changes &amp; contact</SectionHeading>
      <p className="leading-relaxed text-ink/75">
        We may update this policy as the store evolves; the &quot;last
        updated&quot; date above will change when we do. Questions? Reach us
        at{" "}
        <a href={`mailto:${siteConfig.email}`} className="text-pine underline">
          {siteConfig.email}
        </a>
        , by phone at {siteConfig.phone}, by mail at {formatAddress()}, or
        through the{" "}
        <Link href="/contact" className="text-pine underline">
          contact page
        </Link>
        .
      </p>
    </article>
  );
}

// <!-- TODO: requires attorney review before launch -->
