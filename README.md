# Recurlog — Field Service Management CRM

A mobile-first, offline-capable field service management web application for scheduling, tracking, and managing recurring and one-time service tasks. Built for field service businesses with multiple technicians serving residential and commercial customers.

**Live demo credentials:** `admin@demo.com` / `demo123`
**Staff demo:** `{firstname}@demo.com` / `demo123` (e.g. `ramesh@demo.com`)

---

## Table of Contents

- [Overview](#overview)
- [Screens & Features](#screens--features)
- [Data Model](#data-model)
- [Recurrence Engine](#recurrence-engine)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Build & Deployment](#build--deployment)
- [Seed Data](#seed-data)
- [Design System](#design-system)
- [API v1 (REST)](#api-v1-rest)
- [PWA & Push Notifications](#pwa--push-notifications)
- [License](#license)

---

## Overview

Recurlog is a field service management solution in **dual-mode**: `.html` pages are 100% client-side (ES5, localStorage), `.php` pages use a PHP/MySQL backend. Both coexist — `.php` is the live target, `.html` remains as fallback. See `AGENTS.md` for the full architecture guide.

### Recent additions (June 2026)

- **v1 REST API** — JWT-authenticated (HS256) RESTful API at `api/v1/` with pagination, search, and CRUD for all entities. Access token: 7 days, refresh: 30 days. Used by the React Native mobile app.
- **Staff reassignment** — Change assignee on tasks, services, and orders via a reusable modal. Tracks history in `fscrm_assignment_history`.
- **Hard delete** — Admin users can permanently delete tasks (all 3 views), orders, and customers (with cascade of child records). Delete buttons with confirmation modals.
- **Push notifications** — Web Push (VAPID) + Expo Push for mobile. Sound system for native notifications.
- **PWA support** — Service worker + manifest.json + install prompt. Offline-cached shell for login, CSS, and JS.
- **Task detail & edit pages** — Dedicated `task-detail.php` (full info with customer, schedule, assignment, service, completion details, signature) and `task-edit.php` (update title, status, date, assignee, notes).
- **SweetAlert2-style toasts** on all CRUD actions across the app.

### Who it's for

- Small field service businesses (HVAC, plumbing, repair, maintenance)
- Service teams with 2-20 technicians
- Companies managing recurring maintenance contracts
- Demo / pitch prototypes for CRM software

### Key workflows

1. **Register a customer** — name, address, contact, equipment types (RO, AC, TV, Refrigerator, Washing Machine, Other)
2. **Create a service** — assign a category (Annual Maintenance, Filter Change, Repair, Deep Cleaning, Installation, Inspection), choose one-time or recurring, set interval, assign staff
3. **Auto-generate tasks** — on save, the system creates a pending task for the first scheduled date
4. **Complete tasks** — mark complete with date and notes; if the service is recurring, the next occurrence is auto-scheduled based on the recurrence engine
5. **Track performance** — view reports by recurring vs. one-time, staff-wise, category-wise with completion rates and weekly charts

---

## Screens & Features

### Login (`pages/login.php`)

Server-side authentication via PHP sessions & `fscrm_users` table. Pre-filled demo credentials in placeholder. Left panel shows brand identity. Rate-limited (5 attempts / 5 min per IP). CSRF protected. Google sign-in is UI placeholder. PWA install button on supporting browsers.

### Dashboard (`pages/dashboard.php`)

- **4 stat cards** — Total Customers, Tasks Today, Missed Tasks, Active Staff
- **Today's Schedule** — table of today's tasks with inline "Complete" buttons
- **Recent Activity** — last 8 notifications with relative timestamps
- **To Do** — action cards for adding services/orders, managing staff, viewing reports
- **Skeleton loading** animation on page transitions
- **Responsive** — stat cards switch from 4 columns on desktop to 2 on mobile

### Staff Portal (`pages/staff-dashboard.php`)

Mobile-first dashboard for staff users (non-admin). Header with logout, stats bar (Completed Today, Pending, Missed), Today's Tasks with inline Complete button opening a modal (notes, signature pad, received by/contact), Upcoming tasks, Assigned orders, Recent activity feed. Bottom nav with Dashboard, Tasks, Back.

### Customers (`pages/customers.php`)

- Searchable table with name, address, contact, and service-type badges
- Equipment color coding: RO (emerald), TV (blue), Refrigerator (cyan), AC (orange), Washing Machine (purple), Other (gray)
- Edit, Delete (with cascade confirmation), and View buttons
- Empty state with helpful message when no results match search
- 50-record pagination

### Add Customer (`pages/customer-add.php`)

Form with:
- Name, Address, Contact Number (prefixed with +977-), Area/Locality
- Service-for chip selector (RO, TV, Refrigerator, AC, Washing Machine, Other) — toggle on/off with brand color
- Interactive **location map** — draggable pin on a CSS grid map; updates lat/lng read-only fields
- Input validation with toast error messages
- CSRF protected POST handler
- On save, auto-generates a notification

### Customer Detail (`pages/customer-detail.php`)

- Customer information card (name, phone, address, service chips)
- **Services table** — title, category name, type badge, next due date, status, assigned staff
- **Tasks table** — all customer tasks sorted by date descending with status pill
- "Add Service" button linking to service-add
- Routes via `?id=N` URL param or `fscrm_currentCustomerId` localStorage

### Add Service (`pages/service-add.php`)

Most feature-rich form:
- **Customer dropdown** — includes "+ Add New Customer" option (redirects to customer-add)
- **Service-for chip** — single-select (RO, TV, etc.)
- **Category dropdown** — populated from seed categories
- **Service type toggle** — One Time / Recurring toggle buttons
- **Recurrence section** (shown when Recurring selected):
  - Repeat Every: number + unit selector (Days/Weeks/Months/Years)
  - Repeat From: radio buttons (Last Done Date | Fixed Schedule)
  - Live preview text
- First Scheduled Date picker (defaults to today)
- Staff assignment dropdown, problem/notes textarea
- Transaction-based POST handler with CSRF protection

### Tasks (`pages/tasks.php`)

Hash-routed tabs (`#today`, `#upcoming`, `#missed`) with **delete support**:
- **Today** — tasks matching current date, pending/completed/missed
- **Upcoming** — future pending tasks
- **Missed** — all tasks with status=missed

Each task card shows:
- Title, customer name, staff avatar+name, relative date
- Status pill (pending=amber, completed=green, missed=red)
- "Mark Complete" (pending only) and **"Delete"** buttons with confirmation modal

**Mark Complete Modal:**
- Completion date picker, notes field
- On confirm, marks complete, computes next due date for recurring, generates notification

Search filters across title, customer name, staff name. 50-record pagination.

### One-Time Tasks (`pages/onetime-task.php`) & Recurring Tasks (`pages/recurring-task.php`)

These pages list tasks by type with:
- Client-side search filter by title, customer, staff
- Table with task title, customer, assigned staff, scheduled date, status pill, edit button, service type inline-add
- **Delete button** with confirmation modal (SweetAlert2-style toasts)
- Edit: links to `task-edit.php?id=N`

### Task Detail (`pages/task-detail.php`)

Full drill-down page for any task:
- Header with task title, ID, status pill
- **Customer** card with avatar, phone link, address
- **Schedule** card with scheduled date, completed date, first scheduled date
- **Assignment** card with staff name, phone, "Change Assignee" button
- **Service** card with service type, category, recurring badge
- **Details** section for problem/notes
- **Completion Details** section (completed_by, received_name, received_contact, signature image)
- Works for both admin and staff portal

### Task Edit (`pages/task-edit.php`)

Form to update task title, status (pending/completed/missed), scheduled date, assigned staff, and notes. POST handler with CSRF protection and transaction.

### Staff (`pages/staff.php`)

Card grid showing:
- Avatar (via ui-avatars.com), name, phone
- Active task count + completion rate percentage with progress bar
- "Has Login" / "No Login" badge (via LEFT JOIN on `fscrm_users.staff_id`)
- "Set Password" / "Reset Password" button (opens modal with email + password fields)
- "View Profile" link
- Delete with `fscrm_users` cleanup

### Staff Detail (`pages/staff-detail.php`)

- Profile card with avatar, name, phone
- **4 stat boxes** — Total Tasks, Completed, Missed, Completion Rate
- **Assigned Tasks table** — all tasks with title, customer, date, status

### Reports (`pages/reports.php`)

Four report sections powered by Chart.js with **color-coded rows**:

1. **Recurring Tasks** — all recurring tasks (All/Missed/Today/This Month)
2. **One-Time Tasks** — same filters
3. **Staff-Wise** — dropdown → task stats + chart
4. **Category-Wise** — dropdown → task stats + chart

Each section: filter tabs, stats cards, bar chart (4-week completions), detail table with status-based row colors (completed=green, missed=red, pending=amber, in-progress=blue, cancelled=gray).

### Notifications (`pages/notifications.php`)

- Chronological list (newest first) with type-based icons and colors
- Unread items have brand left border + dot indicator
- Unread count badge in sidebar/header
- "Mark All Read" button
- Notification types: `task_completed` (green), `task_missed` (red), `service_added` (blue), `customer_added` (green)

### Orders (`pages/orders.php`)

- Order cards with customer name, problem description, service type, priority badge (Urgent/Normal), status badge, assigned staff, dates
- **Assign** (pending orders), **Complete** (assigned orders), **Cancel**, **Delete** buttons with confirmation modals
- 50-record pagination

### Localities (`pages/localities.php`)

Full CRUD page (admin only) for `fscrm_localities`. Server-side POST handlers (add/edit/delete). Linked from sidebar between Staff and Daybook.

### Settings (`pages/settings.php`)

- **Profile card** — admin avatar, name, email, "Edit Profile" (placeholder)
- **Preferences** — notifications toggle, language, light/dark theme (UI only)
- **Data** — Export CSV/PDF (placeholder), **Reset Demo Data** (confirmation → clears all `fscrm_*` localStorage keys → reloads)
- **Account** — Logout button with session cleanup
- Footer with version

---

## Data Model

All data stored in `localStorage` under `fscrm_*` keys:

### `fscrm_customers`
```json
{
  "id": 1,
  "name": "Sharma Family",
  "address": "Adarsh Nagar, Birgunj",
  "phone": "+977-9801234001",
  "servicesFor": ["RO", "Refrigerator"],
  "location": { "lat": 27.00, "lng": 84.87 }
}
```

### `fscrm_staff`
```json
{
  "id": 1,
  "name": "Ramesh Yadav",
  "phone": "+977-9812345001",
  "avatar": "https://ui-avatars.com/api/?name=Ramesh+Yadav&background=1DB954&color=fff&size=200",
  "activeTasks": 0
}
```

### `fscrm_categories`
```json
{ "id": 1, "name": "Annual Maintenance", "color": "#1DB954" }
```

Categories:
| ID | Name | Color |
|----|------|-------|
| 1 | Annual Maintenance | `#1DB954` green |
| 2 | Filter Change | `#0EA5E9` blue |
| 3 | Repair | `#F59E0B` amber |
| 4 | Deep Cleaning | `#8B5CF6` purple |
| 5 | Installation | `#EC4899` pink |
| 6 | Inspection | `#6366F1` indigo |

### `fscrm_services`
```json
{
  "id": 1,
  "customerId": 1,
  "categoryId": 2,
  "serviceFor": "RO",
  "title": "RO Filter Change",
  "isRecurring": true,
  "firstScheduledDate": "2026-03-06",
  "assignedTo": 1,
  "notes": "Filter replaced with new unit.",
  "recurrence": {
    "value": 30,
    "unit": "days",
    "repeatFrom": "last_service"
  }
}
```

For one-time services, `recurrence` is `null`.

### `fscrm_tasks`
```json
{
  "id": 1,
  "serviceId": 1,
  "customerId": 1,
  "title": "RO Filter Change",
  "status": "completed",
  "scheduledDate": "2026-03-06",
  "completedDate": "2026-03-06",
  "assignedTo": 1,
  "notes": "Filter replacement done.",
  "categoryId": 2
}
```

Status values: `pending`, `completed`, `missed`.

### `fscrm_notifications`
```json
{
  "id": 1,
  "text": "Ramesh Yadav completed RO Filter Change for Sharma Family. Next service: Mar 6, 2026",
  "type": "task_completed",
  "relatedId": 1,
  "isRead": false,
  "createdAt": "2026-03-06T00:00:00.000Z"
}
```

### `fscrm_sidebar_collapsed`
Boolean string: `"true"` or `"false"`.

### `fscrm_auth`
Boolean string: `"true"` when logged in.

### `fscrm_seeded`
Boolean string: `"true"` after seed data initializes. Prevents re-seeding.

---

## Recurrence Engine

Located in `assets/js/app.js:36` (`window.getNextDueDate`). Still client-side — not yet ported to PHP.

```javascript
getNextDueDate(service, lastCompletedDate, previousScheduledDate)
```

### Logic

1. If `repeatFrom === 'last-done'` and `lastCompletedDate` exists → base = last completed date
2. If `repeatFrom === 'fixed-schedule'` and `previousScheduledDate` exists → base = previous scheduled date
3. Fallback → base = first available date
4. Returns `addToDate(baseDate, value, unit)`

### `addToDate(dateStr, value, unit)`

- `days` → `setDate(getDate() + value)`
- `weeks` → `setDate(getDate() + value * 7)`
- `months` → `setMonth(getMonth() + value)`
- `years` → `setFullYear(getFullYear() + value)`

### Task completion and next-gen flow

When `completeTask()` is called:
1. Task is marked `completed` with optional notes
2. If the service is recurring:
   - `getNextDueDate()` computes the next date
   - A new `pending` task is created with that date
   - Notification generated: "[Staff] completed [Task] for [Customer]. Next service: [Date]"
3. If one-time: simply marks complete
4. Staff active counts are refreshed

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **HTML** | Semantic HTML5, viewport-fit for mobile notches |
| **CSS** | Custom design system (1290 lines), CSS custom properties |
| **CSS Framework** | Tailwind CSS (via CDN) |
| **Icons** | Lucide (via CDN, latest) |
| **Charts** | Chart.js (via CDN) |
| **Font** | Poppins (Google Fonts) |
| **JavaScript** | Vanilla JS (ES5), no transpilation needed |
| **Backend** | PHP 8+ (`.php` pages) |
| **Database** | MySQL 8 (`recurlog` DB, `fscrm_*` tables) |
| **Data (client)** | localStorage API (`fscrm_*` keys) |
| **Auth (PHP)** | PHP sessions, `fscrm_users` table |
| **Auth (HTML)** | localStorage boolean flag (`fscrm_auth`) |
| **REST API** | v1 JWT-authenticated (HS256) at `api/v1/` |
| **Push Notifications** | Web Push (VAPID) + Expo Push |
| **PWA** | Service worker (`sw.js`) + manifest.json |
| **Mobile App** | React Native (Expo SDK 54) at `mobile/` |

### Why no build step?

Client-side pages are zero-dependency — all libraries load from CDN at runtime. PHP pages need Apache/Nginx + MySQL. No npm, no webpack, no build tools regardless.

---

## Project Structure

```
```
Recurlog/
├── index.html                 # Entry point (splash → redirect to dashboard.php)
├── sw.js                      # Service worker (PWA offline caching)
├── manifest.json              # PWA manifest (standalone display, icons)
├── api/                       # PHP REST API endpoints
│   ├── config.php             # DB connection, JSON helpers, session auth
│   ├── helpers.php            # camelCase/snake_case field conversion
│   ├── auth.php               # Login/logout/session check
│   ├── customers.php          # Customer CRUD
│   ├── services.php           # Service CRUD
│   ├── tasks.php              # Task CRUD
│   ├── staff.php              # Staff CRUD
│   ├── categories.php         # Category CRUD
│   ├── notifications.php      # Notification CRUD
│   ├── orders.php             # Order CRUD
│   ├── localities.php         # Locality CRUD
│   ├── service_types.php      # Service type CRUD
│   ├── reassign.php           # Staff reassignment (POST, CSRF)
│   ├── seed.php               # Seed MySQL database
│   └── v1/                    # JWT-authenticated REST API (HS256)
│       ├── config.php         # JWT helpers, DB, rate limiting
│       ├── auth.php           # Login/refresh/me with JWT
│       ├── customers.php      # Customer CRUD + pagination + search
│       ├── services.php       # Service CRUD
│       ├── tasks.php          # Task CRUD
│       ├── staff.php          # Staff CRUD
│       ├── categories.php     # Category CRUD
│       ├── orders.php         # Order CRUD
│       ├── notifications.php  # Notification CRUD
│       ├── localities.php     # Locality CRUD
│       └── service_types.php  # Service type CRUD
├── includes/                  # PHP template parts
│   ├── config.php             # DB connection, auth helpers, session start
│   ├── header.php             # Auth guard, sidebar, unread badge, opens HTML
│   ├── footer.php             # Closes HTML, loads sidebar.js + app.js
│   ├── sidebar.php            # Nav links + bottom nav, user info
│   └── notification_helper.php# createNotification() DB helper
├── assets/
│   ├── css/
│   │   └── custom.css         # Mobile-first design system (1290 lines)
│   │                          # Design tokens, sidebar, buttons, cards,
│   │                          # forms, modals, tables, toasts, maps,
│   │                          # skeleton loading, utility classes
│   ├── js/
│   │   ├── app.js             # Shared JS helpers (389 lines)
│   │   │                      # Recurrence engine, navigation, UI helpers,
│   │   │                      # searchable dropdown, toast system,
│   │   │                      # staff reassignment modal, signature compression
│   │   ├── router.js          # Auth guard, navigation, skeleton (HTML only)
│   │   ├── seed.js            # Client-side seed data (349 lines)
│   │   │                      # 8 customers, 5 staff, 6 categories,
│   │   │                      # ~55 services, ~350+ tasks, ~25 notifs
│   │   └── sidebar.js         # Responsive sidebar (94 lines)
│   │                          # Mobile drawer, desktop collapse,
│   │                          # backdrop handling, resize listeners
│   └── icons/                 # PWA icon set (48x48 to 512x512)
├── scripts/
│   └── generate-icons.mjs     # PWA icon generation script
├── mobile/                    # React Native (Expo) mobile app
│   ├── App.tsx                # Entry point
│   ├── src/                   # 12 screens, store, API client, types
│   └── ...
├── docs/superpowers/          # Design specs & implementation plans
│   ├── specs/                 # Feature design documents
│   └── plans/                 # Implementation plans
└── pages/                     # PHP server-side pages (primary)
    ├── login.php              # Authentication
    ├── dashboard.php          # Admin dashboard with KPI cards
    ├── customers.php          # Customer list (delete + pagination)
    ├── customer-add.php       # Add/edit customer
    ├── customer-detail.php    # Customer profile with services/tasks
    ├── customer-report.php    # Printable customer report
    ├── service-add.php        # Add service with recurrence
    ├── tasks.php              # Task board (today/upcoming/missed + delete)
    ├── task-detail.php        # Full task detail with all metadata
    ├── task-edit.php          # Edit task (title, status, date, staff, notes)
    ├── onetime-task.php       # One-time task list with delete
    ├── recurring-task.php     # Recurring task list with delete
    ├── staff.php              # Staff directory with login management
    ├── staff-detail.php       # Staff profile
    ├── staff-dashboard.php    # Staff portal dashboard
    ├── reports.php            # Reports with Chart.js + color-coded rows
    ├── notifications.php      # Notification inbox
    ├── settings.php           # Settings & profile
    ├── daybook.php            # Daily agenda
    ├── orders.php             # Order CRUD with signature pad + delete
    ├── localities.php         # Locality CRUD (admin only)
    ├── logout.php             # PHP session destroy + redirect
    └── task-complete-ajax.php # AJAX task completion endpoint
```
```

---

## Getting Started

### Option 1: Direct open (client-side only)

```
1. Open index.html in any modern browser
2. Login with admin@demo.com / demo123
3. Seed data loads automatically on first visit
```
HTML pages work offline. PHP pages will not function without a server.

### Option 2: Local static server (client-side only)

```bash
npx serve .
# or
python -m http.server 8000
# then open http://localhost:8000
```

### Option 3: PHP + MySQL (full experience)

Requirements: Apache/Nginx with PHP 8+, MySQL 8.
```
1. Create MySQL database `recurlog`
2. Run migration.sql: `SOURCE /path/to/migration.sql;`
3. Point your web server to repo root
4. Visit http://localhost/pages/login.php
5. Login: admin@demo.com / demo123
```
Or use Settings > Reset Demo Data to seed after migration.

### Option 4: Docker

```dockerfile
FROM nginx:alpine
COPY . /usr/share/nginx/html
EXPOSE 80
```

---

## Build & Deployment

### GitHub Pages (static client-side)

```bash
git push origin main
# .github/workflows/static.yml deploys repo root to GitHub Pages
```
PHP files won't execute on Pages (static only). Deploys client-side `.html` fallback.

### Production PHP server

See `PRODUCTION.md` for the full production checklist. Key steps:
1. Set environment variables: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
2. Run `migration.sql` to create tables
3. Configure HTTPS (auto-detected by `session.cookie_secure`)
4. Change demo passwords before going live

---

## Seed Data

Located in `assets/js/seed.js`. Generates realistic demo data for a fictional Birgunj, Nepal service business.

### Seed contents

| Entity | Count | Details |
|--------|-------|---------|
| Customers | 8 | Residential & commercial (Sharma Family, Gupta Electronics, Hotel Makalu, Patel Residence, Singh Niwas, Modern Pharmacy, Khanal House, Birgunj Sweets) |
| Staff | 5 | Ramesh Yadav, Suresh Thakur, Bikash Sah, Anita Devi, Manoj Kumar — each linked to `fscrm_users` for staff portal login |
| Staff Users | 5 | `{firstname}@demo.com` / `demo123` — e.g. `ramesh@demo.com` |
| Categories | 6 | Annual Maintenance, Filter Change, Repair, Deep Cleaning, Installation, Inspection |
| Services | ~55 | Mix of recurring (30d, 45d, 60d, 90d intervals) and one-time |
| Tasks | ~350+ | Auto-generated from services across a 90-day window (75 days past, 15 days future) |
| Orders | ~15 | With statuses (pending, assigned, completed, cancelled) and signature data |
| Notifications | ~25 | From recent tasks + system events |

### Completion probabilities per staff

- Ramesh Yadav (ID 1): 90%
- Anita Devi (ID 4): 90%
- Bikash Sah (ID 3): 80%
- Suresh Thakur (ID 2): 80%
- Manoj Kumar (ID 5): 70%

### Note templates

Each category has 5 realistic note templates used for both service notes and task completion notes.

### Re-seeding

To reset all data, go to Settings → Data → Reset Demo Data. This clears all `fscrm_*` localStorage keys and reloads the page, which triggers seed data initialization again.

---

## Design System

The custom CSS (`assets/css/custom.css`, 1290 lines) is a complete mobile-first design system:

### Design Tokens

```css
:root {
  --color-primary: #1DB954;    /* Brand green */
  --color-navy: #0B1E3D;       /* Dark navy (sidebar bg) */
  --color-amber: #F59E0B;      /* Warning */
  --color-danger: #EF4444;     /* Error/missed */
  --color-info: #3B82F6;       /* Info */
  --sidebar-width: 240px;
  --sidebar-collapsed-width: 60px;
  --header-height: 56px;
  --bottom-nav-height: 56px;
}
```

### Responsive Strategy

| Breakpoint | Layout |
|------------|--------|
| < 768px (mobile) | Bottom nav, sidebar as drawer overlay, tables as stacked cards |
| >= 768px (tablet+) | Sidebar persistent, sticky header, standard tables |

### Component Classes

- `.btn` / `.btn-sm` / `.btn-md` / `.btn-lg` — Button system with `.btn-primary`, `.btn-secondary`, `.btn-ghost`, `.btn-danger` variants
- `.card` / `.card-header` / `.card-body` — Card component with hover shadow
- `.form-input` / `.form-select` / `.form-textarea` / `.form-label` — Form elements with brand focus ring
- `.badge` / `.badge-pending` / `.badge-completed` / `.badge-missed` / `.badge-info` — Status badges
- `.data-table` — Responsive table (stacked on mobile, standard on desktop with `data-label` attribute for pseudo-elements)
- `.modal-overlay` / `.modal-content` — Bottom sheet on mobile, centered on desktop
- `.toast-container` / `.toast` — Slide-in toast notifications with success/error/info variants
- `.sidebar` — Fixed sidebar with `.collapsed` state, mobile drawer with `.open` class
- `.bottom-nav` — Fixed bottom tab bar (mobile only)
- `.empty-state` — Centered empty state with icon, message, optional CTA
- `.skeleton` — Shimmer loading animation
- `.map-grid` / `.map-pin` — Draggable pin map with CSS grid background
- `.brand-glow` — Green box-shadow glow effect for primary CTAs

### Animations

- `@keyframes fadeIn` — For page content entrance (600ms, 12px translateY)
- `@keyframes modalSlideUp` — For bottom sheet modal (250ms)
- `@keyframes slideIn` / `slideOut` — For toast notifications (300ms)
- `@keyframes shimmer` — For skeleton loading (1.5s infinite)
- `@keyframes spin` — For splash loading spinner (700ms)

---

## JavaScript Architecture

### Module loading — differs per page type

**HTML pages** (loads 4 scripts in order):
```html
<script src="../assets/js/sidebar.js"></script>  <!-- In <head> via document.write -->
<script src="../assets/js/seed.js"></script>      <!-- window.SEED_DATA + seedData.init() -->
<script src="../assets/js/router.js"></script>    <!-- Auth guard + navigation -->
<script src="../assets/js/app.js"></script>        <!-- Recurrence engine, UI helpers -->
```

**PHP pages** (loads 2 scripts via `includes/footer.php`):
```html
<script src="../assets/js/sidebar.js"></script>
<script src="../assets/js/app.js"></script>
```
No `seed.js` or `router.js` — auth is server-side via PHP sessions. `seed.js` and `router.js` are only loaded by `.html` pages.

### Key global functions (current `app.js`, 389 lines)

| Function | Line | Purpose |
|----------|------|---------|
| `todayISO()` | 3 | Returns today as YYYY-MM-DD |
| `addToDate()` | 22 | Date math (days/weeks/months/years) |
| `getNextDueDate()` | 36 | Recurrence calculation |
| `showToast()` | 52 | Toast notification |
| `renderStatusPill()` | 79 | Status badge HTML |
| `formatDate()` | 89 | Formats "Mar 6, 2026" |
| `formatRelative()` | 102 | "Today", "Tomorrow", "3 days ago" |
| `goToCustomer()` | 129 | Navigate to customer + set localStorage |
| `goToStaff()` | 134 | Navigate to staff + set localStorage |
| `goToTask()` | 139 | Navigate to task + set localStorage |
| `goToService()` | 144 | Navigate to service + set localStorage |
| `showLoadingSkeleton()` | 149 | Skeleton overlay |
| `playNotificationSound()` | 278 | Web Audio API beep for notifications |
| `buildSearchableDropdown()` | 181 | Reusable dropdown component |
| `reassignStaff()` | 297 | Staff reassignment modal + API call |
| `compressSignature()` | — | Client-side JPEG downsampling (max 400px, q 0.6) |

Data CRUD (`getCustomers`, `getServices`, `getTasks`, `completeTask`, etc.) moved from `app.js` to PHP backend (`api/*.php`).

### Data flow — HTML pages (client-side)

```
User Action → Page Script → window.*() → localStorage → Re-render
```
Everything synchronous, no modules.

### Data flow — PHP pages (server-side)

```
Request → includes/config.php (auth) → MySQL query → Server renders HTML
         → window.__VARIABLE = <?= json_encode(...) ?> → JS manipulates DOM
```
AJAX endpoints (`api/*.php`) handle mutations via JSON.

---

---

## API v1 (REST)

A JWT-authenticated REST API lives at `api/v1/`. Used by the React Native mobile app and third-party integrations.

### Auth

| Endpoint | Method | Description |
|----------|--------|-------------|
| `auth.php` | POST | Login — returns `token` (7d) + `refreshToken` (30d) |
| `auth.php?action=refresh` | POST | Exchange refresh token for new tokens |
| `auth.php?action=me` | GET | Get current user from Bearer token |

### CRUD Endpoints

All require `Authorization: Bearer <token>` header.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `{entity}.php` | List (paginated, searchable) |
| GET | `{entity}.php?id=N` | Single record |
| POST | `{entity}.php` | Create |
| PUT | `{entity}.php?id=N` | Update |
| DELETE | `{entity}.php?id=N` | Delete |

Entities: `customers`, `services`, `tasks`, `orders`, `staff`, `categories`, `localities`, `service_types`, `notifications`.

### Pagination

All GET list endpoints accept `page` (default 1), `per_page` (default 50, max 200), `search` (filters across relevant fields). Response includes `pagination: { page, perPage, total, totalPages }`.

### Filters

- `services.php`: `customer_id`, `category_id`, `is_recurring`
- `tasks.php`: `status`, `customer_id`, `assigned_to`, `service_id`, `scheduled_date`, `start_date`, `end_date`
- `orders.php`: `status`, `customer_id`, `priority`
- `notifications.php`: `is_read`

### Data Format

Responses use camelCase. Requests accept camelCase or snake_case. Special fields: `servicesFor` (array), `location` ({lat, lng}), `recurrence` ({value, unit, repeatFrom}).

### Error Codes

| Code | HTTP | Meaning |
|------|------|---------|
| `UNAUTHORIZED` | 401 | Missing/invalid Bearer token |
| `TOKEN_EXPIRED` | 401 | JWT expired or invalid signature |
| `FORBIDDEN` | 403 | Insufficient role |
| `NOT_FOUND` | 404 | Resource not found |
| `VALIDATION_ERROR` | 400 | Missing/invalid fields |
| `RATE_LIMITED` | 429 | Too many requests (IP-based, 10 req/60s) |
| `DB_ERROR` | 500 | Database failure |

See `api-endpoint.md` for the full API reference.

---

## PWA & Push Notifications

### Progressive Web App

- **Service Worker** (`sw.js`): Caches login page, CSS, JS, and app icon on install. Cache-first strategy for static assets, network-first for everything else.
- **Manifest** (`manifest.json`): Standalone display, navy background, brand green theme, icon set (48x48 to 512x512).
- **Install button** on login page for supporting browsers.
- Icon set generated via `scripts/generate-icons.mjs`.

### Push Notifications

- **Web Push**: Uses VAPID keys (`vendor/` includes a custom VAPID helper for PHP 8.1+ compatibility). Triggers push notifications on task assignment/completion.
- **Expo Push**: For the React Native mobile app. Server sends notifications via Expo Push API.
- **Sound system**: `playNotificationSound()` in `app.js:278` uses Web Audio API to play a short beep on new notifications.

---

## Future / TODO

- [ ] CSV/PDF export (UI placeholders exist)
- [ ] Dark mode theme (UI toggle exists)
- [ ] Customer geo-map view with clustering
- [ ] Multi-language i18n (Nepali + English)
- [ ] Calendar view for task scheduling
- [ ] Invoice generation from completed tasks
- [ ] Staff CRUD (add/edit/delete) admin page
- [x] API backend integration (PHP/MySQL, dual-mode with client-side fallback)
- [x] Staff portal with login (5 staff users)
- [x] PWA + service worker
- [x] Push notifications (Web Push + Expo Push)
- [x] v1 REST API with JWT auth
- [x] Staff reassignment with history tracking
- [x] Hard delete with cascade for admin users
- [x] Task detail + edit pages
- [x] Locality CRUD management
- [x] Color-coded report rows by status

---

## License

MIT
