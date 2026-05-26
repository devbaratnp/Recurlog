# Recurlog Mobile App — Design Spec

**Date:** 2026-05-22  
**Project:** Recurlog — Field Service Management CRM  
**Platform:** React Native (Expo managed workflow)  
**Target devices:** iOS + Android (mobile-first)

---

## 1. Design System

### 1.1 Color Palette

```
Brand   #1DB954 (green)     — Primary actions, active states
Navy    #0B1E3D             — Headings, primary text
Amber   #F59E0B             — Warning, pending status
Danger  #EF4444 (red)       — Errors, missed status
Info    #3B82F6 (blue)      — Informational, links
Gray    50–900 scale        — UI chrome, secondary text
White   #FFFFFF             — Cards, backgrounds
```

### 1.2 Typography

- **Font:** Poppins (system sans-serif fallback)
- **Weights:** 400 Regular, 500 Medium, 600 Semibold, 700 Bold
- **Size scale:** xs(12), sm(14), base(16), lg(18), xl(20), 2xl(24), 3xl(30), 4xl(36)

### 1.3 Spacing & Borders

- **Spacing:** 4/8/12/16/20/24/32/40/48
- **Border radius:** sm(4), md(8), lg(12), xl(16), full(9999)
- **Shadows:** light (elevation 1-2) on cards, medium (elevation 4-8) on modals/buttons

---

## 2. Shared UI Components (8)

### 2.1 ScreenWrapper
- Wraps content in SafeAreaView (top edges) + gray background
- Used by every screen

### 2.2 Card
- White container, 1px gray-200 border, lg border radius, light shadow
- Optional `noPadding` prop for custom layouts

### 2.3 Button
- Variants: `primary` (brand bg), `secondary` (white + border), `ghost`, `danger`
- Sizes: `sm` (32px), `md` (44px), `lg`
- Loading state shows ActivityIndicator; disabled at 0.5 opacity
- `fullWidth` prop for stretched buttons

### 2.4 StatusPill
- Colored pill based on status: `pending` (amber), `completed` (green), `missed` (red)
- Uses `getStatusColor` / `getStatusBg` helpers

### 2.5 Badge
- Small colored label pill
- Variants: `brand`, `info`, `amber`, `gray`

### 2.6 ServiceChip
- Colored chip for service types (RO, TV, AC, etc.)
- Colors mapped from `getCategoryColor` helper per service type

### 2.7 EmptyState
- Centered icon (72px), optional title, message, optional action button
- Used across list screens when data is empty

### 2.8 Toast (via context)
- `ToastProvider` wraps the app; `useToast()` returns `showToast(msg, type)`
- Slides up from bottom, auto-dismisses after 2.5s
- Types: `success` (green), `error` (red), `info` (blue)

---

## 3. Navigation Structure

```
Root (Stack)
├── index              → Splash → auto-redirect (dashboard or login)
├── login              → Sign-in form
├── (tabs)             → Bottom tab navigator
│   ├── dashboard      → Stats + today's schedule + activity
│   ├── customers      → Customer list + search
│   ├── tasks          → Task list with Today/Upcoming/Missed tabs
│   └── staff          → Staff directory grid
├── customers/[id]     → Customer detail (info, services, tasks)
├── customers/add      → New customer form
├── services/add       → New service form with recurrence
├── staff/[id]         → Staff detail (stats, assigned tasks)
├── notifications      → Notification list with unread badge
└── settings           → Profile, preferences, reset, logout
```

---

## 4. Screen Specifications

### 4.1 Splash (`index.tsx`)
- Navy background, centered logo, Recurlog + tagline text, spinner
- On mount: check auth state → redirect to `/(tabs)/dashboard` or `/login`

### 4.2 Login (`login.tsx`)
- Navy hero section with logo, white card form
- Email + password fields, forgot password link, Sign In button
- Google sign-in divider (placeholder)
- Calls `AuthContext.login()` → toast + redirect on success

### 4.3 Dashboard (`(tabs)/dashboard.tsx`)
- Header with notification bell → `/notifications`
- 2x2 stat grid: Total Customers, Tasks Today, Missed Tasks, Active Staff (tappable → respective tab)
- Quick actions row: Add Customer, Add Service
- Today's Schedule card: task rows with StatusPill + "Complete" button
- Recent Activity card: dot-colored timeline list
- Bottom sheet modal for marking tasks complete (date + notes)

### 4.4 Customers (`(tabs)/customers.tsx`)
- Header with Add button → `/customers/add`
- Search input filtering by name
- Customer cards showing name, address, phone, service chips, View button
- Each card → `/customers/[id]`

### 4.5 Customer Detail (`customers/[id].tsx`)
- Back button + name header
- Customer Info card: name, phone, address, area, services chips + active badge
- Services section: card per service (title, category, recurring/one-time badge, next due, staff). "Add Service" button
- Tasks section: card per task (title, date, staff, StatusPill)

### 4.6 Add Customer (`customers/add.tsx`)
- Back button + title
- Form: name, address, area, phone (+977- prefix), service type chips (multi-select), map placeholder, lat/lng fields
- Cancel + Save Customer buttons
- Validates all required fields before save

