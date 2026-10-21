# Class Flow — Flutter App UI/UX Blueprint

**Companion to:** Laravel Class Flow CRM (`/api/v1` Sanctum)  
**Audience:** Flutter engineers + product  
**Date:** 21 Oct 2026  
**Status:** Design system + screen architecture (implementation not started in this repo)

This app is a **phone window into the same CRM**, not a second product. Screens hide/show from `user.permissions`. `mobile_role` only picks dashboard *layout* (`institute` / `teacher` / `parent`).

API contracts: `docs/mobile-api-app-developer-guide.md`  
Manual click-through: `docs/mobile-api-manual-runbook.md`

---

## 1. Project theme & UI design system

### 1.1 Brand tokens

| Token | Light | Dark | Use |
|-------|-------|------|-----|
| **Primary accent** | Royal Blue `#2563EB` | `#3B82F6` (slightly lifted) | Primary buttons, active tab, links, focus rings |
| **Primary pressed** | `#1D4ED8` | `#2563EB` | Button down-state |
| **Primary container** | `#EFF6FF` | `#1E3A8A` @ 24% | Chip / selected filter / icon wells |
| **Secondary accent** | Teal `#0D9488` | `#14B8A6` | Success-adjacent actions, “paid / present / live” |
| **Secondary container** | `#F0FDFA` | `#134E4A` @ 30% | Teal chips |
| **Background** | Clean off-white `#F8FAFC` | Deep Navy `#0F172A` | Scaffold |
| **Surface** | `#FFFFFF` | `#1E293B` | Cards, sheets, app bar |
| **Surface muted** | `#F1F5F9` | `#334155` | Input fill, list zebra optional |
| **Text primary** | `#0F172A` | `#F8FAFC` | Titles, numbers |
| **Text secondary** | `#64748B` | `#94A3B8` | Captions, meta |
| **Border** | `#E2E8F0` | `#334155` | 1 px hairlines — **soft, never black** |
| **Error** | `#DC2626` | `#F87171` | Validation, 422 field errors |
| **Warning** | `#D97706` | `#FBBF24` | Attention / due |
| **Success** | `#059669` | `#34D399` | Active, present, collected |
| **Overlay** | `#0F172A` @ 40% | `#000` @ 55% | Modal scrim |

**Low-contrast rule:** body text never sits on `#FFFFFF` at full `#0F172A` for long paragraphs — use 16–17 sp and generous line-height (1.45). Cards use **1 px border + 8–12 elevation blur at 4% opacity**, not heavy drop shadows.

### 1.2 Typography

Family: **Inter** (fallback **Roboto** / `google_fonts`).

| Style | Size / weight | Use |
|-------|----------------|-----|
| Display | 28 / 700 | Splash wordmark only |
| H1 | 22 / 650 | Screen titles |
| H2 | 18 / 600 | Section headers (“Quick actions”) |
| Body | 15–16 / 400 | Forms, list titles |
| Caption | 12–13 / 500 | Timestamps, badges |
| Numeric | 20 / 650 tabular | Metric card values |

Never mix a third display font. One family, hierarchy by size/weight only.

### 1.3 Shape, space, motion

- **Radius:** 12 (inputs, small chips) · 16 (cards) · 24 (bottom sheets) · 999 (pills / FABs).
- **Grid:** 16 px page padding; 8 px rhythm. Metric cards 12 px internal.
- **Touch:** min 48×48. List row height 64–72.
- **Motion:** 200–280 ms `easeOutCubic`. Shared-axis for list → detail. Fade for tab switch. No bounce on forms.
- **Haptics:** light impact on tab change; medium on successful save.

### 1.4 Style principles (minimalist SaaS)

1. One primary action per screen (filled Royal Blue). Secondary = outlined or text-teal.  
2. Empty states: one line + one action, no illustration dumps.  
3. Status = **badge**, not a rainbow row. Max two colors on a card besides text.  
4. Dark mode is first-class: same layout, navy canvas, lifted primary — not inverted light.  
5. Reduce eye strain: avoid pure `#000` on `#FFF`; prefer off-white / navy pair above.

### 1.5 Flutter `ThemeData` sketch

```
ColorScheme.light(
  primary: Color(0xFF2563EB),
  secondary: Color(0xFF0D9488),
  surface: Color(0xFFFFFFFF),
  error: Color(0xFFDC2626),
)
ColorScheme.dark(
  primary: Color(0xFF3B82F6),
  secondary: Color(0xFF14B8A6),
  surface: Color(0xFF1E293B),
  error: Color(0xFFF87171),
)
scaffoldBackgroundColor: light #F8FAFC / dark #0F172A
```

