# School Fees IS — Complete System Documentation

> **Scope:** every data flow in the MVP — how a request travels, which tables it touches, and what data comes out the other end.
---

## 1. High-Level Architecture

```
┌──────────────────────────────────────────────────────┐
│                     BROWSER                           │
│  HTML forms (POST) · links (GET) · 1 fetch() call    │
└──────────────┬───────────────────────────────────────┘
               │ HTTP
               ▼
┌──────────────────────────────────────────────────────┐
│  public/index.php  —  Front Controller, single entry  │
│  session_start() → loads bootstrap → parses ?route= │
└──────────────┬───────────────────────────────────────┘
               ▼
┌──────────────────────────────────────────────────────┐
│  app/bootstrap.php                                    │
│  • SPL autoloader (class → file)                     │
│  • Database::connect('sqlite:'.DB_PATH)              │
│  • PRAGMA foreign_keys = ON                          │
│  • helpers.php (render, redirect, flash_*, csrf_*)  │
└──────────────┬───────────────────────────────────────┘
               ▼
┌──────────────────────────────────────────────────────┐
│  Controllers (Auth guard → validate → Services → DB) │
│  Services: PaymentService · LedgerService ·           │
│    AllocationService · CashSessionService ·           │
│    AuditService · NotificationService ·              │
│    StudentIdService                                   │
│  Auth.php · Database.php (PDO singleton + txns)      │
└──────────────┬───────────────────────────────────────┘
               ▼
┌──────────────────────────────────────────────────────┐
│  database/school_fees.sqlite  (SQLite, FK enforced)  │
│  database/schema.sql          (DDL, 17 tables)      │
│  database/seed.php            (install + demo data)  │
└──────────────────────────────────────────────────────┘
```

**Five rules that govern every flow:**

1. **Multi-tenant by `school_id`.** Every school-scoped table carries `school_id`. Every DB query in school controllers filters `school_id = Auth::schoolId()`. Only `platform_admin` (whose `users.school_id = NULL`) sees all tenants.

---

## 2. Directory Map

| Path | Role |
|---|---|
| `public/index.php` | Front controller — every HTTP request enters here |
| `public/assets/css/app.css` | Styles |
| `public/assets/js/app.js` | Progressive enhancement (sidebar toggle, confirm dialogs, live student lookup) |
| `app/bootstrap.php` | Autoloader, constants, `Database::connect()`, helpers |
| `app/helpers.php` | `render()`, `redirect()`, `flash_set/flash_get`, `csrf_token/csrf_check`, `e()`, `abort()`, `fmt_money()` |
| `app/Database.php` | PDO singleton — `fetch`, `fetchAll`, `scalar`, `insert`, `update`, `transaction` |
| `app/Auth.php` | `login()`, `logout()`, `user()`, `requireLogin()`, `requireCan()`, `requirePlatform()`, `can()`, `schoolId()` |
| `app/Controllers/*.php` | One class per screen area |
| `app/Services/*.php` | Reusable business logic (payments, ledger, cash, notifications, audit) |
| `app/Views/` | PHP templates, rendered inside `layouts/app.php` |
| `database/schema.sql` | 17-table DDL, FKs, unique indexes |
| `database/seed.php` | Installer — creates schema and demo data (idempotent) |
| `database/school_fees.sqlite` | The actual database file |

---

## 3. Request Lifecycle

1. **Bootstrap.** `public/index.php` starts the PHP session and `require`s `app/bootstrap.php`. That file registers a SPL autoloader (class `Foo_Bar` → `app/Foo/Bar.php`), defines `APP_ROOT` / `DB_PATH` / `SCHEMA_PATH`, calls `Database::connect('sqlite:' . DB_PATH)`, sets `PRAGMA foreign_keys = ON`, and loads helpers.
2. **Route parse.** The router reads `?route=` (default `home`), splits on `/` into `$route` + `$segments`, and uses a big `match`-style table to map routes to `Controller::method`.
3. **Auth guard.** The first line of every controller method is one of:

---

## 4. Database Schema (17 tables, all FK-enforced)

### 4.1 Identity & Tenancy
| Table | Key columns | Purpose |
|---|---|---|
| `schools` | `id, code (UNIQUE), name, status ('active'\|'suspended')` | Tenant root |
| `campuses` | `id, school_id→schools, name, address` | Physical branches |
| `roles` | `id, code, name` | Static role catalogue (10 roles) |
| `users` | `id, school_id→schools (NULL = platform admin), name, email (UNIQUE), password_hash, phone, status, last_login_at` | Login accounts |
| `user_roles` | `user_id→users, role_id→roles, UNIQUE(user_id,role_id)` | Many-to-many role assignment |
| `academic_years` | `id, school_id, name, is_current` | e.g. "2026" |
| `terms` | `id, school_id, academic_year_id, name` | e.g. "Term 1" |
| `classes` | `id, school_id, name` | e.g. "Primary 5" |
| `guardians` | `id, school_id, name, phone, email` | Separate entity from users |
| `student_guardians` | `student_id→students, guardian_id→guardians, relationship, is_primary` | Link table |

### 4.2 Students
| Table | Key columns | Purpose |
|---|---|---|
| `students` | `id, school_id, student_id (e.g. KFS-2026-0238, UNIQUE), reg_form_number, full_name, class_id, status, created_at` | Master record |

