import { NextRequest, NextResponse } from "next/server";
import { SESSION_COOKIE, verifySessionToken } from "@/lib/security";

/**
 * Two jobs, one middleware:
 *
 * 1. SECURITY HEADERS on every response (storefront, admin and API).
 *    CSP starts in Report-Only mode so nothing breaks silently; the
 *    path to enforcing mode is documented in SECURITY-INTEGRATION.md.
 *
 * 2. ADMIN SESSION AUTH for /admin/:path* (except /admin/login).
 *    The old HTTP Basic Auth is replaced by a stateless signed session
 *    cookie (HMAC-SHA256, key derived from ADMIN_PASSWORD — see
 *    lib/security.ts). Login/logout live in app/admin/login/. Fail
 *    closed: no ADMIN_PASSWORD configured means no admin access.
 */

const CSP_REPORT_ONLY = [
  "default-src 'self'",
  // Next.js inlines its bootstrap scripts; move to nonces before enforcing.
  "script-src 'self' 'unsafe-inline'",
  // Next/Tailwind emit inline style attributes.
  "style-src 'self' 'unsafe-inline'",
  // Self-hosted product images (R2 proxy) + data: placeholders; https:
  // leaves room for the future R2 public custom domain.
  "img-src 'self' data: https:",
  "font-src 'self'",
  "connect-src 'self' https://api.stripe.com",
  // Stripe Checkout is redirect-based today; frames stay locked.
  "frame-src 'none'",
  "frame-ancestors 'none'",
  "base-uri 'self'",
  "form-action 'self'",
].join("; ");

function applySecurityHeaders(res: NextResponse): NextResponse {
  res.headers.set("Content-Security-Policy-Report-Only", CSP_REPORT_ONLY);
  res.headers.set("X-Frame-Options", "DENY");
  res.headers.set("X-Content-Type-Options", "nosniff");
  res.headers.set("Referrer-Policy", "strict-origin-when-cross-origin");
  res.headers.set("Permissions-Policy", "camera=(), microphone=(), geolocation=()");
  return res;
}

export async function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  if (pathname.startsWith("/admin")) {
    const token = request.cookies.get(SESSION_COOKIE)?.value;
    const authed = await verifySessionToken(token, process.env.ADMIN_PASSWORD);

    if (pathname === "/admin/login") {
      // Already signed in? Skip the form.
      if (authed && request.method === "GET") {
        return applySecurityHeaders(
          NextResponse.redirect(new URL("/admin", request.url)),
        );
      }
      return applySecurityHeaders(NextResponse.next());
    }

    if (!authed) {
      // Browsers navigating get the login page; programmatic clients
      // (fetch, server-action POSTs with a dead cookie) get a JSON 401.
      if (request.method === "GET") {
        const loginUrl = new URL("/admin/login", request.url);
        loginUrl.searchParams.set("from", pathname);
        return applySecurityHeaders(NextResponse.redirect(loginUrl));
      }
      return applySecurityHeaders(
        NextResponse.json(
          { error: { code: "UNAUTHORIZED", message: "Authentication required" } },
          { status: 401 },
        ),
      );
    }
  }

  return applySecurityHeaders(NextResponse.next());
}

export const config = {
  // Everything except Next's static output and self-hosted assets —
  // security headers belong on pages and API responses alike.
  matcher: ["/((?!_next/static|_next/image|favicon\\.ico).*)"],
};