Persist theme in `shared_preferences` (`theme_mode`: `system` | `light` | `dark`). Default = **system**.

---

## 2. Complete mobile screen architecture & flow

### 2.0 Navigation map

```
Splash
  ├─ no token ──────────────► Login
  │                             ├─ Forgot password *   (UI now; API later)
  │                             └─ OTP *               (UI now; API later)
  └─ valid token
        └─ must_change_password? ──► Force change password
              └─ Shell (4 tabs)
                    ├─ Home / Dashboard
                    ├─ Operations (module hub)
                    ├─ Analytics
                    └─ Profile
```

`*` Backend today: **email + password login only**. Forgot password / OTP screens ship as **designed shells** (copy + disabled or “contact institute admin”) until Laravel endpoints exist. Do not fake a reset.

Biometric is **client-side**: after a successful password login, offer “Unlock with Face ID / fingerprint” and store the refreshable token in `flutter_secure_storage`. Next cold start: biometric → reuse token → `GET /auth/me`. Fail → Login.

### 2.1 Authentication

#### Splash

- Full-bleed Deep Navy (`#0F172A`) or off-white by system theme.  
- Centered wordmark **Class Flow** + thin teal underline.  
- 400–600 ms then route by token.  
- No spinner unless `GET /auth/me` is in flight.

#### Login

- Email + password `CfTextField`s, `autovalidateMode.onUserInteraction`.  
- Email: required, `EmailValidator`. Password: required, min 8 (matches API).  
- Primary button **Sign in** → `POST /auth/login`.  
- Errors: map 422 `errors.email` under the field; 403 message as a banner (admin / inactive / suspended).  
- Secondary: **Forgot password?** (see gap).  
- After first success: bottom sheet **Use biometrics next time?**  
- Show institute-agnostic footer: “Platform & Institute Admin use the web.”

#### Forgot password (UI placeholder)

- Email field + “Send reset link”.  
- On submit: snackbar *“Password reset is not on mobile yet. Ask your institute admin.”*  
- Do not call a fake endpoint.

#### OTP verification (UI placeholder)

- 6-box PIN.  
- Same honest empty-state until a real OTP API exists.  
- Reuse later for `must_change_password` or future 2FA.

#### Force change password

- Shown when `must_change_password == true`. Blocks the shell.  
- Fields: current, new, confirm.  
- `POST /auth/change-password`. Then `GET /auth/me`.

### 2.2 Main layout — 4-tab shell

Bottom `NavigationBar` (Material 3), Royal Blue selected icon + label, muted unselected.

| Index | Tab | Icon | Screen |
|-------|-----|------|--------|
| 0 | Home | `home_outlined` | Dashboard (role layout) |
| 1 | Work | `apps_outlined` | Operations hub (permission grid) |
| 2 | Insights | `insights_outlined` | Analytics / widgets |
| 3 | Me | `person_outline` | Profile & settings |

- Safe area + 8 px top hairline on the bar.  
- Optional **FAB** only on Work when the current module has `*.add` (e.g. Add inquiry). Hide on Home/Insights/Me.  
- Parent: app bar **child switcher** chip (selected `student_id`) visible on Home + Work child-scoped lists.  
- BM/Principal with many campuses: Home app bar **branch chip** (`branch_id`).

### 2.3 Dashboard (Home)

One route, **three bodies** from `GET /dashboard` → `data.kind`.

Shared chrome:

- **Header:** `Good morning, {name}` (API `greeting` + `name`). Institute / place under caption.  
- **Bell:** top-right. Inbox **not built** (FCM deferred). Bell opens a sheet: “Notifications coming soon.” Badge stays 0.  
- **Horizontal metric cards** (`CfMetricCard`): swipe, 3–4 visible peek. Bind `summary` / `kpis`. Teal for healthy, amber for due, blue for counts.  
- **Quick action grid:** 2×N from `quick_actions[]`. Keys already mapped (`mark_attendance`, `give_homework`, `apply_leave`, …). Disabled/hidden if missing.  
- **Recent / attention feed:** `attention[]` + role extras (teacher homework, parent dues). Each row: title, time, **status badge**. Tap → module detail.

#### Institute / BM / Principal / Accountant / Receptionist (`kind: institute`)

Cards: students, inquiries, collections, attendance pulse (whatever `summary` returns).  
Campus chips if `branches.length > 1` — reload with `?branch_id=`.

#### Teacher (`kind: teacher`)

Cards from `summary`. Batch pulse list: `is_live`, `is_marked`, present/absent. Tap batch → batch detail or mark attendance if `attendance.add`.

