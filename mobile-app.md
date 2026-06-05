# Recurlog Mobile App

React Native mobile app for Recurlog — Field Service Management CRM.

## Stack

- **Expo SDK 54** · React Native 0.76.6 · React 19
- **TypeScript** throughout
- **Zustand 5** — state management (auth, notifications)
- **TanStack Query 5** — server-state cache layer (`QueryClientProvider` in App.tsx)
- **React Navigation 7** — bottom tabs + native stacks
- **Axios** — HTTP client with JWT auto-refresh interceptor
- **react-hook-form** — form handling
- **lucide-react-native** — icon set
- **date-fns** — date formatting
- **expo-google-fonts/poppins** — Poppins font family

## Project Structure

```
mobile/
├── App.tsx                      # Root: fonts → splash → GestureHandler → SafeArea → QueryClient → Navigator
├── app.json                     # Expo SDK 54 config
├── package.json                 # Dependencies
├── babel.config.js              # babel-preset-expo + reanimated plugin
├── tsconfig.json                # Strict TS, @/ alias → src/
├── .gitignore
├── .env.example
├── assets/
│   ├── icon.png                 # App icon (placeholder)
│   ├── adaptive-icon.png        # Android adaptive icon (placeholder)
│   ├── splash-icon.png          # Splash image (placeholder)
│   └── favicon.png              # Web favicon (placeholder)
└── src/
    ├── api/
    │   └── client.ts            # Axios instance + all API endpoint modules
    ├── components/
    │   ├── EmptyState.tsx        # Centered empty placeholder
    │   ├── LoadingSkeleton.tsx   # Animated shimmer blocks (DashboardSkeleton)
    │   ├── PriorityBadge.tsx     # Urgent / Normal pill
    │   ├── SearchBar.tsx         # Search input with icon
    │   ├── ServiceChip.tsx       # Colored service type chip (RO, AC, TV, etc.)
    │   ├── StatCard.tsx          # Metric card (label, value, color)
    │   └── StatusBadge.tsx       # Status pill (pending/completed/missed/assigned/cancelled)
    ├── constants/
    │   ├── config.ts             # API URL, storage keys
    │   └── theme.ts              # Colors, fonts, spacing, radii, shadows, service/category colors
    ├── hooks/                    # (reserved for custom hooks)
    ├── navigation/
    │   ├── AppNavigator.tsx      # Auth gate: MainNavigator vs AuthNavigator
    │   ├── AuthNavigator.tsx     # Login stack
    │   └── MainNavigator.tsx     # Bottom tabs (Dashboard, Customers, Orders, Tasks, More) + sub-stack
    ├── screens/
    │   ├── auth/
    │   │   └── LoginScreen.tsx           # Brand header, email/password form, validation, error display
    │   ├── customers/
    │   │   ├── CustomerListScreen.tsx    # FlatList + search + service chips + edit link
    │   │   ├── CustomerAddScreen.tsx     # Create/edit form + service selection chips
    │   │   └── CustomerDetailScreen.tsx  # Customer info + services + tasks
    │   ├── dashboard/
    │   │   └── DashboardScreen.tsx       # Stats grid, quick actions, notification bell
    │   ├── notifications/
    │   │   └── NotificationScreen.tsx    # Notification list + mark read/all read
    │   ├── orders/
    │   │   └── OrderListScreen.tsx       # FlatList + priority/status badges + search
    │   ├── reports/
    │   │   └── ReportsScreen.tsx         # Overview stats + staff/category bars + recent tasks
    │   ├── settings/
    │   │   └── SettingsScreen.tsx        # Profile, preferences, export, logout
    │   ├── staff/
    │   │   ├── StaffListScreen.tsx       # FlatList + avatar + completion bar
    │   │   └── StaffDetailScreen.tsx     # Profile + stat grid + assigned tasks
    │   └── tasks/
    │       └── TaskListScreen.tsx        # Today/Upcoming/Missed tabs + search + complete modal
    ├── store/
    │   ├── authStore.ts          # Zustand: login, logout, restoreSession, JWT persistence
    │   └── notificationStore.ts  # Zustand: fetch, markRead, markAllRead, unreadCount
    ├── types/
    │   └── index.ts              # User, Customer, Service, Task, Order, Staff, Notification, etc.
    └── utils/
        └── date.ts               # formatDate, formatRelative, getNextDueDate (recurrence engine)
```

