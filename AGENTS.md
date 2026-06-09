# AGENTS.md

Recurlog — field service CRM, originally client-side, now migrating to PHP/MySQL. Both `.html` (client-side) and `.php` (server-side) pages coexist in `pages/`.

**`.php` pages are the live target.** `index.html` redirects to `pages/dashboard.php`. New work goes into `.php` files unless explicitly told otherwise.

## Dual architecture at a glance

| | `.html` pages | `.php` pages |
|---|---|---|
| Data | `localStorage` under `fscrm_*` keys | MySQL `recurlog` DB (same table names) |
| Auth | `fscrm_auth` localStorage flag | PHP session, `fscrm_users` table |
| Script load | `seed.js → router.js → app.js` (+ sidebar.js in `<head>`) | `includes/footer.php` loads `sidebar.js` + `app.js` only |
| Seed | `seed.js` → `seedData.init()` on first visit | `api/seed.php` or Settings > Reset Demo Data |
| Recurrence engine | `window.getNextDueDate` in `app.js:36` | (not yet ported — still uses client-side) |

## PHP backend setup

- **MySQL**: `127.0.0.1:3306`, database `recurlog`, user `root` / no password (see `api/config.php` and `includes/config.php`)
- **REST API**: `api/*.php` — auth, customers, services, tasks, staff, categories, orders, notifications, localities, service_types, seed
- **Table names**: `fscrm_*` matching the former localStorage keys (customers, services, tasks, staff, categories, notifications, orders, users, service_types, localities)
- **PHP templates**: `includes/header.php` (auth guard, sidebar, unread badge), `includes/footer.php` (closes HTML), `includes/sidebar.php` (nav + bottom nav), `includes/config.php` (DB helpers)
- **Data flow (PHP)**: Server queries MySQL → renders HTML with `window.__VARIABLE = <?= json_encode(...) ?>` → JS manipulates DOM

## Script load differences (critical)

**PHP pages** (`includes/footer.php`):
```
sidebar.js → app.js → lucide.createIcons()
```
No `seed.js`, no `router.js`. Auth is handled server-side via PHP sessions.

**HTML pages** (every page):
```
sidebar.js (in <head> via document.write)
seed.js → router.js → app.js → api.js
```
Order matters. `seed.js` defines `window.SEED_DATA`/`window.seedData`. `router.js` guards auth. `api.js` does not exist (dead reference from migration).

## Cache-busting

Dynamic version via `cacheBust()` function in `includes/config.php` using `filemtime()` of `assets/css/custom.css` — no more manual version bumps.

## `app.js` current state (249 lines)

Reduced from original 826-line version. Data accessors and CRUD moved to PHP. What remains:
- Date helpers (`todayISO`, `formatDate`, `formatRelative`, `addToDate`)
- `getNextDueDate()` at line 36 (still client-side — recurrence is not yet ported to PHP)
- `showToast()`, `renderStatusPill()`, `goToCustomer/Staff/Task/Service`, `showLoadingSkeleton`
- `buildSearchableDropdown()` — reusable dropdown component
- All functions exposed on `window.*`

## Recurrence inconsistency (FIXED in seed.php)

Seed data (`api/seed.php`) previously used `repeatFrom: 'last_service'` which matches neither branch in `getNextDueDate()`. Now uses `'last-done'` which matches the app's `getNextDueDate()` branch at `app.js:56`.

## Security improvements applied (June 2026)