#### Parent (`kind: parent`)

Child avatar switcher from `children[]`. Cards: attendance rate, fees due. Lists: homework, exams, dues, notices. Always pass `student_id`.

Pull-to-refresh → `GET /dashboard` (+ widget calls if charts shown on Home — prefer Insights tab).

### 2.4 Operations / modules (Work tab)

**Hub first**, not 20 stacked tabs.

```
WorkHub
  tiles from permissions (icon + label + optional count)
    → ModuleList
         → ModuleDetail
              → Create / Edit (full screen on phone, sheet on tablet)
```

#### Hub groups (show tile only if `can`)

| Group | Tiles | Permission cue |
|-------|-------|----------------|
| Campus | My branches, Batches | `branch.view`, `batch.view` |
| People | Teachers, Staff, Users, Students | `teacher.view`, `staff.view`, `users.view_*`, `student.view` |
| Front desk | Inquiries, Follow-ups | `inquiry.view`, `follow_up.view` |
| Classroom | Attendance, Homework, Exams, Results, Study material, Notices | matching `*.view` / `*.add` |
| Money | Fees, Fee follow-ups, GST, Income & expense, Inventory | `fees.*`, `gst_invoice.*`, … |
| Time | Holidays, To-dos, Leave | `holiday.view`, `todo.view`, `student_leave.*` / `staff_leave.*` |
| Masters | Subjects, Standards | `subject.view`, `standard.view` |

Default **teacher** hub may only show Batches (+ extras if granted). Default **parent** hub: Homework, Exams, Results, Attendance, Notices, Fees due, Leave. **Accountant:** Fees, GST, I&E. **Receptionist:** Inquiries, Students, Batches.

#### List pattern (every module)

- Search `q` debounce 350 ms.  
- Filter chips: `status`, `branch_id`, `batch_id` as the API allows.  
- `ListView` + pagination (`page`, `per_page` ≤ 50).  
- Row: title, subtitle (meta), trailing **status badge**.  
- Empty: “No {module} yet” + Add if `*.add`.  
- 403: hide the tile (don’t open a dead list).  
- 404 on detail: “Not available for your campus.”

#### Detail pattern

- Sliver header + status badge.  
- Tab bar **only** when the API has tabs (batch: students / attendance / exams…; teacher: batches / login / attendance / work).  
- Overflow menu: Edit, Toggle status, Delete, Receipt/PDF — each gated.

#### Create / edit pattern

- Phone: new route (`ModuleFormPage`). Tablet: `showModalBottomSheet` 90% height.  
- Reuse `CfTextField`, `CfDateField`, `CfSelect` (batches, standards).  
- Validate client-side, then show API 422 under fields.  
- Success 201: pop + snackbar + refresh list.  
- Multipart for photos / homework / study material.

**Never** navigate to `/teachers/{id}` with a null id. Guard in the router.

### 2.5 Analytics & reports (Insights tab)

- Date range chip (This month / Last 30 days / Custom). Pass through to widget query if the API later accepts it; today widgets are server-defined.  
- Only render `allowed_widgets` from dashboard.  
- Charts: **fl_chart** Line + Bar. Royal Blue line, Teal bars, grid at 8% opacity.  
- Institute widgets: `fees_trend`, `attendance_trend`, `admissions`, `lead_sources`, `standards`, `branches`, `payment_methods`, `batch_attendance`.  
- Teacher: `attendance_trend`, `batch_attendance`.  
- Parent: `child_attendance`, `child_results` + `student_id`.  
- Below charts: export tiles (CSV/PDF) that open the file from `GET /export/...` with Bearer.  
- Empty / 403 widget: skip the card, don’t error the whole tab.

### 2.6 Profile & settings (Me tab)

- Avatar (tap → camera/gallery → `POST /profile` multipart `avatar`).  
- Name, email (read-only), phone (editable).  
- Role chip + institute name.  
- **Appearance:** Light / Dark / System.  
- **Security:** Biometric toggle, Change password (real API).  
- **Campus / child** defaults.  
- **Logout:** `POST /auth/logout` + wipe secure storage. Confirm sheet.  
- Version + API base URL (debug only).

---

## 3. Technical architecture guidelines

### 3.1 Repo layout (Flutter app — new project, e.g. `class_flow_app/`)

