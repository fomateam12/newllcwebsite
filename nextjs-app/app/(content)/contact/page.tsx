import type { Metadata } from "next";
import { siteConfig } from "@/lib/site-config";
import { absoluteUrl } from "@/lib/site";
import { PageHeader } from "../page-header";
import { ContactForm } from "./contact-form";

export async function generateMetadata(): Promise<Metadata> {
  const description = `Questions about an order, personalization, or a bulk quote? Write to the ${siteConfig.brand} workshop in ${siteConfig.address.city}, ${siteConfig.address.state} — we reply within one business day.`;
  const canonical = absoluteUrl("/contact");
  return {
    title: "Contact Us",
    description,
    alternates: { canonical },
    openGraph: {
      title: "Contact FomaFamily",
      description,
      type: "website",
      url: canonical,
      siteName: siteConfig.brand,
    },
  };
}

export default function ContactPage() {
  return (
    <article>
      <PageHeader
        kicker="Contact"
        title="Talk to the workshop"
        intro="No ticket queues, no chatbots. Your message lands with the same family that engraves and prints your order — and we reply within one business day."
      />

      <div className="grid gap-10 pt-8 md:grid-cols-[1fr_260px]">
        <ContactForm />

        <aside className="space-y-5">
          <div className="rounded-md border border-line bg-white p-4">
            <h2 className="font-mono text-xs uppercase tracking-wider text-ink/50">
              Workshop
            </h2>
            <address className="mt-2 text-sm not-italic leading-relaxed text-ink/75">
              {siteConfig.companyName}
              <br />
              {siteConfig.address.street}
              <br />
              {siteConfig.address.city}, {siteConfig.address.state}{" "}
              {siteConfig.address.zip}
            </address>
          </div>

          <div className="rounded-md border border-line bg-white p-4">
            <h2 className="font-mono text-xs uppercase tracking-wider text-ink/50">
              Email
            </h2>
            <p className="mt-2 text-sm">
              <a
                href={`mailto:${siteConfig.email}`}
                className="text-pine underline"
              >
                {siteConfig.email}
              </a>
            </p>
          </div>

          <div className="rounded-md border border-line bg-white p-4">
            <h2 className="font-mono text-xs uppercase tracking-wider text-ink/50">
              Phone
            </h2>
            <p className="mt-2 text-sm text-ink/75">{siteConfig.phone}</p>
          </div>

          <p className="rounded-md border border-dashed border-amber/60 bg-white/60 px-4 py-3 text-xs leading-relaxed text-ink/70">
            We answer within <strong>one business day</strong> — usually much
            faster during workshop hours (Mon–Fri, Eastern time).
          </p>
        </aside>
      </div>
    </article>
  );
}
