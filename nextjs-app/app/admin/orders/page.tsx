import { desc, eq } from "drizzle-orm";
import Link from "next/link";
import { getDb, schema } from "@/lib/db";
import { formatPrice } from "@/lib/catalog";
import { statusPillClasses } from "@/lib/order-status";

export const dynamic = "force-dynamic";

export default async function AdminOrdersPage() {
  const db = getDb();
  const { orders, customers } = schema;

  const rows = await db
    .select({
      id: orders.id,
      status: orders.status,
      total: orders.total,
      createdAt: orders.createdAt,
      customerEmail: customers.email,
    })
    .from(orders)
    .leftJoin(customers, eq(orders.customerId, customers.id))
    .orderBy(desc(orders.createdAt), desc(orders.id));

  return (
    <div>
      <h2 className="mb-3 font-display text-lg text-ink">
        Orders <span className="font-mono text-sm text-ink/50">({rows.length})</span>
      </h2>
      {rows.length === 0 ? (
        <p className="rounded-md border border-dashed border-line p-6 text-sm text-ink/50">
          No orders yet.
        </p>
      ) : (
        <div className="overflow-x-auto rounded-md border border-line bg-white">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-line font-mono text-xs uppercase tracking-wide text-ink/50">
              <tr>
                <th className="px-4 py-2.5">Order</th>
                <th className="px-4 py-2.5">Date</th>
                <th className="px-4 py-2.5">Customer</th>
                <th className="px-4 py-2.5">Status</th>
                <th className="px-4 py-2.5 text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((o) => (
                <tr key={o.id} className="border-b border-line/60 last:border-0">
                  <td className="px-4 py-2.5 font-mono">
                    <Link
                      href={`/admin/orders/${o.id}`}
                      className="text-pine hover:underline"
                    >
                      #{o.id}
                    </Link>
                  </td>
                  <td className="px-4 py-2.5 font-mono text-xs text-ink/60">
                    {o.createdAt}
                  </td>
                  <td className="px-4 py-2.5">{o.customerEmail ?? "—"}</td>
                  <td className="px-4 py-2.5">
                    <span
                      className={`inline-block rounded-sm border border-dashed px-1.5 py-0.5 font-mono text-xs ${statusPillClasses(o.status)}`}
                    >
                      {o.status}
                    </span>
                  </td>
                  <td className="px-4 py-2.5 text-right font-mono">
                    {formatPrice(o.total)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