## Files (39 total)

### Root (`mobile/`)

| File | Lines | Purpose |
|---|---|---|
| `App.tsx` | 81 | Entry point — font loading, splash screen, GestureHandler + SafeArea + QueryClient wrappers |
| `app.json` | 38 | Expo config — SDK 54, portrait, navy splash, bundle ID, asset paths |
| `package.json` | 41 | Dependencies — Expo 54, React 19, RN 0.76.6, Nav 7, Zustand 5, TanStack 5, etc. |
| `babel.config.js` | 7 | Babel — expo preset + reanimated plugin |
| `tsconfig.json` | 10 | Strict TS, `@/*` path alias |
| `.gitignore` | 31 | Standard RN/Expo ignores |
| `.env.example` | 8 | API URL + prefix template |

### Assets (`mobile/assets/`)

| File | Purpose |
|---|---|
| `icon.png` | Placeholder app icon (1×1 transparent PNG) |
| `adaptive-icon.png` | Placeholder Android adaptive icon |
| `splash-icon.png` | Placeholder splash screen image |
| `favicon.png` | Placeholder web favicon |

### API (`src/api/`)

| File | Lines | Purpose |
|---|---|---|
| `client.ts` | 180 | Axios instance with JWT auto-refresh interceptor (queues concurrent 401s). Exports typed API modules: `authApi`, `customersApi`, `servicesApi`, `tasksApi`, `ordersApi`, `staffApi`, `categoriesApi`, `notificationsApi`, `localitiesApi`, `serviceTypesApi` |

### Components (`src/components/`)

| File | Lines | Purpose |
|---|---|---|
| `EmptyState.tsx` | 37 | Centered title + optional subtitle |
| `LoadingSkeleton.tsx` | 73 | Animated shimmer blocks with `DashboardSkeleton` preset (4 stat cards + 3 list items) |
| `PriorityBadge.tsx` | 30 | Red "Urgent" / gray "Normal" pill |
| `SearchBar.tsx` | 46 | Search icon + TextInput, white rounded border |
| `ServiceChip.tsx` | 27 | Colored chip for service types (RO → green, TV → blue, AC → orange, etc.) |
| `StatCard.tsx` | 49 | Uppercase label + value + color, optionally pressable |
| `StatusBadge.tsx` | 37 | Colored pill for task/order statuses (5 variants) |

### Constants (`src/constants/`)