### 4.3 Fee Configuration & Billing
| Table | Key columns | Purpose |
|---|---|---|
| `fee_items` | `id, school_id, name, description` | Line-item catalogue (tuition, meals…) |
| `fee_structures` | `id, school_id, term_id→terms, name, status ('draft'\|'published'), created_by` | Term-level pricing plan |
| `fee_structure_items` | `id, fee_structure_id, fee_item_id, amount, UNIQUE(structure,item)` | Prices per item |
| `fee_obligations` | `id, school_id, student_id, term_id, invoice_no (UNIQUE), total_amount, paid_amount, status ('unpaid'\|'partial'\|'paid'), created_by` | The invoice |
| `fee_obligation_items` | `id, fee_obligation_id, fee_item_id, amount, paid_amount` | Per-line paid tracking |

### 4.4 Payments
| Table | Key columns | Purpose |
|---|---|---|
| `payment_intents` | `id, school_id, student_id, reference (UNIQUE), channel, amount, status, idempotency_key, created_by, confirmed_by` | (unused in MVP) |
| `payment_attempts` | `id, payment_intent_id, provider_ref, status, channel, raw_payload` | (unused in MVP) |
| `payments` | `id, school_id, intent_id (NULL in MVP), student_id, reference (UNIQUE), channel ('cash'\|'bank'\|'mobile_money'\|'cheque'), amount, status, provider_ref, confirmed_at, confirmed_by` | Confirmed money in |
| `payment_allocations` | `id, school_id, payment_id, fee_obligation_item_id, amount, created_by` | Which payment line paid which fee line |

Indexes: `idx_intent_idem` (unique on `school_id,idempotency_key`), `idx_payment_provider_ref` (unique on `provider_ref`).

### 4.5 Cash Handling
| Table | Key columns | Purpose |
|---|---|---|
| `cash_sessions` | `id, school_id, campus_id, cashier_id, opening_amount, expected_amount, declared_amount, difference_amount, status ('open'\|'closed'), opened_at, closed_at` | One drawer per cashier per day |
| `cash_payments` | `id, school_id, payment_id (UNIQUE), cashier_id, cash_session_id, cash_receipt_number, received_at, notes` | Ties a cash payment to a drawer |

### 4.6 Ledger, Receipts, Adjustments
| Table | Key columns | Purpose |
|---|---|---|
| `ledger_entries` | `id, school_id, student_id, entry_type ('debit'\|'credit'), amount, description, reference, payment_id (nullable), adjustment_id (nullable), created_by, created_at` | Append-only financial journal |
| `adjustments` | `id, school_id, student_id, type ('discount'\|'waiver'\|'correction'\|'penalty'), amount, reason, status ('pending'\|'approved'\|'rejected'), requested_by, reviewed_by, reviewed_at` | Fee-change requests |
| `receipts` | `id, school_id, payment_id (UNIQUE), receipt_number (UNIQUE), issued_by, issued_at, amount, status ('active'\|'reissued'\|'void')` | One receipt per payment, never re-used |
| `audit_logs` | `id, school_id (nullable), user_id (nullable), action, entity, entity_id, details (JSON), ip, created_at` | Append-only activity log |
| `notifications` | `id, school_id, recipient_user_id, channel ('sms'\|'email'), recipient, subject, body, status ('queued'\|'sent'\|'failed'), sent_at, error` | Outbox queue |
   - `Auth::requireLogin()` → redirects `/` if no session
   - `Auth::requireCan('perm')` → additionally 403s without permission
   - `Auth::requirePlatform()` → 403 unless role is `platform_admin`
4. **CSRF.** Mutating routes call `csrf_check()` which compares `$_POST['csrf']` against `$_SESSION['csrf']`; mismatch → `abort(419)`.
---

## 5. Authentication & Role-Based Access Control

**Class:** `app/Auth.php` — static methods, backed by PHP sessions (`$_SESSION['user_id']`).

### 5.1 Login flow
```
POST /login
  ↓ AuthController::login()
  ↓ Database::fetch("SELECT * FROM users WHERE email=? AND status='active'")
  ↓ password_verify($input, $stored_hash)
  ↓ success → $_SESSION['user_id'] = $user['id']
  ↓ Database::update('users', ['last_login_at' => now()], 'id=?')
  ↓ AuditService::log('auth.login', ...)
  ↓ redirect('/dashboard')
```

The "dashboard chooser" at `GET /` reads `Auth::user()` and redirects to `/dashboard` if already logged in.

### 5.2 Roles & Permissions (10 roles)

| Role | Key permissions |
|---|---|
| `platform_admin` | `*` (all permissions) |
| `director` | reports.view, audit.view, adjust.approve, students.view, receipt.view, ledger.view, recon.view |
| `school_admin` | users.manage, students.manage, fees.manage, billing.manage, school.manage, children.view, receipt.view, reports.view |
| `bursar` | students.manage, fees.manage, billing.manage, payment.record, cash.session, recon.manage/view, adjust.request/approve, reports.view, ledger.view, receipt.view/reprint |
| `accountant` | students.view, recon.manage/view, reports.view, ledger.view, receipt.view, adjust.request/approve |
| `cashier` | students.view, receipt.view/reprint, ledger.view |
| `teacher` | students.view |
| `parent` | children.view, receipt.view |
| `auditor` | reports.view, audit.view, ledger.view, receipt.view, recon.view |
| `support` | students.view, recon.view, recon.manage, receipt.view |

`Auth::can('perm')` checks every role the user holds; `'*'` in any role grants everything. `school_id` is read from the user row (NULL only for platform_admin).
---

## 6. End-to-End Data Flows

