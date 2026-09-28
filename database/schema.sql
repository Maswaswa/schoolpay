PRAGMA foreign_keys = ON;

-- ============================================================
-- SCHOOL FEES INFORMATION SYSTEM  (SQLite schema)
-- Multi-tenant: every school-owned table carries school_id.
-- ============================================================

-- ---------- Tenant & Access Layer ----------
CREATE TABLE IF NOT EXISTS schools (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS campuses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  name TEXT NOT NULL,
  address TEXT
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER REFERENCES schools(id),          -- NULL => platform-level user
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  phone TEXT,
  password_hash TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  last_login_at TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  code TEXT NOT NULL UNIQUE,
  name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INTEGER NOT NULL REFERENCES users(id),
  role_id INTEGER NOT NULL REFERENCES roles(id),
  PRIMARY KEY (user_id, role_id)
);

-- ---------- Academic Layer ----------
CREATE TABLE IF NOT EXISTS academic_years (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  name TEXT NOT NULL,
  is_current INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS terms (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  academic_year_id INTEGER NOT NULL REFERENCES academic_years(id),
  name TEXT NOT NULL,
  UNIQUE(school_id, name)
);

CREATE TABLE IF NOT EXISTS classes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  campus_id INTEGER REFERENCES campuses(id),
  name TEXT NOT NULL
);

-- ---------- Students Layer ----------
CREATE TABLE IF NOT EXISTS students (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  campus_id INTEGER REFERENCES campuses(id),
  student_id TEXT NOT NULL UNIQUE,          -- permanent public identifier
  reg_form_number TEXT,                     -- distinct from Student ID
  full_name TEXT NOT NULL,
  class_id INTEGER REFERENCES classes(id),
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS guardians (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  name TEXT NOT NULL,
  phone TEXT,
  email TEXT
);

CREATE TABLE IF NOT EXISTS student_guardians (
  student_id INTEGER NOT NULL REFERENCES students(id),
  guardian_id INTEGER NOT NULL REFERENCES guardians(id),
  relationship TEXT,
  PRIMARY KEY (student_id, guardian_id)
);

-- ---------- Fees & Billing Layer ----------
CREATE TABLE IF NOT EXISTS fee_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  code TEXT NOT NULL,
  name TEXT NOT NULL,
  category TEXT DEFAULT 'tuition',
  UNIQUE(school_id, code)
);

CREATE TABLE IF NOT EXISTS fee_structures (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  term_id INTEGER NOT NULL REFERENCES terms(id),
  name TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'draft',
  created_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS fee_structure_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  fee_structure_id INTEGER NOT NULL REFERENCES fee_structures(id),
  fee_item_id INTEGER NOT NULL REFERENCES fee_items(id),
  amount REAL NOT NULL,
  UNIQUE(fee_structure_id, fee_item_id)
);

CREATE TABLE IF NOT EXISTS fee_obligations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  student_id INTEGER NOT NULL REFERENCES students(id),
  term_id INTEGER NOT NULL REFERENCES terms(id),
  invoice_no TEXT UNIQUE,
  total_amount REAL NOT NULL DEFAULT 0,
  paid_amount REAL NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'unpaid',
  created_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS fee_obligation_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  fee_obligation_id INTEGER NOT NULL REFERENCES fee_obligations(id),
  fee_item_id INTEGER NOT NULL REFERENCES fee_items(id),
  amount REAL NOT NULL DEFAULT 0,
  paid_amount REAL NOT NULL DEFAULT 0
);


