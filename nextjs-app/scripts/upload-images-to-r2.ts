/**
 * Upload the OpenCart product images listed in scripts/image-paths.txt
 * from ~/Downloads/image/ to the fomawebsitev2 R2 bucket, preserving the
 * original path as the object key (matches product_images.r2_key in D1).
 *
 * Uses `wrangler r2 object put --remote` per file (OAuth already set up),
 * with bounded concurrency and up to 2 retry passes for failures.
 *
 * Run: npx tsx scripts/upload-images-to-r2.ts
 */
import { spawn } from "node:child_process";
import * as fs from "node:fs";
import * as os from "node:os";
import * as path from "node:path";

const BUCKET = "fomawebsitev2";
const SRC_BASE = path.join(os.homedir(), "Downloads", "image");
const LIST = path.join(__dirname, "image-paths.txt");
const FAILED_OUT = path.join(__dirname, "upload-failures.txt");
const CONCURRENCY = 10;
const MAX_RETRY_PASSES = 2;

function put(key: string): Promise<{ key: string; ok: boolean; err?: string }> {
  const file = path.join(SRC_BASE, key);
  return new Promise((resolve) => {
    const child = spawn(
      "npx",
      ["wrangler", "r2", "object", "put", `${BUCKET}/${key}`,
       "--file", file, "--content-type", "image/jpeg", "--remote"],
      { cwd: path.join(__dirname, ".."), stdio: ["ignore", "ignore", "pipe"] },
    );
    let err = "";
    child.stderr.on("data", (d) => (err += d.toString()));
    child.on("close", (code) =>
      resolve({ key, ok: code === 0, err: err.slice(-300) }),
    );
    child.on("error", (e) => resolve({ key, ok: false, err: String(e) }));
  });
}

async function runPass(keys: string[], passLabel: string): Promise<string[]> {
  const failures: string[] = [];
  let done = 0;
  let cursor = 0;
  async function worker() {
    while (cursor < keys.length) {
      const key = keys[cursor++];
      const res = await put(key);
      done++;
      if (!res.ok) {
        failures.push(key);
        console.error(`FAIL ${key}: ${res.err?.split("\n").slice(-2).join(" ")}`);
      }
      if (done % 50 === 0 || done === keys.length) {
        console.log(`[${passLabel}] ${done}/${keys.length} (fail: ${failures.length})`);
      }
    }
  }
  await Promise.all(Array.from({ length: CONCURRENCY }, worker));
  return failures;
}

async function main() {
  const keys = fs
    .readFileSync(LIST, "utf8")
    .split("\n")
    .map((l) => l.trim())
    .filter(Boolean);

  const missing = keys.filter((k) => !fs.existsSync(path.join(SRC_BASE, k)));
  if (missing.length) {
    console.error(`ABORT: ${missing.length} source files missing, e.g. ${missing.slice(0, 3).join(", ")}`);
    process.exit(1);
  }
  console.log(`${keys.length} files verified on disk; uploading to ${BUCKET} with concurrency ${CONCURRENCY}`);

  let pending = keys;
  let failures: string[] = [];
  for (let pass = 0; pass <= MAX_RETRY_PASSES && pending.length; pass++) {
    const label = pass === 0 ? "upload" : `retry ${pass}`;
    failures = await runPass(pending, label);
    pending = failures;
  }

  if (failures.length) {
    fs.writeFileSync(FAILED_OUT, failures.join("\n") + "\n");
    console.error(`DONE WITH FAILURES: ${failures.length} objects failed after ${MAX_RETRY_PASSES} retries → ${FAILED_OUT}`);
    process.exit(2);
  }
  console.log(`DONE: all ${keys.length} objects uploaded successfully.`);
}

main();
