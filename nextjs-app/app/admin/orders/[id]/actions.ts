"use server";

import { eq } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { getDb, schema } from "@/lib/db";
import { isOrderStatus } from "@/lib/order-status";

/**
 * Server action: update an order's status from the admin detail page.
 * Route-level auth is enforced by middleware.ts (the action lives under
 * /admin, so its POST endpoint is behind the same Basic Auth wall).
 */
export async function updateOrderStatus(formData: FormData) {
  const id = Number(formData.get("orderId"));
  const status = String(formData.get("status") ?? "");

  if (!Number.isInteger(id) || id <= 0) {
    throw new Error("Invalid order id");
  }
  if (!isOrderStatus(status)) {
    throw new Error(`Invalid status: ${status}`);
  }

  const db = getDb();
  await db
    .update(schema.orders)
    .set({ status })
    .where(eq(schema.orders.id, id));

  revalidatePath(`/admin/orders/${id}`);
  revalidatePath("/admin/orders");
  revalidatePath("/admin");
}