- **CSRF protection**: Every POST form across all PHP pages now includes `csrfHiddenField()` + server-side `requireCsrfToken()`. Tokens generated once per session, validated with `hash_equals`.
- **SQL injection**: All direct `$db->query("... $var")` interpolations replaced with prepared statements in customer-detail.php, customer-report.php, seed.php.
- **Rate limiting**: Login page limits to 5 attempts per 5 min per IP (stored in session).
- **Session hardening**: `HttpOnly`, `SameSite=Lax`, `use_strict_mode`, `session_regenerate_id(true)` on login.
- **CORS**: API responses restricted to matching origin when `Origin` header contains `localhost`; no wildcard with credentials.
- **Auth gate**: `requireAuth()` on every page (already present). `requireRole('admin')` ready for future use.
- **Seed endpoint**: `api/seed.php` now restricted to POST + `requireAuth()` + CSRF validation. Uses DELETE in FK-safe order instead of `SET FOREIGN_KEY_CHECKS=0`.
- **Logout**: Clears session cookie (`setcookie(session_name(), '', time() - 3600, '/')`).
- **Double execute bug**: Fixed `task-complete-ajax.php` which had duplicate `$stmt->execute()` calls for the UPDATE.
- **Transactions**: Multi-table writes in service-add, onetime-task, recurring-task, orders now use `begin_transaction()`/`commit()`/`rollback()`.
- **Hardcoded credentials**: Removed from login form HTML (now uses `placeholder` attributes).
- **Dashboard optimization**: Replaced PHP loop aggregation with SQL `GROUP BY` / `SUM(CASE...)`.
- **Pagination**: Added 50-record pagination to customers.php, tasks.php, orders.php.
- **Signature compression**: Client-side JPEG downsampling (max 400px, quality 0.6) via `window.compressSignature()` in `app.js`.
- **Cache-busting**: Dynamic version via `cacheBust()` function using `filemtime()` of `custom.css` — no more static `?v=20260601a`.
- **Input maxlength**: Added `maxlength` attributes to 30 form inputs across 8 pages.
- **mbstring fallback**: Polyfill for `mb_strlen`/`mb_substr` added in `orders.php`.

## ID navigation

Entity pages pass IDs via `localStorage` (`fscrm_currentCustomerId`, etc.) AND as URL params in `.php` pages (`?id=N`). Both mechanisms may be present simultaneously.

## Key stores/tables (not in README)

- `fscrm_service_types` — custom service type names
- `fscrm_localities` — area/locality names (used in orders workflow)
- `fscrm_orders` — order entities (separate from services/tasks)
- `fscrm_users` — PHP auth users

## When adding a persisted key

Add it to both `seedData.init()` (seed.js) and `api/seed.php` for the PHP seed. All keys/table names prefixed `fscrm_`.

## Stale `.html` pages

HTML pages still reference `api.js` which does not exist. They remain as fallback/offline mode but are no longer the primary code path. **Edit the `.php` equivalent first.**

## No tooling

No `package.json`, npm, build step, lint, or test runner. Run locally:
```
npx serve .
python -m http.server 8000
```
Or open any HTML/PHP file directly (PHP needs Apache/Nginx).

## Staff portal (June 2026)

- `fscrm_users` now has `staff_id` column linking to `fscrm_staff.id`.
- 5 staff user accounts seeded: `{firstname}@demo.com` / `demo123`.
- Login redirects staff users to `staff-dashboard.php` instead of `dashboard.php`.
- Admin header redirects staff users to their portal if they hit an admin page.
- `pages/staff-dashboard.php` — mobile-first dashboard with:
  - Stats bar (completed today, pending, missed)
  - Today's tasks section with inline Complete button
  - Upcoming tasks section
  - Assigned orders section
  - Recent activity feed
  - Task complete modal (notes, signature pad, received by/contact)
- Staff can only see/complete their own assigned tasks.

## Admin set password for staff (June 2026)

- `pages/staff.php` now shows a "Has Login"/"No Login" badge on each staff card (via LEFT JOIN on `fscrm_users.staff_id`).
- "Set Password" / "Reset Password" button opens a modal with email + password fields.
- POST handler `set_password` creates a `fscrm_users` record (role=staff, staff_id linked) if none exists, or updates email/password if one does.
- Deleting a staff member now also cleans up the linked `fscrm_users` record.

## Locality management (June 2026)