-- ---------- Payments Layer ----------
CREATE TABLE IF NOT EXISTS payment_intents (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  student_id INTEGER NOT NULL REFERENCES students(id),
  reference TEXT NOT NULL UNIQUE,
  channel TEXT NOT NULL,
  amount REAL NOT NULL,
  status TEXT NOT NULL DEFAULT 'created',
  idempotency_key TEXT,
  created_by INTEGER REFERENCES users(id),
  confirmed_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_intent_idem
  ON payment_intents(school_id, idempotency_key) WHERE idempotency_key IS NOT NULL;

CREATE TABLE IF NOT EXISTS payment_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  payment_intent_id INTEGER NOT NULL REFERENCES payment_intents(id),
  provider_ref TEXT,
  status TEXT NOT NULL DEFAULT 'pending',
  channel TEXT NOT NULL,
  raw_payload TEXT,
  attempted_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  intent_id INTEGER REFERENCES payment_intents(id),
  student_id INTEGER NOT NULL REFERENCES students(id),
  reference TEXT NOT NULL UNIQUE,
  channel TEXT NOT NULL,
  amount REAL NOT NULL,
  status TEXT NOT NULL DEFAULT 'confirmed',
  provider_ref TEXT,
  confirmed_at TEXT,
  confirmed_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_payment_provider_ref
  ON payments(provider_ref) WHERE provider_ref IS NOT NULL;

CREATE TABLE IF NOT EXISTS payment_allocations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  payment_id INTEGER NOT NULL REFERENCES payments(id),
  fee_obligation_item_id INTEGER NOT NULL REFERENCES fee_obligation_items(id),
  amount REAL NOT NULL,
  created_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------- Cash Layer ----------
CREATE TABLE IF NOT EXISTS cash_sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  campus_id INTEGER REFERENCES campuses(id),
  cashier_id INTEGER NOT NULL REFERENCES users(id),
  opening_amount REAL NOT NULL DEFAULT 0,
  expected_amount REAL NOT NULL DEFAULT 0,
  declared_amount REAL,
  difference_amount REAL,
  status TEXT NOT NULL DEFAULT 'open',
  opened_at TEXT NOT NULL DEFAULT (datetime('now')),
  closed_at TEXT
);

CREATE TABLE IF NOT EXISTS cash_payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  payment_id INTEGER NOT NULL UNIQUE REFERENCES payments(id),
  cashier_id INTEGER NOT NULL REFERENCES users(id),
  cash_session_id INTEGER NOT NULL REFERENCES cash_sessions(id),
  cash_receipt_number TEXT,
  received_at TEXT NOT NULL DEFAULT (datetime('now')),
  notes TEXT
);


-- ---------- Receipts Layer ----------
CREATE TABLE IF NOT EXISTS receipts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  payment_id INTEGER NOT NULL UNIQUE REFERENCES payments(id),
  receipt_number TEXT NOT NULL UNIQUE,
  amount REAL NOT NULL,
  issued_by INTEGER REFERENCES users(id),
  issued_at TEXT NOT NULL DEFAULT (datetime('now')),
  print_count INTEGER NOT NULL DEFAULT 1
);

-- ---------- Finance / Ledger Layer ----------
CREATE TABLE IF NOT EXISTS ledger_entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  student_id INTEGER NOT NULL REFERENCES students(id),
  payment_id INTEGER REFERENCES payments(id),
  entry_type TEXT NOT NULL,
  amount REAL NOT NULL,
  balance_after REAL NOT NULL,
  reason TEXT,
  reference TEXT,
  created_by INTEGER REFERENCES users(id),
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS financial_adjustments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  payment_id INTEGER REFERENCES payments(id),
  type TEXT NOT NULL,
  amount REAL NOT NULL,
  reason TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  requested_by INTEGER REFERENCES users(id),
  approved_by INTEGER REFERENCES users(id),
  requested_at TEXT NOT NULL DEFAULT (datetime('now')),
  decided_at TEXT
);

-- ---------- Settlement / Reconciliation Layer ----------
CREATE TABLE IF NOT EXISTS settlement_accounts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  name TEXT NOT NULL,
  provider TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS settlement_batches (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  account_id INTEGER REFERENCES settlement_accounts(id),
  provider_ref TEXT,
  amount REAL,
  status TEXT NOT NULL DEFAULT 'received',
  received_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS reconciliation_cases (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  payment_id INTEGER REFERENCES payments(id),
  settlement_batch_id INTEGER REFERENCES settlement_batches(id),
  case_type TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  assigned_to INTEGER REFERENCES users(id),
  notes TEXT,
  resolution TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  resolved_at TEXT
);

-- ---------- Supporting Layer ----------
CREATE TABLE IF NOT EXISTS notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER NOT NULL REFERENCES schools(id),
  user_id INTEGER REFERENCES users(id),
  receiver TEXT,
  channel TEXT NOT NULL,
  subject TEXT,
  body TEXT,
  status TEXT NOT NULL DEFAULT 'queued',
  attempts INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL DEFAULT (datetime('now')),
  sent_at TEXT
);

CREATE TABLE IF NOT EXISTS audit_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  school_id INTEGER REFERENCES schools(id),
  user_id INTEGER REFERENCES users(id),
  action TEXT NOT NULL,
  entity TEXT,
  entity_id INTEGER,
  details TEXT,
  ip TEXT,
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