```
lib/
  main.dart
  app.dart                          # MaterialApp.router + theme
  core/
    theme/app_theme.dart
    theme/app_colors.dart
    theme/app_typography.dart
    network/api_client.dart         # Bearer, 401→logout, envelope
    network/api_exception.dart
    storage/token_store.dart        # flutter_secure_storage
    permissions/permission.dart     # can / canAny
    router/app_router.dart          # go_router
  features/
    auth/
      data/auth_repository.dart
      presentation/splash_page.dart
      presentation/login_page.dart
      presentation/forgot_password_page.dart
      presentation/otp_page.dart
      presentation/change_password_page.dart
    dashboard/
      data/dashboard_repository.dart
      presentation/home_page.dart
      presentation/institute_home.dart
      presentation/teacher_home.dart
      presentation/parent_home.dart
    operations/
      presentation/work_hub_page.dart
      batches/ ...
      students/ ...
      inquiries/ ...
      attendance/ ...
      fees/ ...
      # one folder per module as it ships
    analytics/
      presentation/insights_page.dart
      presentation/widgets/cf_line_chart.dart
    profile/
      presentation/profile_page.dart
    shell/
      presentation/app_shell.dart   # 4-tab scaffold
  widgets/
    cf_button.dart
    cf_text_field.dart
    cf_metric_card.dart
    cf_status_badge.dart
    cf_empty_state.dart
    cf_search_bar.dart
```

Rules:

- Features **do not** import other features’ `presentation/`. Share via `core/` + `widgets/`.  
- Data layer talks HTTP only. Presentation holds Riverpod / Bloc.  
- Null-safety: `User?`, never `!` on API maps — use `fromJson` with defaults.  
- Responsive: `LayoutBuilder` — phone column; `shortestSide >= 600` → detail pane beside list.

### 3.2 Reusable widgets (build these first)

| Widget | Spec |
|--------|------|
| `CfButton` | filled / outlined / text; loading spinner; full-width option; disabled opacity 0.4 |
| `CfTextField` | label, hint, errorText, obscure toggle, prefix icon; 12 radius; filled muted |
| `CfMetricCard` | title, value, delta%, accent bar (blue/teal/amber); 160×96 min |
| `CfStatusBadge` | `active` teal, `inactive` slate, `pending` amber, `absent` red, `due` amber |
| `CfSearchBar` | rounded, 350 ms debounce |
| `CfEmptyState` | icon, title, optional `CfButton` |
| `CfAvatar` | network `avatar_url` + initials fallback |

### 3.3 State & networking

- Envelope: `{ success, message, data }` — parse once in `ApiClient`.  
- 401 → clear token → Login.  
- 422 → `Map<String, List<String>>` into form.  
- Pagination: `data.items` + `data.meta`.  
- IDs: `teacher.id` ≠ `user.id`. Document in each repository.  
- Permissions: `can('fees.add')` before showing Collect.

### 3.4 Backend gaps (do not block UI)

| UI | API today |
|----|-----------|
| Forgot password / OTP | **Not in `/api/v1`** — placeholder screens |
| Notification inbox | FCM deferred — bell sheet only |
| Branch create/edit | Web only — no mobile form |
| Platform / Institute Admin login | 403 — copy on Login |

---

## 4. Screen inventory (build checklist)

| # | Screen | Tab / stack | First data call |
|---|--------|-------------|-----------------|
| A0 | Splash | Auth | token? `GET /auth/me` |
| A1 | Login | Auth | `POST /auth/login` |
| A2 | Forgot password | Auth | none (placeholder) |
| A3 | OTP | Auth | none (placeholder) |
| A4 | Change password | Auth / Profile | `POST /auth/change-password` |
| S0 | App shell (4 tabs) | — | — |
| H1 | Home (3 layouts) | Home | `GET /dashboard` |
| W0 | Work hub | Work | permissions from me |
| W1… | Module list / detail / form | Work | see API handbook |
| I1 | Insights | Insights | `GET /dashboard/data/{widget}` |
| P1 | Profile | Me | `GET /auth/me`, `POST /profile` |

---

## 5. Accessibility & QA

- Contrast: body text vs background ≥ 4.5:1 (slate `#64748B` on off-white is OK for captions only).  
- Dynamic type: scale 1.0–1.3; metric numbers wrap, don’t clip.  
- Semantics labels on icon-only bell / biometric.  
- TalkBack: tab bar labels always visible.  
- QA roles: BM, teacher, parent, accountant, receptionist — same APK, different hubs.

---

## 6. Out of scope for v1 UI

- Designing a Platform Admin app.  
- Offline-first sync (retry GET is enough).  
- Custom illustration system.  
- Rebuilding web Roles / Billing / Branding.

When Flutter code starts, keep this file as the visual source of truth; API shapes stay in `docs/mobile-api-*.md`.
