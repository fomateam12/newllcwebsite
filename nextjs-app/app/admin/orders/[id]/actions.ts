"use server";

import { eq } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { getDb, schema } from "@/lib/db";
import { orderStatusUpdateSchema } from "@/lib/security-schemas";

/**
 * Server action: update an order's status from the admin detail page.
 * Route-level auth is enforced by middleware.ts (the action lives under
 * /admin, so its POST endpoint is behind the same session-cookie wall).
 * Input is validated with zod — the status enum comes from
 * lib/order-status.ts via lib/security-schemas.ts.
 */
export async function updateOrderStatus(formData: FormData) {
  const parsed = orderStatusUpdateSchema.safeParse({
    orderId: formData.get("orderId"),
    status: formData.get("status"),
  });
  if (!parsed.success) {
    throw new Error(
      `Invalid status update: ${parsed.error.issues.map((i) => i.message).join("; ")}`,
    );
  }
  const { orderId, status } = parsed.data;

  const db = getDb();
  await db
    .update(schema.orders)
    .set({ status })
    .where(eq(schema.orders.id, orderId));

  revalidatePath(`/admin/orders/${orderId}`);
  revalidatePath("/admin/orders");
  revalidatePath("/admin");
}