### 6.1 Flow 01 — Installation & First-Run Seed
**Route:** `GET/POST /install`  **Controller:** `InstallController`  **Script:** `database/seed.php`

1. `run_schema()` reads `schema.sql`, strips `--` comment lines, splits on `;`, and executes each `CREATE` statement. Errors ("already exists") are swallowed for idempotency.
2. `seed_if_empty()` checks `SELECT COUNT(*) FROM schools`; if `> 0` it returns `false` (already installed).
3. Otherwise it inserts, inside one transaction:
   - 10 roles
   - Platform admin user `platform@schoolfees.test` (`school_id = NULL`)
   - Demo school `KFS / Kampala Future School` + campus `Main Campus`
   - 8 users (director, school_admin, bursar, accountant, cashier, teacher, auditor, parent) — all with password `password123`
   - Academic year `2026` + Terms 1 & 2 + classes `Primary 5`, `Primary 6`
   - Fee items: tuition, meals, transport
4. `seed_rest($ctx)` then inserts:
   - A published `fee_structures` row + 3 `fee_structure_items` (350k / 150k / 50k UGX)
   - 3 students (`KFS-2026-0238..0240`), each with a `guardians` row + `student_guardians` link (and the demo parent linked via `guardians.email = 'parent@kfs.test'`)
   - One `fee_obligations` row per student (`invoice_no = 'INV-2026-T1-<padded student id>'`, total 550 000, status `unpaid`)
   - Three `fee_obligation_items` per obligation
5. Final `AuditService::log('system.seeded', …)` records the install.

**Tables written:** `roles, users, user_roles, schools, campuses, academic_years, terms, classes, fee_items, fee_structures, fee_structure_items, students, guardians, student_guardians, fee_obligations, fee_obligation_items, audit_logs`.

### 6.2 Flow 02 — Logout
`POST /logout` → `AuthController::logout()` → `Auth::logout()` → `unset($_SESSION['user_id'])` → `AuditService::log('auth.logout')` → `redirect('/')`.

### 6.3 Flow 03 — Dashboard Routing (role-aware)
`DashboardController::index()` inspects `Auth::roles()` and picks a view:

| Role(s) | View | Data shown |
|---|---|---|
| platform_admin | `platform/dashboard` | All schools, all users, recent audit |
| director | `dashboard/director` | Collected / outstanding / # students |
| school_admin | `dashboard/school_admin` | Student count, staff list, recent activity |
| bursar | `dashboard/bursar` | Today's cash, open session, pending recon |
| accountant | `dashboard/accountant` | Ledger totals, pending adjustments |

---

## 7. Student Lifecycle

### 7.1 Flow 04 — Register a Student (with instant billing)
**Route:** `GET/POST /students` → `StudentController::index()` / `store()`
**Permission:** `students.manage` (bursar, school_admin)

**Read phase (`GET`):**
1. Load classes, terms, published fee structures for this school.
2. Load students list: `SELECT * FROM students WHERE school_id = ? ORDER BY id DESC`.

**Write phase (`POST /students/store`, wrapped in a DB transaction):**
1. Validate `full_name` (required), `class_id`, optional `reg_form_number`, optional `fee_structure_id`.
2. Lock the next ID: `SELECT COALESCE(MAX(CAST(SUBSTR(student_id,-4) AS INTEGER)),0) FROM students WHERE school_id=?` → `$nextSeq`.
3. `StudentIdService::generate('KFS', $nextSeq)` → format `KFS-<YYYY>-<4-digit seq>` (e.g. `KFS-2026-0238`).
4. `INSERT INTO students (school_id, student_id, reg_form_number, full_name, class_id, status='active')` → returns `$sid`.
5. `AuditService::log('student.created', 'students', $sid)`.
6. **If a fee structure was chosen:**
   - Fetch `fee_structures` row → get its `term_id`
   - Fetch its `fee_structure_items` and sum amounts
   - Build invoice number: `INV-<YYYY>-T<termId>-<padded student id>`
   - `INSERT INTO fee_obligations (…, total_amount=$total, status='unpaid')`
   - Loop: `INSERT INTO fee_obligation_items` (one per fee line)
7. `flash_set('success', "Student registered … invoice … created.")` → `redirect('/students')`.

**Tables written:** `students`, optionally `fee_obligations` + `fee_obligation_items`, `audit_logs`.

### 7.2 Flow 05 — Bulk Import Students (CSV)
**Route:** `GET/POST /students/import`
1. `GET` renders an upload form (CSV with columns `full_name, class, reg_form_number`).
2. `POST` → parse with `fgetcsv()`, validate header, then for each row: same logic as `store()` but no invoice (caller must assign fee structures separately).
3. Row-level errors are collected into `flash_set('error', implode('<br>', $errors))` so the user can fix and retry.
4. On success: `AuditService::log('students.imported', 'students', null, ['count'=>$n])`.

**Tables written:** `students`, possibly `guardians`+`student_guardians` if guardian info present, `audit_logs`.

### 7.3 Flow 06 — Search / Filter Students
`GET /students?q=<text>&class_id=<id>&status=<active|inactive>`
- SQL: `SELECT * FROM students WHERE school_id = ? AND (full_name LIKE %q% OR student_id LIKE %q% OR reg_form_number LIKE %q%) [AND class_id=?] [AND status=?] ORDER BY full_name LIMIT 100`
- The same query powers the inline "Assign fees" list — for each student the controller also fetches their current outstanding balance via `LedgerService::balance($studentId)`.

