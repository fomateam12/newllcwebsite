import type { Metadata } from "next";
import { LoginForm } from "./login-form";

export const metadata: Metadata = {
  title: "Admin sign in",
  robots: { index: false, follow: false },
};

export default function AdminLoginPage() {
  return (
    <div className="mx-auto max-w-sm py-16">
      <div className="rounded-md border border-line bg-white p-6">
        <h2 className="font-display text-lg font-semibold text-pine-deep">
          Sign in to the workshop
        </h2>
        <p className="mb-5 mt-1 text-sm text-ink/60">
          Enter the admin password to manage orders.
        </p>
        <LoginForm />
      </div>
      <p className="mt-4 text-center font-mono text-xs text-ink/40">
        Sessions last 24 hours. Five failed attempts lock this address out
        for 15 minutes.
      </p>
    </div>
  );
}
