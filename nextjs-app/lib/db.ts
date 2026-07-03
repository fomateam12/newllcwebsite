import { getCloudflareContext } from "@opennextjs/cloudflare";
import { drizzle } from "drizzle-orm/d1";
import * as schema from "./schema";

export * as schema from "./schema";

/**
 * Type-safe Drizzle client bound to the D1 database.
 * Must be called per-request — bindings are only available
 * inside a request context.
 */
export function getDb() {
  const { env } = getCloudflareContext();
  return drizzle(env.DB, { schema });
}

export type Db = ReturnType<typeof getDb>;