### 7.4 Flow 07 — Live Student Lookup (the only AJAX endpoint)
**Route:** `GET /lookup/student?id=<studentId-text>`  **Controller:** `PaymentController::lookupStudent()`
**Permission:** `payment.record`

1. JS (public/assets/js/app.js) listens for `input` on `[data-lookup="student"]`; debounces 350 ms, then `fetch('/lookup/student?id=…')`.
2. Server runs:
   ```sql
   SELECT s.id, s.full_name, s.class_id, c.name AS class_name,
          COALESCE(SUM(CASE WHEN le.entry_type='debit'  THEN  le.amount ELSE 0 END),0)
         - COALESCE(SUM(CASE WHEN le.entry_type='credit' THEN le.amount ELSE 0 END),0) AS outstanding
   FROM students s
   LEFT JOIN classes c ON c.id = s.class_id
   LEFT JOIN ledger_entries le ON le.student_id = s.id
   WHERE s.school_id = ? AND s.student_id = ?
   GROUP BY s.id
   ```
3. Responds JSON `{found: true, id, name, class_name, outstanding}` or `{found: false}`.
4. JS injects a hidden `<input name="student_db_id">` into the form so the POST uses the resolved database id rather than trusting the typed text.

**Security:** the query filters by `school_id`, so a bursar from School A cannot probe School B's students.

---

## 8. Fee Configuration

### 8.1 Flow 08 — Create Fee Items
**Route:** `POST /fees/items/store`
**Permission:** `fees.manage`

1. `INSERT INTO fee_items (school_id, name, description)`.
2. `AuditService::log('fee_item.created', 'fee_items', $newId)`.

**Tables written:** `fee_items`, `audit_logs`.

### 8.2 Flow 09 — Create / Publish a Fee Structure
**Route:** `GET/POST /fees/structures` → `FeeController::index()` / `structureStore()`
**Permission:** `fees.manage`

**Write phase:**
1. Validate: term_id required, name required, at least one item with `amount > 0`.
2. `INSERT INTO fee_structures (school_id, term_id, name, status='published', created_by)` — published immediately (no draft stage in the MVP).
3. Loop through each item: `INSERT INTO fee_structure_items (fee_structure_id, fee_item_id, amount)`.
4. `AuditService::log('fee_structure.created', 'fee_structures', $structureId, ['name'=>$name])`.

**Tables written:** `fee_structures`, `fee_structure_items`, `audit_logs`.

### 8.3 Flow 10 — Bulk-Bill All Students for a Structure (Assign Fees)
**Route:** `POST /fees/assign` → `FeeController::assign()`
**Permission:** `billing.manage`

This creates a `fee_obligations` row for every active student that doesn't already have one for the chosen term.

1. `$structure = fetch('SELECT * FROM fee_structures WHERE id=? AND school_id=?')`.
2. `$items = fetchAll('SELECT fee_item_id, amount FROM fee_structure_items WHERE fee_structure_id=?')`.
3. `$students = fetchAll('SELECT id FROM students WHERE school_id=? AND class_id=? AND status=?')`.
4. For each student:
   - Check: `SELECT id FROM fee_obligations WHERE student_id=? AND term_id=?` → skip if exists.
   - `$total = array_sum(array_column($items,'amount'))`.
   - `INSERT fee_obligations (…)` → invoice_no = `INV-<YEAR>-T<term>-<padded student id>`.
   - Loop: `INSERT fee_obligation_items (…paid_amount=0)`.
5. Count created in `audit_logs` details.

**Tables written:** `fee_obligations`, `fee_obligation_items`, `audit_logs`.

### 8.4 Flow 11 — Adjust an Individual Obligation (Discount / Waiver)
**Route:** `POST /fees/adjust` → `FeeController::adjust()`
**Permission:** `adjust.request` (or `adjust.approve` if auto-approving)

---

## 9. Payment Recording (Core Financial Flow)

### 9.1 Architecture — two-layer money tracking

```
fee_obligations.paid_amount   ← running total, updated by AllocationService
ledger_entries                ← append-only debit (invoice) / credit (payment) pairs
```

The UI balance for any student = `SUM(debits) - SUM(credits)` over `ledger_entries`. This number is never stored — it is always computed. `fee_obligations.paid_amount` is the secondary tracker driving invoice status badges.

### 9.2 Flow 12 — Record a Payment (idempotent, transactional)
**Route:** `GET /payments` (form) → `POST /payments/store` → `PaymentController::store()`
**Permission:** `payment.record` (bursar)

Inside `Database::transaction()`:

**Step 1 — Duplicate guard (idempotency).**
```
SELECT id FROM payments WHERE school_id=:s AND provider_ref=:ref
```
`provider_ref` = bank slip / mobile-money transaction number. If it already exists → return the existing `receipt_number` with `duplicate: true`. Prevents double-entry when a bursar submits twice.

**Step 2 — Create the payment record.**
```sql
INSERT INTO payments (
  school_id, intent_id=NULL, student_id, reference,
  channel, amount, status='confirmed',
  provider_ref, confirmed_by, confirmed_at=NOW()
) → $paymentId
```
`reference` = `PAY-YYYYMMDD-XXXXXX` from `PaymentService::newReference()` (sequence drawn from `ref_counters` table).

**Step 3 — Allocation.** `AllocationService::allocate()`:
1. Find `fee_obligation_items` for the student where `paid_amount < amount`.
2. Per line: `INSERT payment_allocations (amount = min(outstanding, remaining))`, update `fee_obligation_item.paid_amount`, deduct from remaining.
3. Money left over after all items = **advance credit** (carried forward, shown in ledger description).

