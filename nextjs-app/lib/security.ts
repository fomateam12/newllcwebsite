/**
 * Security primitives shared by middleware, API routes and server actions.
 * Everything here is Web Crypto only — no Node-specific APIs — so it runs
 * identically in the Workers runtime, `wrangler dev` and `next dev`.
 */

const encoder = new TextEncoder();

/* ------------------------------------------------------------------ */
/* Constant-time comparison                                            */
/* ------------------------------------------------------------------ */

/**
 * Constant-time string comparison. Both inputs are SHA-256 hashed first,
 * which normalizes length (so length differences leak nothing) and lets
 * us compare fixed-size buffers byte-by-byte without early exit.
 */
export async function safeEqual(a: string, b: string): Promise<boolean> {
  const [da, db] = await Promise.all([
    crypto.subtle.digest("SHA-256", encoder.encode(a)),
    crypto.subtle.digest("SHA-256", encoder.encode(b)),
  ]);
  const va = new Uint8Array(da);
  const vb = new Uint8Array(db);
  let diff = 0;
  for (let i = 0; i < va.length; i++) diff |= va[i] ^ vb[i];
  return diff === 0;
}

/* ------------------------------------------------------------------ */
/* Client IP                                                           */
/* ------------------------------------------------------------------ */

/**
 * Best-effort client IP for rate-limit keys. On Cloudflare,
 * `cf-connecting-ip` is set by the edge and cannot be spoofed by the
 * client; `x-forwarded-for` is a fallback for local dev.
 */
export function getClientIp(headers: Headers): string {
  return (
    headers.get("cf-connecting-ip") ??
    headers.get("x-forwarded-for")?.split(",")[0]?.trim() ??
    "unknown"
  );
}

/* ------------------------------------------------------------------ */
/* JSON API error shape                                                */
/* ------------------------------------------------------------------ */

/**
 * The one true API error shape: `{ error: { code, message } }`.
 * `message` must always be safe to show a client — internal details
 * (stack traces, DB errors, upstream messages) belong in logs only.
 */
export function apiError(
  code: string,
  message: string,
  status: number,
  headers?: HeadersInit,
): Response {
  return Response.json({ error: { code, message } }, { status, headers });
}

/* ------------------------------------------------------------------ */
/* Signed admin session (stateless, HMAC-SHA256)                       */
/* ------------------------------------------------------------------ */

export const SESSION_COOKIE = "admin_session";
export const SESSION_TTL_SECONDS = 60 * 60 * 24; // 24h

const TOKEN_VERSION = "v1";

/**
 * Derive the HMAC signing key from ADMIN_PASSWORD via HKDF-SHA256.
 * Stateless by design: no session store, and rotating ADMIN_PASSWORD
 * invalidates every outstanding session at once.
 */
async function getSessionKey(adminPassword: string): Promise<CryptoKey> {
  const raw = await crypto.subtle.importKey(
    "raw",
    encoder.encode(adminPassword),
    "HKDF",
    false,
    ["deriveKey"],
  );
  return crypto.subtle.deriveKey(
    {
      name: "HKDF",
      hash: "SHA-256",
      salt: encoder.encode("fomafamily-admin-session"),
      info: encoder.encode(TOKEN_VERSION),
    },
    raw,
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign", "verify"],
  );
}

function toBase64Url(buf: ArrayBuffer): string {
  let binary = "";
  const bytes = new Uint8Array(buf);
  for (const b of bytes) binary += String.fromCharCode(b);
  return btoa(binary).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
}

/**
 * Mint a session token: `v1.<expiryUnixSeconds>.<base64url HMAC>`.
 * The signed portion is the version + expiry, so tokens cannot be
 * extended or replayed past their expiry.
 */
export async function createSessionToken(
  adminPassword: string,
  ttlSeconds: number = SESSION_TTL_SECONDS,
): Promise<string> {
  const exp = Math.floor(Date.now() / 1000) + ttlSeconds;
  const payload = `${TOKEN_VERSION}.${exp}`;
  const key = await getSessionKey(adminPassword);
  const sig = await crypto.subtle.sign("HMAC", key, encoder.encode(payload));
  return `${payload}.${toBase64Url(sig)}`;
}

/** Validate a session token: structure, expiry, then HMAC (constant-time). */
export async function verifySessionToken(
  token: string | undefined,
  adminPassword: string | undefined,
): Promise<boolean> {
  if (!token || !adminPassword) return false;
  const parts = token.split(".");
  if (parts.length !== 3 || parts[0] !== TOKEN_VERSION) return false;
  const exp = Number(parts[1]);
  if (!Number.isInteger(exp) || exp <= Math.floor(Date.now() / 1000)) return false;

  const payload = `${TOKEN_VERSION}.${exp}`;
  const key = await getSessionKey(adminPassword);
  const expected = await crypto.subtle.sign("HMAC", key, encoder.encode(payload));
  return safeEqual(parts[2], toBase64Url(expected));
}