### 4.7 Tasks (`(tabs)/tasks.tsx`)
- Segmented tab bar: Today / Upcoming / Missed
- Search input filtering across title, customer, staff
- Task cards with title, customer, staff, date, StatusPill
- Pending tasks show "Mark Complete" button → bottom sheet modal

### 4.8 Services Add (`services/add.tsx`)
- Customer selector (chip grid), service type chips, problem description
- Category selector, one-time/recurring toggle
- Recurrence section: interval value + unit (days/weeks/months/years), "repeat from" radio (last done / fixed schedule), preview text
- First scheduled date, staff assignment chip grid, notes
- Create Service button

### 4.9 Staff (`(tabs)/staff.tsx`)
- Grid/column layout (responsive: 1 col < 500px, 2 cols >= 500px)
- Card per staff: avatar (initials), name, phone, stats (active/done/missed), completion progress bar
- "View Profile" link → `/staff/[id]`

### 4.10 Staff Detail (`staff/[id].tsx`)
- Back button + name
- Profile card: avatar, name, phone
- Stats grid: Total Tasks, Completed, Missed, Rate %
- Assigned Tasks section: cards with title, customer, date, StatusPill

### 4.11 Notifications (`notifications.tsx`)
- Back button, title with unread count badge, "Mark All Read" button
- Notification cards: colored icon per type, text, relative time, unread dot indicator

### 4.12 Settings (`settings.tsx`)
- Profile card: avatar (initials), name, email, Edit Profile button (placeholder)
- Preferences: notification toggle, language, light/dark theme toggle
- Data: CSV/PDF export (placeholder), Reset Demo Data with confirmation modal
- Account: Logout button
- Footer: version string

---

## 5. Data Layer (API)

### 5.1 Current state (prototype)
- `src/lib/api.ts` defines typed API client methods for all endpoints
- Screens use inline `fetch()` with mock fallbacks on failure
- `src/lib/auth.tsx` uses localStorage for mock persistence

### 5.2 Target state (production)
- Connect `api.ts` to live PHP backend at `https://recurlog.isoftro.com/backend/api/`
- Auth: POST `/auth/login` → receive token → store in SecureStore (expo-secure-store)
- All requests: attach `Authorization: Bearer <token>` header
- Replace mock data with real API responses

### 5.3 API endpoints

```
POST   /auth/login          → { token, user }
GET    /dashboard           → dashboard stats + today tasks + activity
GET    /customers           → customer list
POST   /customers           → create customer
GET    /customers/:id       → customer detail + services + tasks
GET    /services            → services (optional ?customerId=)
POST   /services            → create service
GET    /tasks               → tasks (optional ?tab=&customerId=&staffId=)
POST   /tasks/:id/complete  → mark task complete
GET    /staff               → staff list
GET    /staff/:id           → staff detail + stats + tasks
GET    /categories          → service categories
GET    /notifications       → notification list
POST   /notifications/read-all → mark all read
```

---

## 6. Key Design Decisions

1. **No external UI library** — all components hand-built for consistent look matching admin panel
2. **Context for auth + toast** — avoids prop drilling for global concerns
3. **Mock data fallback** — each screen works offline with demo data
4. **Expo Router file-based routing** — matches project structure visually
5. **Bottom tabs for primary navigation** — standard mobile pattern
6. **Modal bottom sheets for task completion** — keeps context visible
7. **Responsive staff grid** — adapts to tablet widths

---

## 7. File Structure

```
mobile/
├── app/
│   ├── _layout.tsx              → Root layout (SafeArea + Auth + Toast + Stack)
│   ├── index.tsx                → Splash / auth gate
│   ├── login.tsx                → Sign-in screen
│   ├── notifications.tsx        → Notifications list
│   ├── settings.tsx             → Settings screen
│   ├── (tabs)/
│   │   ├── _layout.tsx          → Tab navigator (4 tabs)
│   │   ├── dashboard.tsx
│   │   ├── customers.tsx
│   │   ├── tasks.tsx
│   │   └── staff.tsx
│   ├── customers/
│   │   ├── [id].tsx             → Customer detail
│   │   └── add.tsx              → Add customer form
│   ├── services/
│   │   └── add.tsx              → Add service form
│   └── staff/
│       └── [id].tsx             → Staff detail
├── src/
│   ├── components/
│   │   ├── Badge.tsx
│   │   ├── Button.tsx
│   │   ├── Card.tsx
│   │   ├── EmptyState.tsx
│   │   ├── ScreenWrapper.tsx
│   │   ├── ServiceChip.tsx
│   │   ├── StatusPill.tsx
│   │   └── Toast.tsx
│   ├── lib/
│   │   ├── api.ts               → API client (axios)
│   │   ├── auth.tsx             → Auth context provider
│   │   └── helpers.ts           → Date, status, notification formatters
│   └── theme/
│       └── index.ts             → Colors, spacing, typography tokens
├── tsconfig.json
├── package.json
└── app.json
```
