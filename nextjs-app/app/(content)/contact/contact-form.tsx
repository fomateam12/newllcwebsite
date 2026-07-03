"use client";

import { useActionState } from "react";
import { siteConfig } from "@/lib/site-config";
import { submitContactMessage, type ContactFormState } from "./actions";
import { SUBJECTS } from "./subjects";

const initialState: ContactFormState = { status: "idle" };

const inputClasses =
  "w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-ink/35 focus:border-pine focus:outline-none focus:ring-1 focus:ring-pine";
const labelClasses = "mb-1.5 block font-mono text-xs uppercase tracking-wider text-ink/60";

function FieldError({ message }: { message?: string }) {
  if (!message) return null;
  return (
    <p role="alert" className="mt-1.5 text-xs text-red-700">
      {message}
    </p>
  );
}

export function ContactForm() {
  const [state, formAction, pending] = useActionState(
    submitContactMessage,
    initialState,
  );

  if (state.status === "success") {
    return (
      <div className="rounded-md border border-dashed border-pine/50 bg-white/60 px-5 py-6">
        <p className="font-display text-lg text-pine-deep">
          Message received — thank you!
        </p>
        <p className="mt-2 text-sm leading-relaxed text-ink/70">
          A real person from the workshop will get back to you within one
          business day. If it&apos;s about an order in production, we&apos;ll
          prioritize it.
        </p>
      </div>
    );
  }

  const values = state.values;

  return (
    <form action={formAction} noValidate className="space-y-4">
      {state.status === "failed" && (
        <div
          role="alert"
          className="rounded-md border border-dashed border-amber/60 bg-white/60 px-4 py-3 text-sm leading-relaxed text-ink/80"
        >
          Something went wrong on our end and your message didn&apos;t save.
          Please email us directly at{" "}
          <a
            href={`mailto:${siteConfig.email}`}
            className="text-pine underline"
          >
            {siteConfig.email}
          </a>{" "}
          — we answer every message.
        </div>
      )}

      <div>
        <label htmlFor="contact-name" className={labelClasses}>
          Your name
        </label>
        <input
          id="contact-name"
          name="name"
          type="text"
          autoComplete="name"
          defaultValue={values?.name}
          className={inputClasses}
          aria-invalid={state.errors?.name ? true : undefined}
        />
        <FieldError message={state.errors?.name} />
      </div>

      <div>
        <label htmlFor="contact-email" className={labelClasses}>
          Email
        </label>
        <input
          id="contact-email"
          name="email"
          type="email"
          autoComplete="email"
          defaultValue={values?.email}
          className={inputClasses}
          aria-invalid={state.errors?.email ? true : undefined}
        />
        <FieldError message={state.errors?.email} />
      </div>

      <div>
        <label htmlFor="contact-subject" className={labelClasses}>
          Subject
        </label>
        <select
          id="contact-subject"
          name="subject"
          defaultValue={values?.subject ?? ""}
          className={inputClasses}
          aria-invalid={state.errors?.subject ? true : undefined}
        >
          <option value="" disabled>
            Choose a subject…
          </option>
          {SUBJECTS.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
        <FieldError message={state.errors?.subject} />
      </div>

      <div>
        <label htmlFor="contact-message" className={labelClasses}>
          Message
        </label>
        <textarea
          id="contact-message"
          name="message"
          rows={6}
          defaultValue={values?.message}
          placeholder="Tell us what you need — order numbers, names, dates, impossible ideas all welcome."
          className={inputClasses}
          aria-invalid={state.errors?.message ? true : undefined}
        />
        <FieldError message={state.errors?.message} />
      </div>

      {/* Honeypot — hidden from humans, tempting to bots. */}
      <div aria-hidden="true" className="absolute -left-[9999px] top-auto h-px w-px overflow-hidden">
        <label htmlFor="contact-company-website">
          Leave this field empty
        </label>
        <input
          id="contact-company-website"
          name="company_website"
          type="text"
          tabIndex={-1}
          autoComplete="off"
        />
      </div>

      <button
        type="submit"
        disabled={pending}
        className="rounded-md bg-pine px-5 py-2.5 font-display text-base text-paper transition-colors hover:bg-pine-deep disabled:opacity-60"
      >
        {pending ? "Sending…" : "Send message"}
      </button>
    </form>
  );
}
