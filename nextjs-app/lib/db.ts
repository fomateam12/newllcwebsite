import { getRequestContext } from "@cloudflare/next-on-pages";
import { drizzle } from "drizzle-orm/d1";
import * as schema from "./schema";

export * as schema from "./schema";

/**
 * Type-safe Drizzle client bound to the D1 database.
 * Must be called per-request (edge runtime) — bindings are only
 * available inside a request context.
 */
export function getDb() {
  const { env } = getRequestContext();
  return drizzle(env.DB, { schema });
}

export type Db = ReturnType<typeof getDb>;
