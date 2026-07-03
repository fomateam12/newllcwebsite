/**
 * OpenCart (MySQL dump) → D1 (SQLite) data migration.
 *
 * Reads site-data-only.sql (tables extracted from the live
 * `fomafami_testvt` OpenCart database) and produces:
 *   - migrations/0002_seed_data.sql  (SQLite INSERTs for categories,
 *     products, product_images — original OpenCart IDs preserved)
 *   - scripts/image-paths.txt        (unique image paths for the
 *     upcoming R2 upload step; r2_key keeps the original path for now)
 *
 * Run: npx tsx scripts/migrate-data.ts
 */
import * as fs from "node:fs";
import * as path from "node:path";

const ROOT = path.join(__dirname, "..");
const INPUT = path.join(ROOT, "site-data-only.sql");
const OUT_SQL = path.join(ROOT, "migrations", "0002_seed_data.sql");
const OUT_PATHS = path.join(__dirname, "image-paths.txt");

type Row = Record<string, string | number | null>;

// ---------- MySQL INSERT parsing ----------

/** Split the dump into full statements (a statement ends with ";" at EOL). */
function* statements(sql: string): Generator<string> {
  let buf: string[] = [];
  for (const line of sql.split("\n")) {
    buf.push(line);
    if (line.trimEnd().endsWith(";")) {
      yield buf.join("\n");
      buf = [];
    }
  }
}

/** Parse one VALUES tuple list with MySQL quoting/escapes. */
function parseTuples(body: string): (string | number | null)[][] {
  const tuples: (string | number | null)[][] = [];
  let i = 0;
  const n = body.length;
  while (i < n) {
    while (i < n && body[i] !== "(") i++;
    if (i >= n) break;
    i++; // consume "("
    const tuple: (string | number | null)[] = [];
    let field = "";
    let inStr = false;
    let done = false;
    while (i < n && !done) {
      const c = body[i];
      if (inStr) {
        if (c === "\\" && i + 1 < n) {
          const e = body[i + 1];
          field +=
            e === "n" ? "\n"
            : e === "r" ? "\r"
            : e === "t" ? "\t"
            : e === "0" ? "\0"
            : e; // \' \" \\ and anything else → literal char
          i += 2;
          continue;
        }
        if (c === "'") {
          if (body[i + 1] === "'") { // doubled quote inside string
            field += "'";
            i += 2;
            continue;
          }
          inStr = false;
          tuple.push(field);
          field = "";
          i++;
          continue;
        }
        field += c;
        i++;
        continue;
      }
      if (c === "'") {
        inStr = true;
        field = ""; // discard whitespace accumulated before the quote
        i++;
      } else if (c === "," ) {
        if (field.trim() !== "") tuple.push(rawValue(field));
        field = "";
        i++;
      } else if (c === ")") {
        if (field.trim() !== "") tuple.push(rawValue(field));
        tuples.push(tuple);
        done = true;
        i++;
      } else {
        field += c;
        i++;
      }
    }
  }
  return tuples;
}

function rawValue(s: string): string | number | null {
  const t = s.trim();
  if (t.toUpperCase() === "NULL") return null;
  const num = Number(t);
  return Number.isNaN(num) ? t : num;
}

/** Collect rows (as name→value objects) for each target table. */
function loadRows(sql: string): Map<string, Row[]> {
  const tables = new Map<string, Row[]>();
  const header = /^INSERT INTO `([a-z_]+)` \(([^)]+)\) VALUES/;
  for (const stmt of statements(sql)) {
    const m = stmt.match(header);
    if (!m) continue;
    const table = m[1];
    const cols = m[2].split(",").map((c) => c.trim().replace(/`/g, ""));
    const body = stmt.slice(stmt.indexOf("VALUES") + 6);
    const rows = tables.get(table) ?? [];
    for (const tuple of parseTuples(body)) {
      if (tuple.length !== cols.length) {
        throw new Error(
          `${table}: tuple has ${tuple.length} fields, expected ${cols.length}: ${JSON.stringify(tuple).slice(0, 200)}`,
        );
      }
      const row: Row = {};
      cols.forEach((c, idx) => (row[c] = tuple[idx]));
      rows.push(row);
    }
    tables.set(table, rows);
  }
  return tables;
}

// ---------- mapping helpers ----------

function slugify(name: string): string {
  return name
    .normalize("NFKD")
    .replace(/[̀-ͯ]/g, "") // strip diacritics
    .toLowerCase()
    .replace(/&[a-z]+;|&#\d+;/g, " ") // html entities
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 80) || "item";
}

function sqlStr(v: string | number | null): string {
  if (v === null || v === undefined) return "NULL";
  if (typeof v === "number") return String(v);
  return `'${v.replace(/'/g, "''")}'`;
}

// ---------- main ----------

const dump = fs.readFileSync(INPUT, "utf8");
const t = loadRows(dump);

const ocCategories = t.get("oc_category") ?? [];
const ocCatDesc = t.get("oc_category_description") ?? [];
const ocProducts = t.get("oc_product") ?? [];
const ocProdDesc = t.get("oc_product_description") ?? [];
const ocProdImage = t.get("oc_product_image") ?? [];
const ocProdToCat = t.get("oc_product_to_category") ?? [];

// language_id=1 descriptions, keyed by id
const catName = new Map<number, Row>();
for (const r of ocCatDesc)
  if (r.language_id === 1) catName.set(r.category_id as number, r);
const prodDesc = new Map<number, Row>();
for (const r of ocProdDesc)
  if (r.language_id === 1) prodDesc.set(r.product_id as number, r);

// product → primary category (lowest category_id among links)
const prodCat = new Map<number, number>();
for (const r of ocProdToCat) {
  const pid = r.product_id as number;
  const cid = r.category_id as number;
  if (!prodCat.has(pid) || cid < prodCat.get(pid)!) prodCat.set(pid, cid);
}