- `pages/localities.php` — full CRUD page (admin only) for `fscrm_localities`.
- Uses server-side POST handlers (add/edit/delete) matching the `staff.php` pattern.
- Linked from sidebar between Staff and Daybook.

## Report color coding (June 2026)

- `pages/reports.php` now applies status-based row colors in all tables:
  - Completed = green tint, Missed/Overdue = red tint, Pending = amber tint, In Progress = blue tint, Cancelled = gray tint.

## Task detail & edit pages (June 2026)

- `pages/task-detail.php` — full drill-down view showing customer info, schedule, assignment (with reassign button), service details, problem/notes, completion details (signature, received by/contact), and status pill. Works for both admin and staff portal.
- `pages/task-edit.php` — form to update title, status, scheduled date, assigned staff, and notes. CSRF + transaction protected.

## Hard delete for admin users (June 2026)

- Delete buttons with confirmation modals added to:
  - `pages/tasks.php` — delete icon on each task card
  - `pages/onetime-task.php` — trash icon in table action column
  - `pages/recurring-task.php` — trash icon in table action column
  - `pages/orders.php` — delete button on order cards
  - `pages/customers.php` — trash icon in table action column (cascade deletes services, tasks, orders)
- Each uses a confirmation modal showing key details and "This action cannot be undone" warning.
- API cascade deletes fixed in `api/customers.php` and `api/services.php` using transactions.

## Staff reassignment (June 2026)

- `api/reassign.php` — POST endpoint (CSRF protected) supporting entity types: `task`, `order`, `service`.
- `window.reassignStaff()` in `app.js:297` — reusable modal that loads staff list via API, lets admin pick a new assignee, and calls `reassign.php`.
- Tracks all changes in `fscrm_assignment_history` table.
- Notifications sent to both old and new assignee on reassignment.

## PWA support (June 2026)

- `sw.js` — service worker caching login page, CSS, JS, and app icon. Cache-first for assets, network-first for navigations.
- `manifest.json` — standalone display, portrait orientation, navy background, brand green theme, icon set (48x48 to 512x512).
- Install button on login page.
- Icons generated via `scripts/generate-icons.mjs`.

## Push notifications (June 2026)

- **Web Push**: VAPID keys for push notifications. Uses a custom VAPID helper for PHP 8.1+ compatibility (avoids web-token/jwt-library).
- **Expo Push**: Server-side integration for React Native mobile push notifications.
- **Sound**: `playNotificationSound()` in `app.js:278` — Web Audio API beep on new notifications.

## v1 REST API (June 2026)

- JWT-authenticated (HS256) REST API at `api/v1/`.
- Access token: 7 days; refresh token: 30 days.
- Auto-refresh via Axios interceptor in mobile app.
- Pagination (page/per_page), search, and entity-specific filters.
- CRUD for all entities: customers, services, tasks, orders, staff, categories, notifications, localities, service_types.
- Rate limiting: 10 requests per 60 seconds per IP.
- Error codes: `UNAUTHORIZED` (401), `TOKEN_EXPIRED` (401), `FORBIDDEN` (403), `NOT_FOUND` (404), `VALIDATION_ERROR` (400), `RATE_LIMITED` (429), `DB_ERROR`/`INTERNAL_ERROR` (500).

## Database migration

- `migration.sql` — schema-only migration. Run once on the target database before first use:
  ```sql
  SOURCE /path/to/migration.sql;
  ```
  or import via phpMyAdmin / MySQL CLI. Creates all tables with `IF NOT EXISTS` (safe to re-run).
  Includes `fscrm_assignment_history` and `fscrm_users.staff_id` column.
- `db.sql` — raw SQLyog dump with schema + demo data (local dev backup).
- Seeding with demo data is done via `api/seed.php` or Settings > Reset Demo Data.

## Deployment

Push to `main` → `.github/workflows/static.yml` deploys repo root to GitHub Pages. PHP files won't execute on Pages (static only) — deploys client-side version.

For PHP production deployment, see `PRODUCTION.md`.
