"use client";

import { useActionState } from "react";
import { login, type LoginState } from "./actions";

export function LoginForm() {
  const [state, formAction, pending] = useActionState<LoginState, FormData>(login, null);

  return (
    <form action={formAction} className="space-y-4">
      <div>
        <label
          htmlFor="password"
          className="mb-1 block font-mono text-xs uppercase tracking-wide text-ink/50"
        >
          Password
        </label>
        <input
          id="password"
          name="password"
          type="password"
          required
          autoFocus
          autoComplete="current-password"
          className="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink outline-none focus:border-pine focus:ring-1 focus:ring-pine"
        />
      </div>
      {state?.error ? (
        <p role="alert" className="rounded-sm border border-dashed border-amber/60 bg-amber/10 px-3 py-2 font-mono text-xs text-amber">
          {state.error}
        </p>
      ) : null}
      <button
        type="submit"
        disabled={pending}
        className="w-full rounded-md bg-pine px-4 py-2 font-mono text-sm text-paper transition-colors hover:bg-pine-deep disabled:opacity-60"
      >
        {pending ? "Signing in…" : "Sign in"}
      </button>
    </form>
  );
}