const lines: string[] = [
  "-- 0002_seed_data.sql — data migrated from OpenCart (fomafami_testvt)",
  "-- Generated by scripts/migrate-data.ts — do not edit by hand.",
  "PRAGMA defer_foreign_keys = on;",
  "",
];

// --- categories (preserve OpenCart IDs) ---
const catIds = new Set<number>();
const usedCatSlugs = new Set<string>();
let catCount = 0;
for (const c of ocCategories) {
  const id = c.category_id as number;
  const desc = catName.get(id);
  if (!desc) continue; // no name in default language → unusable
  catIds.add(id);
}
// Topological order: parents before children, so immediate FK checks pass
// (remote D1 does not reliably honor defer_foreign_keys across a file import).
const byId = new Map(ocCategories.map((c) => [c.category_id as number, c]));
const emitted = new Set<number>();
const sortedCats: Row[] = [];
function emitCat(id: number) {
  if (emitted.has(id) || !catIds.has(id)) return;
  emitted.add(id); // mark before recursing (cycle guard)
  const c = byId.get(id)!;
  const parent = c.parent_id as number;
  if (parent && catIds.has(parent)) emitCat(parent);
  sortedCats.push(c);
}
for (const c of ocCategories) emitCat(c.category_id as number);

for (const c of sortedCats) {
  const id = c.category_id as number;
  const desc = catName.get(id)!;
  const name = String(desc.name).trim();
  let slug = slugify(name);
  if (usedCatSlugs.has(slug)) slug = `${slug}-${id}`;
  usedCatSlugs.add(slug);
  const parent =
    c.parent_id && catIds.has(c.parent_id as number)
      ? (c.parent_id as number)
      : null;
  const image = String(c.image ?? "").trim() || null;
  lines.push(
    `INSERT INTO categories (id, name, slug, parent_id, sort_order, image_url, created_at) VALUES (` +
      `${id}, ${sqlStr(name)}, ${sqlStr(slug)}, ${parent ?? "NULL"}, ${(c.sort_order as number) ?? 0}, ${sqlStr(image)}, ${sqlStr(String(c.date_added ?? "") || null)});`,
  );
  catCount++;
}
lines.push("");

// --- products (preserve OpenCart IDs) ---
const usedProdSlugs = new Set<string>();
const imagePaths = new Set<string>();
let prodCount = 0;
let noDesc = 0;
const prodIds = new Set<number>();
for (const p of ocProducts) {
  const id = p.product_id as number;
  const desc = prodDesc.get(id);
  const name = desc ? String(desc.name).trim() : String(p.model ?? "").trim();
  if (!name) { noDesc++; continue; }
  if (!desc) noDesc++;
  prodIds.add(id);
  let slug = slugify(name);
  if (usedProdSlugs.has(slug)) slug = `${slug}-${id}`;
  usedProdSlugs.add(slug);
  const description = desc ? String(desc.description ?? "") : "";
  const cat = prodCat.get(id);
  const categoryId = cat !== undefined && catIds.has(cat) ? cat : null;
  const sku = String(p.sku ?? "").trim() || String(p.model ?? "").trim() || null;
  const status = p.status === 1 ? "active" : "draft";
  const created = String(p.date_added ?? "") || null;
  const updated = String(p.date_modified ?? "") || created;
  lines.push(
    `INSERT INTO products (id, name, slug, description, base_price, category_id, sku, status, created_at, updated_at) VALUES (` +
      `${id}, ${sqlStr(name)}, ${sqlStr(slug)}, ${sqlStr(description)}, ${Number(p.price ?? 0)}, ${categoryId ?? "NULL"}, ${sqlStr(sku)}, ${sqlStr(status)}, ${sqlStr(created)}, ${sqlStr(updated)});`,
  );
  prodCount++;
}
lines.push("");

// --- product images: main image (is_primary) + gallery ---
let imgCount = 0;
for (const p of ocProducts) {
  const id = p.product_id as number;
  if (!prodIds.has(id)) continue;
  const main = String(p.image ?? "").trim();
  if (main) {
    const desc = prodDesc.get(id);
    const alt = desc ? String(desc.name).trim() : null;
    lines.push(
      `INSERT INTO product_images (product_id, r2_key, alt_text, sort_order, is_primary) VALUES (` +
        `${id}, ${sqlStr(main)}, ${sqlStr(alt)}, 0, 1);`,
    );
    imagePaths.add(main);
    imgCount++;
  }
}
for (const im of ocProdImage) {
  const pid = im.product_id as number;
  if (!prodIds.has(pid)) continue;
  const key = String(im.image ?? "").trim();
  if (!key) continue;
  lines.push(
    `INSERT INTO product_images (product_id, r2_key, alt_text, sort_order, is_primary) VALUES (` +
      `${pid}, ${sqlStr(key)}, NULL, ${((im.sort_order as number) ?? 0) + 1}, 0);`,
  );
  imagePaths.add(key);
  imgCount++;
}

fs.writeFileSync(OUT_SQL, lines.join("\n") + "\n");
fs.writeFileSync(OUT_PATHS, [...imagePaths].sort().join("\n") + "\n");

console.log(`source rows: products=${ocProducts.length} prod_desc(lang1)=${prodDesc.size} categories=${ocCategories.length} cat_desc(lang1)=${catName.size} images=${ocProdImage.length} prod_to_cat=${ocProdToCat.length}`);
console.log(`written: categories=${catCount} products=${prodCount} (name fallback/skips: ${noDesc}) product_images=${imgCount}`);
console.log(`unique image paths: ${imagePaths.size} → ${OUT_PATHS}`);
console.log(`sql → ${OUT_SQL}`);