**Step 4 — Ledger credit.** `LedgerService::postCredit(...)`:
```sql
INSERT INTO ledger_entries (school_id, student_id, entry_type='credit',
  amount, description, reference, payment_id, created_by)
```

### 9.3 Flow 13 — Void / Reverse a Payment
**Route:** `POST /payments/void` → `PaymentController::void()`
**Permission:** `payment.record` + admin role

1. `SELECT * FROM payments WHERE id=? AND school_id=?`.
2. `SELECT * FROM payment_allocations WHERE payment_id=?`.
3. For each allocation → `UPDATE fee_obligation_items SET paid_amount = paid_amount - ?`.
4. Recompute `fee_obligations.paid_amount` and `status` from its items.
5. `DELETE payment_allocations WHERE payment_id=?`.
6. `DELETE ledger_entries WHERE payment_id=?`.
7. `UPDATE receipts SET status='void' WHERE payment_id=?`.
8. `AuditService::log('payment.voided', 'payments', $id, ['amount'=>…])`.

**Tables modified:** `fee_obligation_items`, `fee_obligations`, `payment_allocations`, `ledger_entries`, `receipts`, `audit_logs`.

---

## 10. Cash Session Management

### 10.1 Flow 14 — Open Cash Session
**Route:** `POST /cash/open` → `CashController::open()` → `CashSessionService::openSessionFor($cashierId)`
**Permission:** `cash.session`

```sql
SELECT id FROM cash_sessions WHERE cashier_id=? AND status='open'
  → if found: reuse it (bursar continues their shift)
INSERT INTO cash_sessions (school_id, campus_id, cashier_id,
                           opening_amount=0, expected_amount=0, status='open')
```
`AuditService::log('cash.session.opened', 'cash_sessions', $id)`.

**Tables written:** `cash_sessions`, `audit_logs`.
---

## 11. Ledger, Reports & Reconciliation

### 11.1 Flow 18 — Ledger (Student Account Statement)
**Route:** `GET /ledger` → `LedgerController::index($studentId?)`
**Permission:** `ledger.view`

1. If `$studentId` is given: fetch student info + `LedgerService::balance($studentId)`.
2. `LedgerService::statement($studentId)` runs:
   ```sql
   SELECT le.*, u.name AS created_by_name,
          (SELECT name FROM users WHERE id=le.confirmed_by) AS confirmed_by_name
   FROM ledger_entries le
   LEFT JOIN users u ON u.id=le.created_by
   WHERE le.school_id=? [AND le.student_id=?]
   ORDER BY le.created_at ASC
   ```
3. Returns both debit and credit rows so the student statement shows every charge (debit) and every payment (credit). Running balance is computed in the view.

`LedgerService` methods:
- `balance($studentId)` → `SUM(debit_amounts) - SUM(credit_amounts)`
- `postCredit(…)` → `INSERT ledger_entries (type='credit', …)`
- `postDebit(…)` → `INSERT ledger_entries (type='debit', …)` (used by billing to record the initial invoice)
- `postDiscount(…)` → `INSERT ledger_entries (type='credit', description='Discount …', …)`

### 11.2 Flow 19 — Financial Reports
**Route:** `GET /reports` → `ReportController::index()`
**Permission:** `reports.view`

Supported report types (selected by `$_GET['type']`):

| type param | Report | Key SQL |
|---|---|---|
| `collection` | Daily / date-range collection summary | `SELECT channel, SUM(amount) FROM payments WHERE school_id=? AND status='confirmed' AND confirmed_at BETWEEN ? AND ? GROUP BY channel` |
| `outstanding` | Per-student outstanding balances | `SELECT s.student_id, s.full_name, SUM(debit) - SUM(credit) AS balance FROM students s JOIN ledger_entries le ON le.student_id=s.id GROUP BY s.id HAVING balance>0` |
| `fee_summary` | Per fee-item totals collected vs billed | JOINs `fee_obligation_items` → `payment_allocations` |

All reports filter by `Auth::schoolId()` and the selected academic year.

### 11.3 Flow 20 — Cash Reconciliation
**Route:** `GET /reconciliation` → `ReconciliationController::index()`
**Permission:** `recon.view` / `recon.manage`

Shows per-cashier per-session data:
```sql
SELECT cs.*, u.name AS cashier,
       (SELECT COUNT(*) FROM cash_payments cp WHERE cp.cash_session_id=cs.id) AS payment_count,
       (SELECT SUM(p.amount) FROM cash_payments cp JOIN payments p ON p.id=cp.payment_id
        WHERE cp.cash_session_id=cs.id AND p.status='confirmed') AS collected
FROM cash_sessions cs
JOIN users u ON u.id=cs.cashier_id
WHERE cs.school_id=? [AND cs.status=?]
ORDER BY cs.opened_at DESC
```
Difference badge shows green (exact), red (over/short).

`recon.manage` permission additionally allows the accountant to mark a session as reconciled (add a `reviewed_at` / `reviewed_by` column — the schema has this as nullable for future use).

### 11.4 Flow 21 — Audit Trail
**Route:** `GET /audit` → `AuditController::index()` OR `GET /platform/audit` (platform-wide)
**Permission:** `audit.view`
---

## 12. Parent Portal

### 12.1 Flow 23 — Parent Login & Child Overview
**Route:** `GET/POST /parent` → `ParentController::index()`
**Permission:** `children.view`

The parent `users` row has role `parent`. On login the controller:
1. Looks up students via `student_guardians → guardians → g.email = Auth::user()['email']`.
2. Fetches each child's balance via `LedgerService::balance($childId)`.
3. Renders `parents/portal` with the child's student record, class, school, and live balance.

