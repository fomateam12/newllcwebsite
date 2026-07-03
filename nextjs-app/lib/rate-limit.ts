import { getCloudflareContext } from "@opennextjs/cloudflare";
import { logWarn } from "@/lib/logging";

/**
 * Fixed-window rate limiter backed by Workers KV (RATE_LIMIT binding).
 *
 * Why KV and not the alternatives (full write-up in SECURITY-INTEGRATION.md):
 *  - The native Workers `ratelimit` binding only supports 10s/60s periods
 *    fixed at deploy time, counts per-colo, and returns only `success` —
 *    it cannot express a 15-minute login lockout or report `remaining`.
 *  - Durable Objects give exact counts but need a custom class exported
 *    from the worker entry, which OpenNext generates — avoidable friction.
 *  - KV supports arbitrary windows, is global-ish (eventually consistent),
 *    and needs zero changes to the generated worker.
 *
 * Accuracy caveats (fine for abuse throttling, not for billing):
 *  - read-modify-write is not atomic: a tight burst can undercount.
 *  - KV is eventually consistent across colos (~60s), so a distributed
 *    attacker may squeeze out more attempts than `max` before the
 *    counter converges. Single-IP keys almost always hit one colo.
 *
 * Fail-open: if the binding is missing (plain `next dev` without the
 * wrangler platform) or KV errors, requests are allowed and a warning
 * is logged — the storefront must never go down because KV hiccuped.
 */

export interface RateLimitOptions {
  /** Maximum number of hits allowed inside one window. */
  max: number;
  /** Window length in seconds. */
  windowSec: number;
}

export interface RateLimitResult {
  allowed: boolean;
  /** Hits left in the current window (0 when blocked). */
  remaining: number;
  /** Unix seconds when the current window resets. */
  resetAt: number;
}

function getKv(): KVNamespace | null {
  try {
    const { env } = getCloudflareContext();
    return (env as CloudflareEnv).RATE_LIMIT ?? null;
  } catch {
    return null;
  }
}

function bucketKey(key: string, windowSec: number, now: number): { kvKey: string; resetAt: number } {
  const bucket = Math.floor(now / windowSec);
  return { kvKey: `rl:${key}:${windowSec}:${bucket}`, resetAt: (bucket + 1) * windowSec };
}

async function readCount(kv: KVNamespace, kvKey: string): Promise<number> {
  const raw = await kv.get(kvKey);
  const n = raw === null ? 0 : Number(raw);
  return Number.isFinite(n) && n >= 0 ? n : 0;
}

/**
 * Consume one hit for `key`. Returns whether the hit is allowed and how
 * many remain in the current window.
 */
export async function limit(key: string, opts: RateLimitOptions): Promise<RateLimitResult> {
  const now = Math.floor(Date.now() / 1000);
  const { kvKey, resetAt } = bucketKey(key, opts.windowSec, now);
  const kv = getKv();
  if (!kv) {
    logWarn("rate_limit_unavailable", { key, reason: "RATE_LIMIT binding missing" });
    return { allowed: true, remaining: opts.max, resetAt };
  }
  try {
    const count = await readCount(kv, kvKey);
    if (count >= opts.max) return { allowed: false, remaining: 0, resetAt };
    await kv.put(kvKey, String(count + 1), {
      // KV enforces a 60s minimum TTL; pad past the window edge so the
      // bucket outlives its own window but never leaks counts into the next.
      expirationTtl: Math.max(opts.windowSec + 60, 60),
    });
    return { allowed: true, remaining: opts.max - count - 1, resetAt };
  } catch (err) {
    logWarn("rate_limit_unavailable", { key, reason: String(err) });
    return { allowed: true, remaining: opts.max, resetAt };
  }
}

/**
 * Read-only check: is `key` currently blocked? Consumes nothing.
 * Use before doing expensive/verifiable work (e.g. the login lockout
 * check runs before password verification).
 */
export async function peek(key: string, opts: RateLimitOptions): Promise<RateLimitResult> {
  const now = Math.floor(Date.now() / 1000);
  const { kvKey, resetAt } = bucketKey(key, opts.windowSec, now);
  const kv = getKv();
  if (!kv) return { allowed: true, remaining: opts.max, resetAt };
  try {
    const count = await readCount(kv, kvKey);
    return { allowed: count < opts.max, remaining: Math.max(opts.max - count, 0), resetAt };
  } catch (err) {
    logWarn("rate_limit_unavailable", { key, reason: String(err) });
    return { allowed: true, remaining: opts.max, resetAt };
  }
}

/** Clear the current window for `key` (e.g. after a successful login). */
export async function reset(key: string, opts: RateLimitOptions): Promise<void> {
  const now = Math.floor(Date.now() / 1000);
  const { kvKey } = bucketKey(key, opts.windowSec, now);
  const kv = getKv();
  if (!kv) return;
  try {
    await kv.delete(kvKey);
  } catch (err) {
    logWarn("rate_limit_unavailable", { key, reason: String(err) });
  }
}
