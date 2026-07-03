"use server";

import { cookies, headers } from "next/headers";
import { redirect } from "next/navigation";
import { log, logWarn } from "@/lib/logging";
import { limit, peek, reset } from "@/lib/rate-limit";
import {
  createSessionToken,
  getClientIp,
  safeEqual,
  SESSION_COOKIE,
  SESSION_TTL_SECONDS,
} from "@/lib/security";
import { adminLoginSchema } from "@/lib/security-schemas";

export type LoginState = { error: string } | null;

/** 5 failed attempts per IP per 15 minutes → locked out. */
const LOCKOUT = { max: 5, windowSec: 15 * 60 };

const lockoutKey = (ip: string) => `admin-login:${ip}`;

function lockoutMessage(resetAt: number): string {
  const minutes = Math.max(1, Math.ceil((resetAt - Date.now() / 1000) / 60));
  return `Too many failed attempts. Try again in about ${minutes} minute${minutes === 1 ? "" : "s"}.`;
}

export async function login(_prev: LoginState, formData: FormData): Promise<LoginState> {
  const expected = process.env.ADMIN_PASSWORD;
  const ip = getClientIp(await headers());

  // Fail closed: no configured password means no admin access at all.
  if (!expected) {
    logWarn("admin_login_failure", { ip, reason: "ADMIN_PASSWORD not configured" });
    return { error: "Admin sign-in is not configured." };
  }

  // Lockout check BEFORE touching the password — a locked-out IP gets
  // zero verification attempts. peek() consumes nothing, so the lockout
  // window is driven purely by failures.
  const gate = await peek(lockoutKey(ip), LOCKOUT);
  if (!gate.allowed) {
    log("rate_limit_triggered", {
      key: lockoutKey(ip),
      max: LOCKOUT.max,
      windowSec: LOCKOUT.windowSec,
      path: "/admin/login",
    });
    return { error: lockoutMessage(gate.resetAt) };
  }

  const parsed = adminLoginSchema.safeParse({ password: formData.get("password") });
  if (!parsed.success || !(await safeEqual(parsed.data.password, expected))) {
    const result = await limit(lockoutKey(ip), LOCKOUT);
    logWarn("admin_login_failure", {
      ip,
      reason: parsed.success ? "wrong password" : "invalid input",
      attemptsRemaining: result.remaining,
    });
    if (!result.allowed || result.remaining === 0) {
      return { error: lockoutMessage(result.resetAt) };
    }
    return { error: "Incorrect password." };
  }

  // Success: clear the failure counter and mint a fresh session.
  await reset(lockoutKey(ip), LOCKOUT);
  const token = await createSessionToken(expected);
  (await cookies()).set(SESSION_COOKIE, token, {
    httpOnly: true,
    secure: true,
    sameSite: "strict",
    path: "/admin",
    maxAge: SESSION_TTL_SECONDS,
  });
  log("admin_login_success", { ip });
  redirect("/admin");
}

export async function logout(): Promise<void> {
  const ip = getClientIp(await headers());
  (await cookies()).delete({ name: SESSION_COOKIE, path: "/admin" });
  log("admin_logout", { ip });
  redirect("/admin/login");
}