**Tables read:** `students, classes, schools, guardians, student_guardians, users, ledger_entries`.

### 12.2 Flow 24 — View Child's Receipts
**Route:** `GET /parent/receipts?student=<id>` → `ParentController::receipts()`
**Permission:** `receipt.view` + parent must own that student

```sql
SELECT r.*, p.amount, p.channel, p.confirmed_at
FROM receipts r
JOIN payments p ON p.id=r.payment_id

---

## 13. Platform Administration

### 13.1 Flow 25 — Platform Dashboard (all-tenant view)
**Route:** `GET /platform` → `PlatformController::index()`
**Permission:** `platform_admin` only (`Auth::requirePlatform()`)

- Counts: active schools, users, students.
- Platform totals: `SUM(fee_obligations.total_amount)` billed, `SUM(payments.amount WHERE confirmed)` collected, outstanding = billed − collected.
- Per-school rollup via correlated subqueries (students, users, billed, collected, outstanding).
- Last 8 `audit_logs` rows (all schools).

### 13.2 Flow 26 — Onboard a New School
**Route:** `GET/POST /platform/schools/new` → `PlatformController::schoolStore()`
**Permission:** `platform_admin`

All inside one `Database::transaction()`:
1. Validate unique `code` and admin email.
2. `INSERT INTO schools (code, name, status='active')` → `$schoolId`.
3. `INSERT INTO campuses (school_id, name)` → "Main Campus" (or form value).
4. `INSERT INTO users` → school admin (password hashed) + `user_roles` → `school_admin`.
5. `INSERT INTO users` → bursar, email `bursar.<code>@school.test`, same password + `user_roles` → `bursar`.
6. `INSERT INTO academic_years (school_id, name, is_current=1)`.
7. `INSERT INTO terms (school_id, academic_year_id, name)`.
8. `AuditService::log(null, Auth::id(), 'school.onboarded', 'schools', $schoolId, …)`.

**Tables written:** `schools, campuses, users, user_roles, academic_years, terms, audit_logs`.

### 13.3 Flow 27 — Create a User in Any School
**Route:** `GET/POST /platform/users/new` → `PlatformController::userStore()`
**Permission:** `platform_admin`

1. Validate name / email / password ≥ 8 chars / role.
2. Reject duplicate email.
3. `INSERT INTO users (school_id (nullable), name, email, password_hash, status='active')`.
4. `INSERT INTO user_roles (user_id, role_id)` from the chosen role code.
5. `AuditService::log($schoolId, Auth::id(), 'user.created', 'users', $uid, …)`.

`POST /platform/users/toggle` flips `status` between `active` and `disabled`; the seed platform admin account is protected from being disabled.

### 13.4 Flow 28 — Platform Audit Log
**Route:** `GET /platform/audit` → `PlatformController::audit()`
**Permission:** `platform_admin`

Cross-tenant `audit_logs` view with optional `school` / `action LIKE` filters, `LIMIT 300`, newest first.

---

## 14. Security Model

| Threat | Mitigation in code |
|---|---|
| Session hijacking | `session_start()` with default cookie flags; `session_regenerate_id` on login |
| Password storage | `password_hash($pass, PASSWORD_DEFAULT)` + `password_verify()` |
| SQL injection | 100 % prepared statements via `Database::fetch/fetchAll/scalar/insert/update` |
| CSRF | `csrf_check()` compares `$_POST['csrf']` vs `$_SESSION['csrf']` on every mutating route |
---

## 15. Every Write Path — Table-by-Table Inventory

### Identity & Tenancy
| Table | Writer(s) | Trigger |
|---|---|---|
| `schools` | `seed.php`, `PlatformController::schoolStore` | Install; platform onboards a new school |
| `campuses` | `seed.php`, `PlatformController::schoolStore` | Same as above |
| `roles` | `seed.php` | Install only (static catalogue) |
| `users` | `seed.php`, `Auth::login` (last_login_at), `PlatformController::userStore` | Install; login; platform admin creates user |
| `user_roles` | `seed.php`, `PlatformController::schoolStore`, `PlatformController::userStore` | Install; school onboard; user creation |
| `academic_years` | `seed.php`, `PlatformController::schoolStore` | Install; school onboard |
| `terms` | `seed.php`, `PlatformController::schoolStore` | Install; school onboard |
| `classes` | `seed.php`, `UserController::classStore` | Install; school admin adds a class |
| `guardians` | `seed.php`, `StudentController` | Install; each student registration creates a guardian stub |
| `student_guardians` | `seed.php`, `StudentController` | Install; each student registration links guardian |
| `students` | `seed.php`, `StudentController::store`, `StudentController::importSave` | Install (3 demo); manual registration; CSV import |
| `fee_items` | `FeeController::itemStore` | Bursar defines a fee item |
| `fee_structures` | `FeeController::structureStore` | Bursar publishes a term pricing template |
| `fee_structure_items` | `FeeController::structureStore` | Same — one row per fee line |
| `fee_obligations` | `FeeController::assign`, `StudentController::store` | Bulk-bill all students; or single-student registration |
| `fee_obligation_items` | `FeeController::assign`, `StudentController::store` | Same — one row per fee line |
| `adjustments` | `FeeController::adjust` | Bursar requests a discount/waiver |
| `payments` | `PaymentService::record` | Every confirmed payment (cash, bank, mobile, cheque) |
| `payment_allocations` | `AllocationService::allocate` | Same transaction as payments — one per fee line paid |
| `cash_sessions` | `CashSessionService::openSessionFor`, `CashSessionService::closeSession` | Bursar opens shift; bursar closes shift |
| `cash_payments` | `PaymentService::record` (cash branch) | Every cash payment |
| `receipts` | `PaymentService::issueReceipt` | Every confirmed payment |
| `notifications` | `NotificationService::enqueueReceipt` | Every confirmed payment |
| `ledger_entries` | `AllocationService::allocate` (credit), `LedgerService::postDebit` (billing), `LedgerService::postDiscount` (adjustment) | Invoice (debit); payment (credit); discount (credit) |
| `audit_logs` | `AuditService::log` (called by every mutating controller) | Every significant system event |
| XSS | `e()` helper (htmlspecialchars) on all echoed user data |
| Multi-tenant data bleed | every school query filters `school_id = Auth::schoolId()`; platform routes gated by `requirePlatform()` |
| IDOR (parent access) | parent receipts limited to students linked through `student_guardians → guardians.email` |
| Duplicate payments | `payments.provider_ref` UNIQUE partial index + in-transaction duplicate check |
| Race conditions | duplicate re-check runs *inside* the same transaction as the insert |
| Audit tampering | `audit_logs` is append-only; no update/delete route exists |
WHERE p.student_id=? AND r.status='active'
ORDER BY r.issued_at DESC
```

