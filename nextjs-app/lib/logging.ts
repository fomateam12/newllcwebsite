/**
 * Structured logging — one JSON object per line, so Workers Observability
 * (and any log drain) can index on `event` and filter on data fields.
 *
 * Canonical events emitted by this codebase:
 *   - order_created            { orderId, sessionId, itemCount, total }
 *   - stripe_webhook_received  { eventType, eventId }
 *   - stripe_webhook_failed    { reason, eventType?, eventId? }
 *   - admin_login_success      { ip }
 *   - admin_login_failure      { ip, reason }
 *   - rate_limit_triggered     { key, max, windowSec, path }
 *
 * Never put secrets, passwords, tokens or full card/PII payloads in `data`.
 */

type LogData = Record<string, unknown>;

function emit(level: "info" | "warn" | "error", event: string, data: LogData) {
  const line = JSON.stringify({ ts: new Date().toISOString(), level, event, ...data });
  if (level === "error") console.error(line);
  else if (level === "warn") console.warn(line);
  else console.log(line);
}

/** Log a structured event at info level. */
export function log(event: string, data: LogData = {}): void {
  emit("info", event, data);
}

/** Log a structured event at warn level (suspicious but handled). */
export function logWarn(event: string, data: LogData = {}): void {
  emit("warn", event, data);
}

/** Log a structured event at error level (needs attention). */
export function logError(event: string, data: LogData = {}): void {
  emit("error", event, data);
}
