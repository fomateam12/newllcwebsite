"use server";

import { getCloudflareContext } from "@opennextjs/cloudflare";
import { z } from "zod";
import { SUBJECTS } from "./subjects";

const contactSchema = z.object({
  name: z
    .string()
    .trim()
    .min(2, "Please tell us your name (at least 2 characters).")
    .max(120, "That name looks too long — 120 characters max."),
  email: z
    .string()
    .trim()
    .email("That email address doesn't look right.")
    .max(254, "That email address looks too long."),
  subject: z.enum(SUBJECTS, {
    message: "Please pick a subject from the list.",
  }),
  message: z
    .string()
    .trim()
    .min(10, "Give us a little more to go on — at least 10 characters.")
    .max(5000, "Please keep your message under 5,000 characters."),
});

export type ContactFormState = {
  status: "idle" | "success" | "invalid" | "failed";
  /** Field-level validation messages, keyed by input name. */
  errors?: Partial<Record<"name" | "email" | "subject" | "message", string>>;
  /** Echo of submitted values so the form can re-fill on error. */
  values?: { name: string; email: string; subject: string; message: string };
};

export async function submitContactMessage(
  _prev: ContactFormState,
  formData: FormData,
): Promise<ContactFormState> {
  const values = {
    name: String(formData.get("name") ?? ""),
    email: String(formData.get("email") ?? ""),
    subject: String(formData.get("subject") ?? ""),
    message: String(formData.get("message") ?? ""),
  };

  // Honeypot: real visitors never see or fill this field. Bots do.
  // Pretend success so the bot moves on; store nothing.
  if (String(formData.get("company_website") ?? "") !== "") {
    return { status: "success" };
  }

  const parsed = contactSchema.safeParse(values);
  if (!parsed.success) {
    const errors: NonNullable<ContactFormState["errors"]> = {};
    for (const issue of parsed.error.issues) {
      const field = issue.path[0] as keyof NonNullable<
        ContactFormState["errors"]
      >;
      if (field && !errors[field]) errors[field] = issue.message;
    }
    return { status: "invalid", errors, values };
  }

  // TODO: the contact_messages table only exists as a draft migration
  // (migrations/drafts/contact_messages.sql). Until it is applied to D1,
  // this insert will fail and we fall back to the "email us" message.
  try {
    const { env } = getCloudflareContext();
    await env.DB.prepare(
      `INSERT INTO contact_messages (name, email, subject, message)
       VALUES (?1, ?2, ?3, ?4)`,
    )
      .bind(
        parsed.data.name,
        parsed.data.email,
        parsed.data.subject,
        parsed.data.message,
      )
      .run();
    return { status: "success" };
  } catch (err) {
    console.error("contact form: failed to store message", err);
    return { status: "failed", values };
  }
}
