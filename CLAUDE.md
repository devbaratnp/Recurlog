# CLAUDE.md

Recurlog — field service CRM, **dual architecture**: original client-side (`.html` ES5 + localStorage) and server-side (`.php` + MySQL). Both coexist in `pages/`. Work goes into `.php` files unless told otherwise.

See `AGENTS.md` for the full dual-arch reference, `README.md` for design system and data model.

## Commands

No build, lint, or test tooling. Run locally:
```
npx serve .
python -m http.server 8000
```
PHP pages need Apache/Nginx with MySQL. Push to `main` deploys repo root to GitHub Pages (static only — deploys the client-side version).

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

PHP pages use MySQL-backed sessions (`includes/config.php`, `fscrm_users`). HTML pages use `fscrm_auth` boolean in localStorage via `router.js`. Demo login: `admin@demo.com` / `demo123`.

### Entity navigation

Now **dual**: `localStorage` (`fscrm_currentCustomerId`) AND URL params (`?id=N`) in `.php` pages. Both may be set simultaneously.

### Domain model

`customers → services → tasks`. Recurrence loop (`completeTask()` → `getNextDueDate()` → new pending task) is **still client-side** in `app.js:36`. Data CRUD moved to PHP (`api/*.php`). `orders` are a separate workflow (see `pages/orders.php`).

### Recurrence inconsistency

`repeatFrom: 'last_service'` in seed data (`seed.js`/`api/seed.php`) matches neither branch in `getNextDueDate()` (`'last-done'` / `'fixed-schedule'`). Falls through silently. Reconcile all three if touching recurrence.

## Conventions

- **ES5 only** in core JS files — `var`/`function`, no `let`/`const`/arrows. Ships un-transpiled to browsers.
- **All keys/tables prefixed `fscrm_`**. IDs from `getNextId()` (client-side) or MySQL auto-increment (PHP).
- **Adding a persisted key**: add to both `seedData.init()` (seed.js) AND `api/seed.php`.
- **Cache-bust**: `?v=20260601a` on all CSS/JS includes. Bump when deploying fresh assets.
- **Styling**: custom mobile-first system in `custom.css` + Tailwind CDN. Use `.btn`, `.card`, `.data-table`, `.badge-*`, `.modal-*`, `.toast`. Brand: `#1DB954`, sidebar: `#0B1E3D`.
- **HTML pages still reference `api.js`** which does not exist — stale from migration.
