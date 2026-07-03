import { NextRequest, NextResponse } from "next/server";

/**
 * PLACEHOLDER AUTH — protects /admin with a single shared password via
 * HTTP Basic Auth (username is ignored). Good enough to keep the order
 * dashboard off the public internet until real authentication (per-user
 * accounts, sessions, rate limiting) lands. The credential comes from
 * the ADMIN_PASSWORD environment variable (.dev.vars locally, a Workers
 * secret in production). If ADMIN_PASSWORD is unset, /admin is locked
 * shut rather than left open.
 *
 * The matcher below scopes this middleware to /admin only — the
 * storefront never passes through it.
 */

/**
 * Constant-time string comparison. Both inputs are SHA-256 hashed first,
 * which normalizes length (so length differences leak nothing) and lets
 * us compare fixed-size buffers byte-by-byte without early exit.
 */
async function safeEqual(a: string, b: string): Promise<boolean> {
  const enc = new TextEncoder();
  const [da, db] = await Promise.all([
    crypto.subtle.digest("SHA-256", enc.encode(a)),
    crypto.subtle.digest("SHA-256", enc.encode(b)),
  ]);
  const va = new Uint8Array(da);
  const vb = new Uint8Array(db);
  let diff = 0;
  for (let i = 0; i < va.length; i++) diff |= va[i] ^ vb[i];
  return diff === 0;
}

function unauthorized(): NextResponse {
  return new NextResponse("Authentication required", {
    status: 401,
    headers: { "WWW-Authenticate": 'Basic realm="FomaFamily Admin"' },
  }) as NextResponse;
}

export async function middleware(request: NextRequest) {
  const expected = process.env.ADMIN_PASSWORD;
  // Fail closed: no password configured means no admin access.
  if (!expected) return unauthorized();

  const header = request.headers.get("authorization") ?? "";
  if (!header.startsWith("Basic ")) return unauthorized();

  let password = "";
  try {
    const decoded = atob(header.slice(6));
    // Basic auth payload is "user:password"; we only care about the password.
    password = decoded.slice(decoded.indexOf(":") + 1);
  } catch {
    return unauthorized();
  }

  if (!(await safeEqual(password, expected))) return unauthorized();

  return NextResponse.next();
}

export const config = {
  matcher: ["/admin/:path*"],
};