```sql
SELECT a.*, u.name AS actor_name, s.name AS school_name
FROM audit_logs a
LEFT JOIN users u ON u.id=a.user_id
---

## 16. All Routes, Controllers, and Permission Guards

```
PUBLIC (no auth)
  GET  /                          LandingController::index
  POST /login                     AuthController::login
  POST /logout                    AuthController::logout

DASHBOARD
  GET  /dashboard                 DashboardController::index  (requiresLogin → role-aware view)

STUDENTS
  GET  /students                  StudentController::index         students.view
  POST /students                  StudentController::store          students.manage
  GET  /students/import          StudentController::import          students.manage
  POST /students/import          StudentController::importSave    students.manage

FEES
  GET  /fees                      FeeController::billing              billing.manage
  POST /fees/structures           FeeController::structureStore      fees.manage
  POST /fees/assign               FeeController::assign              billing.manage
  POST /fees/adjust               FeeController::adjust              adjust.request

PAYMENTS
  GET  /payments                  PaymentController::index           payment.record
  POST /payments/store            PaymentController::store          payment.record
  POST /payments/void             PaymentController::void           payment.record
  GET  /lookup/student            PaymentController::lookupStudent   payment.record

CASH
  GET  /cash                      CashController::index               cash.session
  POST /cash/open                 CashController::open               cash.session
  POST /cash/close                CashController::close              cash.session
  GET  /cash/report               CashController::report              cash.session

RECEIPTS
  GET  /receipts                  ReceiptController::index            receipt.view
  GET  /receipts/<id>             ReceiptController::show             receipt.view
  POST /receipts/<id>/reprint    ReceiptController::reprint          receipt.reprint

LEDGER
  GET  /ledger                    LedgerController::index             ledger.view

RECONCILIATION
  GET  /reconciliation            ReconciliationController::index     recon.view
  POST /reconciliation            ReconciliationController::manage    recon.manage

REPORTS
  GET  /reports                   ReportController::index            reports.view

AUDIT
  GET  /audit                     AuditController::index               audit.view

PARENT PORTAL
  GET  /parent                    ParentController::index             children.view
  GET  /parent/receipts           ParentController::receipts          children.view + receipt.view

SCHOOL SETTINGS
  GET  /users                     UserController::index               users.manage
  POST /users/school              UserController::schoolStore         users.manage
  POST /users/class               UserController::classStore          users.manage

PLATFORM ADMIN
  GET  /platform                  PlatformController::index           platform_admin only
  GET  /platform/schools         PlatformController::schools         platform_admin only
  GET  /platform/schools/new     PlatformController::schoolNew       platform_admin only
  POST /platform/schools/new     PlatformController::schoolStore     platform_admin only
  POST /platform/schools/toggle  PlatformController::schoolToggle    platform_admin only
  GET  /platform/users           PlatformController::users           platform_admin only
  GET  /platform/users/new       PlatformController::userNew          platform_admin only
  POST /platform/users/new       PlatformController::userStore       platform_admin only
  POST /platform/users/toggle    PlatformController::userToggle       platform_admin only
  GET  /platform/audit           PlatformController::audit           platform_admin only

INSTALL
  GET  /install                   InstallController (runs schema + seed)
```

*Document generated by reading every source file. Last updated: 2026-09-28.*
LEFT JOIN schools s ON s.id=a.school_id
WHERE [conditions] ORDER BY a.id DESC LIMIT 300
```
Filterable by action type and date range. `audit_logs` is append-only (no UPDATE/DELETE route exists).

`AuditService::all()` accepts optional `schoolId` (NULL = all schools) and `action` filter.

### 11.5 Flow 22 — Notifications (Receipt Delivery)
**Route:** triggered internally, not user-facing
**Service:** `NotificationService`

`enqueueReceipt($receiptId, $studentId, $schoolId)`:
1. Fetch `receipts`, `students`, `guardians` for the student.
2. For each guardian with an email: `INSERT INTO notifications (school_id, recipient_user_id, channel='email', recipient, subject, body, status='queued')`.
3. The `notifications` table exists as an outbox; no actual SMTP/HTTP send is implemented in the MVP (status stays `'queued'`).

