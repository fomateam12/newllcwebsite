import { desc, eq, sql } from "drizzle-orm";
import Link from "next/link";
import { getDb, schema } from "@/lib/db";
import { formatPrice } from "@/lib/catalog";
import { ORDER_STATUSES, statusPillClasses } from "@/lib/order-status";

export const dynamic = "force-dynamic";

export default async function AdminDashboardPage() {
  const db = getDb();
  const { orders, customers } = schema;

  const [totalsRows, byStatusRows, recent] = await Promise.all([
    db
      .select({
        count: sql<number>`count(*)`,
        revenue: sql<number>`coalesce(sum(${orders.total}), 0)`,
      })
      .from(orders),
    db
      .select({ status: orders.status, count: sql<number>`count(*)` })
      .from(orders)
      .groupBy(orders.status),
    db
      .select({
        id: orders.id,
        status: orders.status,
        total: orders.total,
        createdAt: orders.createdAt,
        customerEmail: customers.email,
      })
      .from(orders)
      .leftJoin(customers, eq(orders.customerId, customers.id))
      .orderBy(desc(orders.createdAt), desc(orders.id))
      .limit(5),
  ]);

  const totals = totalsRows[0] ?? { count: 0, revenue: 0 };
  const countByStatus = new Map(byStatusRows.map((r) => [r.status, r.count]));
  // Keep known statuses in workflow order; append any unexpected ones.
  const statusList = [
    ...ORDER_STATUSES.map((s) => ({ status: s, count: countByStatus.get(s) ?? 0 })),
    ...byStatusRows.filter(
      (r) => !(ORDER_STATUSES as readonly string[]).includes(r.status),
    ),
  ];

  return (
    <div>
      <div className="grid gap-4 sm:grid-cols-3">
        <div className="rounded-md border border-line bg-white p-4">
          <p className="font-mono text-xs uppercase tracking-wide text-ink/50">
            Total orders
          </p>
          <p className="mt-1 font-display text-3xl text-ink">{totals.count}</p>
        </div>
        <div className="rounded-md border border-line bg-white p-4">
          <p className="font-mono text-xs uppercase tracking-wide text-ink/50">
            Revenue (all orders)
          </p>
          <p className="mt-1 font-display text-3xl text-ink">
            {formatPrice(totals.revenue)}
          </p>
        </div>
        <div className="rounded-md border border-line bg-white p-4">
          <p className="font-mono text-xs uppercase tracking-wide text-ink/50">
            By status
          </p>
          <ul className="mt-2 space-y-1">
            {statusList.map(({ status, count }) => (
              <li
                key={status}
                className="flex items-center justify-between text-sm"
              >
                <span
                  className={`inline-block rounded-sm border border-dashed px-1.5 py-0.5 font-mono text-xs ${statusPillClasses(status)}`}
                >
                  {status}
                </span>
                <span className="font-mono text-ink/70">{count}</span>
              </li>
            ))}
          </ul>
        </div>
      </div>

      <div className="mt-8">
        <div className="mb-3 flex items-baseline justify-between">
          <h2 className="font-display text-lg text-ink">Recent orders</h2>
          <Link
            href="/admin/orders"
            className="font-mono text-xs text-pine hover:underline"
          >
            View all →
          </Link>
        </div>
        {recent.length === 0 ? (
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
                {recent.map((o) => (
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
    </div>
  );
}
