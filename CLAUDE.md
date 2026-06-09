# CLAUDE.md

Recurlog — field service CRM, **dual architecture**: original client-side (`.html` ES5 + localStorage) and server-side (`.php` + MySQL). Both coexist in `pages/`. Work goes into `.php` files unless told otherwise.

See `AGENTS.md` for the full dual-arch reference, `README.md` for design system and data model.

## Commands

No build, lint, or test tooling. Run locally:
```
npx serve .
python -m http.server 8000
```
PHP pages need Apache/Nginx with MySQL. Push to `main` triggers `.github/workflows/static.yml` deploys to GitHub Pages (static only — client-side version). See `PRODUCTION.md` for PHP deployment.

## Architecture

### Dual-mode pages

| | `.html` | `.php` |
|---|---|---|
| Data | `localStorage` under `fscrm_*` | MySQL `recurlog` database |
| Auth | `fscrm_auth` localStorage flag | PHP session, `fscrm_users` table |
| Scripts | `sidebar.js` (head) → `seed.js` → `router.js` → `app.js` → `api.js` (does not exist) | `includes/footer.php`: `sidebar.js` → `app.js` only |
| Seed | `seedData.init()` in `seed.js` | `api/seed.php` or Settings > Reset |

**PHP page flow**: `includes/config.php` guards auth → server queries MySQL → renders HTML with `window.__DATA = <?= json_encode(...) ?>` → JS manipulates DOM.

**HTML page flow**: `seed.js` (window.SEED_DATA) → `router.js` (auth guard) → `app.js` (CRUD + helpers) → all synchronous, all `window.*`.

### Auth

PHP pages use MySQL-backed sessions (`includes/config.php`, `fscrm_users`). HTML pages use `fscrm_auth` boolean in localStorage via `router.js`. Demo login: `admin@demo.com` / `demo123`. Staff: `{firstname}@demo.com` / `demo123`.

### Entity navigation

Now **dual**: `localStorage` (`fscrm_currentCustomerId`) AND URL params (`?id=N`) in `.php` pages. Both may be set simultaneously.

### Domain model

`customers → services → tasks`. Recurrence loop (`completeTask()` → `getNextDueDate()` → new pending task) is **still client-side** in `app.js:36`. Data CRUD moved to PHP (`api/*.php`). `orders` are a separate workflow (see `pages/orders.php`).

### Recurrence inconsistency (FIXED)

Seed data (`api/seed.php`) now uses `repeatFrom: 'last-done'` which matches the `'last-done'` branch in `getNextDueDate()`. Both `seed.js` and `api/seed.php` should be consistent.

## Key features implemented (June 2026)

- **Staff portal**: 5 staff users with dedicated dashboard, task management, signature capture
- **Staff password management**: admin can set/reset passwords from staff list
- **Locality CRUD**: admin-only management of `fscrm_localities`
- **Report color coding**: status-based row colors (green/red/amber/blue/gray)
- **Task detail & edit**: full drill-down and edit pages
- **Hard delete**: admin delete with cascade for tasks, orders, customers
- **Staff reassignment**: change assignee with history tracking + notifications
- **PWA support**: service worker + manifest + install button
- **Push notifications**: Web Push (VAPID) + Expo Push
- **v1 REST API**: JWT-authenticated (HS256) at `api/v1/`

## Conventions

- **ES5 only** in core JS files — `var`/`function`, no `let`/`const`/arrows. Ships un-transpiled to browsers.
- **All keys/tables prefixed `fscrm_`**. IDs from `getNextId()` (client-side) or MySQL auto-increment (PHP).
- **Adding a persisted key**: add to both `seedData.init()` (seed.js) AND `api/seed.php`.
- **Cache-bust**: Dynamic version via `cacheBust()` using `filemtime()` of `custom.css` — no more static version bumps.
- **Styling**: custom mobile-first system in `custom.css` + Tailwind CDN. Use `.btn`, `.card`, `.data-table`, `.badge-*`, `.modal-*`, `.toast`. Brand: `#1DB954`, sidebar: `#0B1E3D`.
- **HTML pages still reference `api.js`** which does not exist — stale from migration.
