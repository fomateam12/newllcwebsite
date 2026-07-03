import { asc, eq } from "drizzle-orm";
import Link from "next/link";
import { notFound } from "next/navigation";
import { getDb, schema } from "@/lib/db";
import { formatPrice } from "@/lib/catalog";
import { ORDER_STATUSES, statusPillClasses } from "@/lib/order-status";
import { updateOrderStatus } from "./actions";

export const dynamic = "force-dynamic";

type Props = { params: Promise<{ id: string }> };

/** "postalCode" / "postal_code" / "postal-code" → "Postal code". */
function humanizeKey(key: string): string {
  const words = key
    .replace(/([a-z0-9])([A-Z])/g, "$1 $2")
    .replace(/[_-]+/g, " ")
    .trim()
    .toLowerCase();
  return words.charAt(0).toUpperCase() + words.slice(1);
}

/** Parse a JSON column defensively; returns null when absent/invalid. */
function parseJson(raw: string | null): unknown {
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function valueToText(value: unknown): string {
  if (value === null || value === undefined || value === "") return "—";
  if (Array.isArray(value)) return value.map(valueToText).join(", ");
  if (typeof value === "object") {
    return Object.entries(value as Record<string, unknown>)
      .map(([k, v]) => `${humanizeKey(k)}: ${valueToText(v)}`)
      .join(" · ");
  }
  return String(value);
}

/** Generic pretty key/value list for parsed JSON blobs. */
function KeyValueList({ data }: { data: Record<string, unknown> }) {
  const entries = Object.entries(data);
  if (entries.length === 0) return <p className="text-sm text-ink/50">—</p>;
  return (
    <dl className="space-y-1.5 text-sm">
      {entries.map(([key, value]) => (
        <div key={key} className="flex gap-2">
          <dt className="min-w-32 shrink-0 font-mono text-xs uppercase tracking-wide text-ink/50 pt-0.5">
            {humanizeKey(key)}
          </dt>
          <dd className="text-ink">{valueToText(value)}</dd>
        </div>
      ))}
    </dl>
  );
}

export default async function AdminOrderDetailPage({ params }: Props) {
  const { id: idParam } = await params;
  const id = Number(idParam);
  if (!Number.isInteger(id) || id <= 0) notFound();

  const db = getDb();
  const { orders, orderItems, customers, products } = schema;

  const [orderRows, items] = await Promise.all([
    db
      .select({
        id: orders.id,
        status: orders.status,
        subtotal: orders.subtotal,
        total: orders.total,
        stripePaymentIntentId: orders.stripePaymentIntentId,
        shippingAddressJson: orders.shippingAddressJson,
        createdAt: orders.createdAt,
        customerEmail: customers.email,
        customerName: customers.name,
        customerPhone: customers.phone,
      })
      .from(orders)
      .leftJoin(customers, eq(orders.customerId, customers.id))
      .where(eq(orders.id, id))
      .limit(1),
    db
      .select({
        id: orderItems.id,
        quantity: orderItems.quantity,
        unitPrice: orderItems.unitPrice,
        customizationDataJson: orderItems.customizationDataJson,
        productName: products.name,
        productSlug: products.slug,
      })
      .from(orderItems)
      .leftJoin(products, eq(orderItems.productId, products.id))
      .where(eq(orderItems.orderId, id))
      .orderBy(asc(orderItems.id)),
  ]);

  const order = orderRows[0];
  if (!order) notFound();

  const shippingAddress = parseJson(order.shippingAddressJson);
  const itemsSum = items.reduce((sum, i) => sum + i.quantity * i.unitPrice, 0);

  return (
    <div>
      <nav className="mb-4 font-mono text-xs text-ink/50" aria-label="Breadcrumb">
        <Link href="/admin" className="hover:text-pine">Admin</Link>
        {" / "}
        <Link href="/admin/orders" className="hover:text-pine">Orders</Link>
        {" / "}#{order.id}
      </nav>

      <div className="mb-6 flex flex-wrap items-center gap-3">
        <h2 className="font-display text-2xl text-ink">Order #{order.id}</h2>
        <span
          className={`inline-block rounded-sm border border-dashed px-2 py-0.5 font-mono text-sm ${statusPillClasses(order.status)}`}
        >
          {order.status}
        </span>
        <span className="font-mono text-xs text-ink/50">{order.createdAt}</span>
      </div>

      <div className="grid gap-4 lg:grid-cols-3">
        {/* Customer */}
        <section className="rounded-md border border-line bg-white p-4">
          <h3 className="mb-3 font-display text-base text-ink">Customer</h3>
          <KeyValueList
            data={{
              name: order.customerName,
              email: order.customerEmail,
              phone: order.customerPhone,
            }}
          />
        </section>

        {/* Shipping address */}
        <section className="rounded-md border border-line bg-white p-4">
          <h3 className="mb-3 font-display text-base text-ink">Shipping address</h3>
          {shippingAddress && typeof shippingAddress === "object" ? (
            <KeyValueList data={shippingAddress as Record<string, unknown>} />
          ) : (
            <p className="text-sm text-ink/50">
              {order.shippingAddressJson
                ? "Could not parse the stored address."
                : "No shipping address on file."}
            </p>
          )}
        </section>

        {/* Update status */}
        <section className="rounded-md border border-line bg-white p-4">
          <h3 className="mb-3 font-display text-base text-ink">Update status</h3>
          <form action={updateOrderStatus} className="flex flex-wrap items-center gap-2">
            <input type="hidden" name="orderId" value={order.id} />
            <label htmlFor="status" className="sr-only">
              Order status
            </label>
            <select
              id="status"
              name="status"
              defaultValue={order.status}
              className="rounded-sm border border-line bg-paper px-2 py-1.5 font-mono text-sm text-ink"
            >
              {ORDER_STATUSES.map((s) => (
                <option key={s} value={s}>
                  {s}
                </option>
              ))}
            </select>
            <button
              type="submit"
              className="rounded-sm border border-pine bg-pine px-3 py-1.5 font-mono text-sm text-white hover:bg-pine-deep"
            >
              Save
            </button>
          </form>
          {order.stripePaymentIntentId && (
            <p className="mt-3 break-all font-mono text-xs text-ink/50">
              Stripe PI: {order.stripePaymentIntentId}
            </p>
          )}
        </section>
      </div>

      {/* Line items */}
      <section className="mt-6">
        <h3 className="mb-3 font-display text-lg text-ink">Items</h3>
        {items.length === 0 ? (
          <p className="rounded-md border border-dashed border-line p-6 text-sm text-ink/50">
            This order has no line items.
          </p>
        ) : (
          <div className="overflow-x-auto rounded-md border border-line bg-white">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-line font-mono text-xs uppercase tracking-wide text-ink/50">
                <tr>
                  <th className="px-4 py-2.5">Product</th>
                  <th className="px-4 py-2.5">Customization</th>
                  <th className="px-4 py-2.5 text-right">Qty</th>
                  <th className="px-4 py-2.5 text-right">Unit</th>
                  <th className="px-4 py-2.5 text-right">Line total</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item) => {
                  const customization = parseJson(item.customizationDataJson);
                  return (
                    <tr key={item.id} className="border-b border-line/60 align-top last:border-0">
                      <td className="px-4 py-3">
                        {item.productSlug ? (
                          <Link
                            href={`/urun/${item.productSlug}`}
                            className="text-pine hover:underline"
                          >
                            {item.productName}
                          </Link>
                        ) : (
                          <span className="text-ink/50">
                            {item.productName ?? "Deleted product"}
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        {customization && typeof customization === "object" ? (
                          <KeyValueList
                            data={customization as Record<string, unknown>}
                          />
                        ) : (
                          <span className="text-ink/40">
                            {item.customizationDataJson ? "Unparseable" : "—"}
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {item.quantity}
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatPrice(item.unitPrice)}
                      </td>
                      <td className="px-4 py-3 text-right font-mono">
                        {formatPrice(item.quantity * item.unitPrice)}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
              <tfoot className="border-t border-line font-mono text-sm">
                <tr>
                  <td colSpan={4} className="px-4 py-2 text-right text-ink/50">
                    Items sum
                  </td>
                  <td className="px-4 py-2 text-right">{formatPrice(itemsSum)}</td>
                </tr>
                <tr>
                  <td colSpan={4} className="px-4 py-2 text-right text-ink/50">
                    Subtotal
                  </td>
                  <td className="px-4 py-2 text-right">{formatPrice(order.subtotal)}</td>
                </tr>
                <tr>
                  <td colSpan={4} className="px-4 py-2 text-right text-ink/50">
                    Total
                  </td>
                  <td className="px-4 py-2 text-right font-semibold">
                    {formatPrice(order.total)}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        )}
      </section>
    </div>
  );
}