`sendAll($schoolId)` iterates queued rows and attempts to send (swallows errors, writes `status='sent'|'failed'`). Designed to be called by a cron job in production.

### 10.2 Flow 15 — Cash Payment → Drawer (subset of Flow 12)
When the payment POST carries `channel='cash'`:
1. Find (or open) the cashier's `cash_sessions` row with `status='open'`.
2. Insert `cash_payments (payment_id, cashier_id, cash_session_id, cash_receipt_number, received_at, notes)`.
3. The session's `expected_amount` grows by the payment amount — this is what closing reconciles against.

### 10.3 Flow 16 — Close Cash Session (reconciliation)
**Route:** `POST /cash/close` → `CashController::close()` → `CashSessionService::closeSession()`
**Permission:** `cash.session`

1. `SELECT SUM(amount) FROM cash_payments WHERE cash_session_id=?` → `$actual`.
2. `UPDATE cash_sessions SET declared_amount=?, difference_amount=(declared - actual), status='closed', closed_at=NOW()`.
3. `AuditService::log('cash.session.closed', 'cash_sessions', $id, ['declared'=>…,'actual'=>…])`.

`difference_amount` is the key audit number — over/under declaration per shift.

### 10.4 Flow 17 — Shift Report
**Route:** `GET /cash/report` → `CashController::report()`
**Permission:** `cash.session`

Lists the cashier's closed sessions in a date range with per-session `expected / declared / difference` and a running cash total. Data comes straight from `cash_sessions` + `cash_payments`; no aggregation table.

**Step 5 — Receipt.** `PaymentService::issueReceipt(...)`:
```sql
SELECT COALESCE(MAX(receipt_number),0)+1 FROM receipts WHERE school_id=:s
INSERT INTO receipts (school_id, payment_id, receipt_number, issued_by, amount, status='active')
```
Receipt numbers are school-global integers, never reused. Status starts `'active'`.

**Step 6 — Cash wiring** (if `channel='cash'`, see §10):
```
$cash_session = CashSessionService::openSessionFor($recordedBy)  // reuses open session
INSERT INTO cash_payments (payment_id, cashier_id, cash_session_id, cash_receipt_number, received_at, notes)
```

**Step 7 — Notification.** `NotificationService::enqueueReceipt(...)` → inserts a `notifications` row.

**Step 8 — Audit.** `AuditService::log('payment.recorded', 'payments', $paymentId, …)`.

**Tables written in one transaction:** `payments, payment_allocations, fee_obligation_items, fee_obligations, ledger_entries, receipts, cash_payments?, notifications, audit_logs`.
1. `INSERT INTO adjustments (school_id, student_id, type, amount, reason, status='pending', requested_by)`.
2. `AuditService::log('adjustment.requested', 'adjustments', $adjId)`.
3. If `type='discount'` (auto-approved by default for small amounts):
   - Create a new positive `fee_obligation_items` row with amount = discount, `paid_amount=0`.
   - Update `fee_obligations.total_amount -= amount`.
   - `LedgerService::postDiscount(...)` creates a credit ledger entry.
4. If `type='waiver'` / `type='correction'` → pending until reviewed.

**Tables written:** `adjustments`, possibly `fee_obligation_items`, `fee_obligations`, `ledger_entries`, `audit_logs`.
| cashier / teacher | `dashboard/staff` | Student lookup + receipt reprint |
| parent | `dashboard/parent` | Children's balances, recent receipts |
| auditor | `dashboard/auditor` | Read-only reports + audit trail |
| support | `dashboard/support` | Recon support view |

All numbers are computed live from SUM over `payments`, `fee_obligations`, `ledger_entries` — no cached aggregates.

### 5.3 School isolation
- School-scoped users always query `WHERE school_id = :sid` using `Auth::schoolId()`.
- `platform_admin` never has a `school_id`; platform-scoped routes (PlatformController) ignore `school_id` filtering.
- `cash_sessions` carry both `school_id` and `cashier_id`; a cashier can only open one session at a time per their school.
5. **Input read.** Controllers pull `$_POST` (forms) or `$_GET` (filters). All SQL is prepared statements — no string interpolation of user data.
6. **Service call / query.** Simple reads run PDO directly. Financial writes delegate to `PaymentService`, `LedgerService`, etc.
7. **Response.**
   - `render('view/name', $data)` → extracts vars, wraps in `layouts/app.php`, echoes HTML.
   - `redirect('/path')` → `header('Location: …')` + `exit` (PRG pattern — every POST redirects after success).
   - `flash_set('success'|'error', $msg)` writes a one-shot message to the session; the layout prints and clears it.
   - Only one JSON endpoint exists: `GET /lookup/student` (used by JS).
2. **No payment gateway.** `payment_intents` / `payment_attempts` tables exist but the MVP records payments manually. `payments.intent_id` stays `NULL`; `payments.provider_ref` holds a bank slip or mobile-money reference number.
3. **Dual money state.** `fee_obligations` + `fee_obligation_items` carry the invoice with running `paid_amount`. `ledger_entries` is the append-only audit trail. UI balances are always computed by summing ledger rows.
4. **All-or-nothing transactions.** Every financial mutation (payment, allocation, ledger, receipt) runs inside `Database::transaction()`.
5. **Everything is audited.** `AuditService::log()` is called for every student creation, fee change, payment, adjustment, user change, and platform event.
> **Stack:** plain PHP 8 (no framework), SQLite (PDO), server-rendered views, session-based auth, zero external services.