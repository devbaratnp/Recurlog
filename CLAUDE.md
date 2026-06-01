# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Recurlog is a **field service management CRM that runs entirely client-side** — vanilla ES5 JavaScript, no build step, no server, no API. All state lives in `localStorage` under `fscrm_*` keys. Libraries (Tailwind, Lucide, Chart.js, Poppins) load from CDN at runtime. Open any HTML file in a browser and it works.

`README.md` is an extensive reference for screens, data model, design tokens, and the function index — read it for detail. This file covers what the README doesn't make obvious.

## Commands

There is **no build, lint, or test tooling** — don't look for `package.json`, npm scripts, or a test runner; none exist.

```bash
# Run locally (any static server works)
npx serve .
python -m http.server 8000
# or just open index.html directly in a browser
```

Deployment is automatic: pushing to `main` triggers `.github/workflows/static.yml`, which deploys the whole repo root to GitHub Pages.

## Architecture

**Script load order matters and is fixed.** Every page loads scripts in this order (see any file in `pages/`):
1. `seed.js` — defines `window.SEED_DATA` and `window.seedData`
2. `router.js` — auth guard + navigation helpers
3. `app.js` — all data accessors, recurrence engine, reports, UI helpers

`app.js` calls `seedData.init()` at module load (app.js:833), which seeds localStorage **only if `fscrm_seeded` is not set**. `sidebar.js` is loaded separately in `<head>` because `injectSidebar()` uses `document.write()` for synchronous injection.

**Everything is global and synchronous.** Functions are attached to `window.*` and called directly from inline `<script>` blocks in each page. No modules, no async, no callbacks. Data flow on every interaction: read localStorage → mutate → write localStorage → re-render from localStorage.

**Auth** is a single localStorage boolean (`fscrm_auth`). `router.js` `initRouter()` redirects to `login.html` if not authed, or to `dashboard.html` if authed and on login. Demo login: `admin@demo.com` / `demo123`.

**Entity navigation** passes IDs via localStorage, not URL params: `goToCustomer(id)` writes `fscrm_currentCustomerId` then navigates; detail pages read that key back. Same pattern for staff, service, task.

### Domain model

`customers → services → tasks`. A service is one-time or recurring. Saving a service generates the first `pending` task. Completing a recurring task (`completeTask()`, app.js:164) computes the next due date via `getNextDueDate()` and creates the next pending task — this is the core recurrence loop. One-time tasks just complete. `orders` are a separate, simpler one-off workflow (see `pages/orders.html`).

### Recurrence engine — known data inconsistency

`getNextDueDate()` (app.js:516) branches on `recurrence.repeatFrom`, checking for the values `'last-done'` and `'fixed-schedule'`. **But seeded recurring services store `repeatFrom: 'last_service'`** (seed.js:206), which matches neither branch and silently falls through to the fallback (`lastCompletedDate || previousScheduledDate || today`). The service-add form (`pages/service-add.html`) writes `'last-done'`/`'fixed-schedule'`. If you touch recurrence logic, reconcile these three spellings rather than assuming they agree.

## Conventions

- **ES5 only** — `var`, `function`, no arrow functions or `let`/`const` in the core JS files (a few spread operators exist, e.g. app.js:258, but match the surrounding ES5 style). No transpilation runs, so anything you write ships as-is to the browser.
- **All persisted keys are prefixed `fscrm_`** (`fscrm_customers`, `fscrm_tasks`, `fscrm_orders`, `fscrm_service_types`, `fscrm_next_id`, `fscrm_seeded`, etc.). IDs come from the global counter `getNextId()` (app.js:21).
- **Resetting data**: Settings → Reset Demo Data clears all `fscrm_*` keys and reloads, re-triggering the seed. When adding a new persisted key, add it to both `seedData.init()` (seed.js) and the reset logic.
- **Styling** is a custom mobile-first design system in `assets/css/custom.css` plus Tailwind utility classes via CDN. Use the existing component classes (`.btn`, `.card`, `.data-table`, `.badge-*`, `.modal-*`, `.toast`) rather than introducing new patterns. Brand color is `#1DB954` (green), sidebar navy is `#0B1E3D`.
- **The README can lag the code** — `orders`, `fscrm_service_types`, and `index.html` redirecting to `dashboard.html` (not `login.html`) all exist in code but predate or differ from the README narrative. Trust the source for behavior.