| File | Lines | Purpose |
|---|---|---|
| `config.ts` | 16 | Platform-aware API URL, AsyncStorage keys |
| `theme.ts` | 118 | Colors (primary #1DB954, navy #0B1E3D, neutral scale, badge colors), fonts (Poppins 400-800), spacing, radii, shadows, SERVICE_COLORS, CATEGORY_COLORS |

### Navigation (`src/navigation/`)

| File | Lines | Purpose |
|---|---|---|
| `AppNavigator.tsx` | 39 | `restoreSession()` → loading spinner → auth gate (MainNavigator vs AuthNavigator) |
| `AuthNavigator.tsx` | 16 | Single screen: Login |
| `MainNavigator.tsx` | 170 | 5 bottom tabs: Dashboard (wraps full sub-stack), Customers, Orders, Tasks, More (→ Reports). Sub-stack has 11 screens. Notification unread badge |

### Screens (`src/screens/`)

| File | Lines | Purpose |
|---|---|---|
| `auth/LoginScreen.tsx` | 228 | Navy brand header, email/password fields (demo pre-fill), password toggle, error handling, loading state |
| `dashboard/DashboardScreen.tsx` | 259 | Header + notification bell, 4 quick-add buttons, Customer/Order stat cards, Staff/Reports links, pull-to-refresh, skeleton loader |
| `customers/CustomerListScreen.tsx` | 136 | Search + FlatList with name, edit button, address, area, phone, service chips, pull-to-refresh |
| `customers/CustomerAddScreen.tsx` | 168 | Create/edit form: name, address, area, phone, service chips (toggleable with checkmarks), validation, save |
| `customers/CustomerDetailScreen.tsx` | 206 | Customer info card, services list with status, tasks list with completion indicators, "Add Service" button |
| `tasks/TaskListScreen.tsx` | 226 | Today/Upcoming/Missed tabs, search, task cards with customer + date + status, "Mark Complete" bottom-sheet modal (date + notes) |
| `orders/OrderListScreen.tsx` | 107 | Search + FlatList with customer, priority badge, service, problem, date, staff, status badge |
| `staff/StaffListScreen.tsx` | 103 | Avatar (UI Avatars fallback), name, phone, task count, completion progress bar |
| `staff/StaffDetailScreen.tsx` | 116 | Profile card, stat grid (Total/Completed/Missed/Rate), assigned tasks list |
| `notifications/NotificationScreen.tsx` | 112 | Type-colored icons, text, relative time, unread indicators, "Mark All Read" |
| `settings/SettingsScreen.tsx` | 171 | Profile card, preferences (notifications/language/theme), data export (CSV/PDF), logout with confirmation |
| `reports/ReportsScreen.tsx` | 168 | Overview stats, staff completion bars, category completion bars (colored), recent 20 tasks |

### Store (`src/store/`)

| File | Lines | Purpose |
|---|---|---|
| `authStore.ts` | 67 | Zustand — `login` (API + AsyncStorage persist), `logout` (clear), `restoreSession` (validate token, refresh user or clear) |
| `notificationStore.ts` | 49 | Zustand — `fetchNotifications`, `markAsRead(id)`, `markAllRead` (optimistic local updates) |

### Types (`src/types/`)

| File | Lines | Purpose |
|---|---|---|
| `index.ts` | 136 | 12 interfaces: User, Customer, Category, Staff, Recurrence, Service, Task, Order, Notification, Locality, ServiceType, AuthResponse + PaginatedResponse/ApiResponse generics |

### Utils (`src/utils/`)

| File | Lines | Purpose |
|---|---|---|
| `date.ts` | 56 | `formatDate`, `formatRelative`, `formatRelativeTime`, `todayISO`, `addToDate`, `getNextDueDate` (recurrence engine: `last-done`/`fixed-schedule` modes) |

## Design System

| Token | Value |
|---|---|
| Primary | `#1DB954` (green) |
| Navy | `#0B1E3D` |
| Status Pending | `bg #FEF3C7` · `text #92400E` |
| Status Completed | `bg #DCFCE7` · `text #166534` |
| Status Missed | `bg #FEE2E2` · `text #991B1B` |
| Font | Poppins (400/500/600/700/800) |
| Border Radius | `sm:4` `md:8` `lg:12` `xl:16` `full:999` |
| Service Colors | RO `#10B981`, TV `#3B82F6`, AC `#F97316`, Refrigerator `#06B6D4`, Washing Machine `#A855F7`, Other `#6B7280` |

## API Endpoints

Base URL: `https://recurlog.isoftro.com/api/v1/` by default, override with `EXPO_PUBLIC_API_URL` in `.env`

All endpoints require `Authorization: Bearer {token}` header. Auth endpoint supports login/refresh/me. CRUD endpoints for customers, services, tasks, orders, staff, categories, notifications, localities, service_types.

## Recurrence Engine

Ported identically from `assets/js/app.js:36` into `utils/date.ts`. Supports:
- **Units**: days, weeks, months, years
- **Repeat modes**: `last-done` (from completion date), `fixed-schedule` (from previous scheduled date)

## Auth Flow

1. Login → API returns `{ token, refreshToken, user }` → stored in AsyncStorage
2. Every request: Axios interceptor injects `Bearer {token}`
3. On 401: interceptor calls refresh endpoint using `refreshToken`, queues concurrent requests, retries with new token
4. On refresh failure: clears all stored tokens, user must re-login
5. App start: `restoreSession()` reads token, validates via `/auth.php?action=me`, refreshes user data or clears

## Running

```bash
cd mobile
npm install --legacy-peer-deps
npx expo start
```

Use Expo Go on device or emulator. Set `EXPO_PUBLIC_API_URL` in `.env` if you need to point at a different backend.
