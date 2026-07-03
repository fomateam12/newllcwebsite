import { getDb, schema } from "@/lib/db";

export const dynamic = "force-dynamic";

export default async function Home() {
  let categories: (typeof schema.categories.$inferSelect)[] = [];
  let error: string | null = null;

  try {
    const db = getDb();
    categories = await db
      .select()
      .from(schema.categories)
      .orderBy(schema.categories.sortOrder);
  } catch (e) {
    error = e instanceof Error ? e.message : String(e);
  }

  return (
    <main className="mx-auto max-w-2xl p-8">
      <h1 className="mb-2 text-2xl font-bold">FomaFamily v2 — D1 smoke test</h1>
      <p className="mb-6 text-sm text-gray-500">
        Lists rows from the <code>categories</code> table to verify the D1
        binding works.
      </p>

      {error ? (
        <div className="rounded border border-red-300 bg-red-50 p-4 text-red-800">
          <p className="font-semibold">D1 query failed</p>
          <p className="mt-1 text-sm">{error}</p>
        </div>
      ) : categories.length === 0 ? (
        <div className="rounded border border-green-300 bg-green-50 p-4 text-green-800">
          <p className="font-semibold">Connected — no categories yet</p>
          <p className="mt-1 text-sm">
            The query succeeded but the table is empty. Data migration comes in
            a later step.
          </p>
        </div>
      ) : (
        <ul className="divide-y rounded border">
          {categories.map((c) => (
            <li key={c.id} className="flex items-center justify-between p-3">
              <span>{c.name}</span>
              <code className="text-sm text-gray-500">/{c.slug}</code>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}
