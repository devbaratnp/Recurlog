# Staff Portal Dashboard Redesign

**Date:** 2026-06-05
**Scope:** Mobile React Native (Expo) — `mobile/src/screens/staff/StaffDashboardScreen.tsx` and supporting components

## Objectives

Transform the field staff dashboard into a modern, premium, enterprise-grade mobile experience comparable to Salesforce Field Service, ServiceTitan, and Zoho FSM — while preserving all existing business logic, APIs, auth, and backend code.

## Current Problems

See UX Audit in attached report: 18 issues identified spanning visual hierarchy, spacing, typography, interaction design, accessibility, and code quality.

## Design Principles

- Modern, premium, enterprise
- Mobile-first, fast, professional
- Minimal borders, elevated cards with subtle shadows
- Consistent 8px spacing grid
- WCAG-compliant contrast ratios
- 44pt minimum tap targets
- Animated micro-interactions
- 60 FPS performance

## Component Architecture

```
StaffDashboardScreen (orchestrator — state + API calls)
├── StaffHeader              — greeting + avatar + notification bell
├── StaffKpiRow              — 3 animated stat cards
├── ProgressSection          — animated progress bar
├── PriorityTaskCard         — hero card for first pending task
├── QuickActions             — 4-action grid
├── UpcomingTimeline (SectionList) — date-grouped upcoming tasks
└── StaffTaskCompleteModal   — unmodified
```

### Data Flow

All API calls remain in `StaffDashboardScreen.fetchData()`. State is passed down as props. No new API calls or backend changes.

### Color Palette

| Token | Value | Usage |
|-------|-------|-------|
| `primary` | `#22C55E` | Brand green |
| `success` | `#16A34A` | Success states |
| `warning` | `#F59E0B` | Amber alert |
| `danger` | `#EF4444` | Error/missed |
| `bg` | `#F8FAFC` | Page background |
| `card` | `#FFFFFF` | Card surface |
| `textPrimary` | `#0F172A` | Primary text |
| `textSecondary` | `#64748B` | Secondary text |
| `border` | `#E2E8F0` | Subtle borders |

### Typography

- Family: Poppins (existing via expo-google-fonts)
- Weights: 400/500/600/700/800
- Sizes: 12/14/16/18/20/24/30px (existing `FONT_SIZES`)

### Spacing

Existing `SPACING` tokens (4/8/12/16/20/24/32/40/48) — consistent 8px base.

## Section Designs

### 1. StaffHeader
- 48px height, white bg, bottom border
- Left: 36px avatar circle (initials) + greeting text "Good Morning, Ramesh"
- Right: Bell icon with badge
- Greeting determined by hour: 5-11=Morning, 12-16=Afternoon, 17-21=Evening, else "Hello"

### 2. StaffKpiRow
- 3 equal-width cards in a row
- Each card: icon top, value (animated counter), label bottom
- Green/Amber/Red color coding
- No borders, subtle elevation, radius 16
- Animated counter via `Animated.timing` (0 → final value over 800ms)

### 3. ProgressSection
- Full-width card with title, progress bar, fraction text
- Animated `<View>` width transition (percentage-based)
- Green bar with rounded caps (radius 4)
- Text: "5 of 11 tasks completed"

### 4. PriorityTaskCard
- Larger elevation, radius 20
- Only rendered when `todayTasks.length > 0`
- Shows: title, customer name, due date, priority/status badges
- Full-width green "▶ Start Visit" button → opens complete modal
- If no tasks: celebration empty state with checkmark

### 5. QuickActions
- Horizontal row, 4 items
- Each: lucide icon + label below
- Navigate: Check-In (map placeholder), Orders, Customers, Daybook
- Press animation: scale to 0.96

### 6. UpcomingTimeline
- `SectionList` with sticky headers
- Sections grouped by scheduled date
- Each section header: date label with subtle bg
- Each row: timeline dot + line, title, customer name
- Rows tappable → TaskDetail navigation

## Micro-Interactions

| Element | Animation |
|---------|-----------|
| KPI counters | `Animated.timing` 0→N, 800ms, ease-out |
| Progress bar | Width transition on data change |
| Priority CTA | Scale press (0.96) |
| Quick actions | Scale press (0.94) |
| Upcoming rows | Scale press (0.98) |
| Pull-to-refresh | Existing RN RefreshControl |

## Performance

- `SectionList` for upcoming tasks (virtualized, no render beyond viewport)
- `React.memo` on all sub-components
- `useCallback` for all handlers
- Only re-render sections whose data changes (avoid spreading entire state)
- No inline `StyleSheet.create` in render paths

## Accessibility

- `accessibilityLabel` on all interactive elements
- `accessibilityRole="button"` on touchables
- Min 44pt tap targets on all buttons
- Contrast ratio ≥ 4.5:1 for all text
- `importantForAccessibility` on decorative icons

## Files Changed

| File | Change |
|------|--------|
| `mobile/src/constants/theme.ts` | Add new color tokens |
| `mobile/src/screens/staff/StaffDashboardScreen.tsx` | Complete refactor |
| `mobile/src/components/StaffHeader.tsx` | New |
| `mobile/src/components/StaffKpiCard.tsx` | New |
| `mobile/src/components/ProgressSection.tsx` | New |
| `mobile/src/components/PriorityTaskCard.tsx` | New |
| `mobile/src/components/QuickActions.tsx` | New |
| `mobile/src/components/UpcomingTimeline.tsx` | New |
| `mobile/src/components/LoadingSkeleton.tsx` | Add StaffDashboardSkeleton |

## Out of Scope

- Tab bar redesign (separate component in MainNavigator)
- Non-staff screens
- Backend/PHP changes
- Authentication flow
