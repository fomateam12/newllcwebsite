-- DRAFT — not applied yet.
-- Backing table for the /contact form (app/(content)/contact/actions.ts).
-- Apply to LOCAL D1 for testing:
--   npx wrangler d1 execute fomawebsitedatabase --file=migrations/drafts/contact_messages.sql --local
-- Remote apply is an operator decision — promote this file to a numbered
-- migration (e.g. 0004_contact_messages.sql) before running it remotely.

CREATE TABLE IF NOT EXISTS contact_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  subject TEXT NOT NULL,
  message TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  status TEXT NOT NULL DEFAULT 'new'
);

CREATE INDEX IF NOT EXISTS idx_contact_messages_status
  ON contact_messages (status);
