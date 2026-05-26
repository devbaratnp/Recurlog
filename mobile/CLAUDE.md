# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Critical: Expo SDK 54

From `AGENTS.md`: **Expo has changed.** Before writing any Expo / Expo Router / React Native code, read the exact versioned docs at https://docs.expo.dev/versions/v54.0.0/. API surfaces (especially `expo-router`, `expo-font`, `expo-system-ui`) differ from older guides and from your training data.

Pinned: `expo ~54.0.34`, `expo-router ~6.0.23`, `react 19.1.0`, `react-native 0.81.5`, TypeScript `~5.9.2`. Downgraded from SDK 56 because Expo Go in stores is still on SDK 54.

## Commands

```
npm start              # Expo dev server (Metro)
npm run android        # open in Android emulator
npm run ios            # open in iOS simulator (macOS only)
npm run web            # open web build
```

There is no test runner, linter, or formatter configured. Don't claim a change passes tests without setting one up first.

## Architecture

**File-based routing via expo-router.** Entry point is `index.ts` → `expo-router/entry`; the router scans `app/` for routes.

- `app/_layout.tsx` — root `Stack`, wraps the tree in `SafeAreaProvider` → `AuthProvider` → `ToastProvider`. Every screen reachable from the router must be declared here as a `Stack.Screen`.
- `app/index.tsx` — splash; redirects to `/(tabs)/dashboard` or `/login` based on `useAuth()`.
- `app/(tabs)/` — bottom-tab group (`dashboard`, `customers`, `tasks`, `staff`) with its own `_layout.tsx` defining the `Tabs` navigator.
- `app/customers/[id].tsx`, `app/staff/[id].tsx` — dynamic routes (use `useLocalSearchParams`).
- `app/customers/add.tsx`, `app/services/add.tsx`, `app/notifications.tsx`, `app/settings.tsx` — pushed on top of the stack.

**Shared code lives in `src/`, not `app/`** (the router treats every file in `app/` as a route):
- `src/components/` — UI primitives (`Button`, `Card`, `Badge`, `StatusPill`, `EmptyState`, `ServiceChip`, `ScreenWrapper`, `Toast`). Wrap screens in `ScreenWrapper` for consistent safe-area + background.
- `src/lib/api.ts` — single fetch-based API client. Base URL hardcoded to `https://recurlog.isoftro.com/api`. All HTTP goes through `api.*` methods; do not call `fetch` directly from screens.
- `src/lib/auth.tsx` — `AuthProvider` + `useAuth()`. Uses `AsyncStorage` + real `api.login` (works on native and web).
- `src/lib/helpers.ts` — date/status/category formatting + the canonical `serviceTypes` list.
- `src/theme/index.ts` — design tokens (`colors`, `spacing`, `borderRadius`, `typography`). Use these instead of hardcoding hex/sizes; the brand palette is green `#1DB954` on navy `#0B1E3D`.
- `src/screens/` is empty — legacy folder, ignore.

**Toast pattern:** call `useToast().showToast(message, 'success' | 'error' | 'info')` from any screen — the provider in the root layout handles rendering.

## TypeScript quirks

- `tsconfig.json` extends `expo/tsconfig.base` with `strict: true`.
- `expo-router.d.ts` at the repo root is a **hand-rolled shim** for `expo-router`'s types covering only the surface this app uses (`useRouter`, `useLocalSearchParams`, `Stack`, `Tabs`). If you need an expo-router API that isn't declared there, add it to the shim — don't expect the real package types to resolve.
