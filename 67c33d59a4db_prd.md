# IVClasses — Product Requirements Document (PRD)

> **Version:** 1.5 · **Date:** 9 Sep 2026 · **Status:** Decisions locked (§16) — Phases 0–6 shipped; Phase 7 mostly done; Phase 8–9 next  
> **Stack (current):** Laravel 13 · Blade + Alpine · Tailwind · Vite · Google Calendar API · **Spatie Permission** (teams)  
> **Goal:** Dynamic multi-tenant SaaS for coaching institutes — SEO → Demo → Plan → Purchase → Full institute CRM  
> **v1.1:** Permission Matrix file — auto-install + Developer auto-assign  
> **v1.2:** Open decisions locked — subdomain, trial, Razorpay+Stripe, global email, parent-first, Principal=role, Spatie+matrix, UI delta list  
> **v1.3:** Phase-wise **TRACKER.md** — living memory of all changes, errors, fixes (one file)  
> **v1.4:** Separate login portals — Principal ≠ Branch Manager (still Spatie roles; different `/login` entry points)  
> **v1.5:** Build status sync — CRM modules live (not mock); in-module CSV/PDF exports + receipts; no separate Reports menu; role templates  

---

## 0. Current Project Snapshot (as-is)

| Area | Status |
|------|--------|
| Marketing website (Home, About, Features, Pricing, Privacy, Book Demo) | ✅ Live |
| Demo booking + Google Meet + admin confirm/decline | ✅ Working |
| Platform admin: project inquiries, demo calendar, availability, settings | ✅ Working |
| Multi-tenancy, plans, trial, RBAC (Spatie teams + matrix) | ✅ Working |
| Soft delete on business entities | ✅ Working |
| AJAX CRUD without reload (lists/modals) | ✅ Working |
| Admin CRM (Branch → Result / Fees / Attendance / …) | ✅ **Wired to DB** (Phases 1–6) |
| Billing SaaS (plans, checkout abstraction, limits, module gate) | ✅ Mostly done — renewals/dunning cron pending |
| In-module CSV exports + printable receipts/PDF | ✅ Working (Phase 9 partial) |
| Parent / Teacher mobile API (Sanctum) | ❌ Phase 8 — not started |
| SEO city pages, Excel import, WhatsApp, automated tests | ❌ Phase 9 — remaining |

**Progress source of truth:** root `TRACKER.md` (update every session).  
**This PRD** = product plan + locked decisions; keep phase checkboxes aligned with tracker.

**This PRD designs the full SaaS on top of existing UI + demo funnel.**

---

## 1. Product Vision

**IVClasses** = multi-tenant SaaS for coaching / tuition institutes.

1. Institute finds product via **SEO / Google** → marketing site  
2. Books **free demo** → Platform Admin confirms  
3. Chooses **Free trial / Paid plan** → registers institute (tenant)  
4. Pays (or starts trial) → gets **Classes Admin** login  
5. Creates **Branches → Batches → Principal / Teachers / Staff → Students + Parents**  
6. Day-to-day: inquiry, fees, attendance, exams, notices, leave, inventory…  
7. Every user sees **only their hierarchical data** (institute → branch → batch)  
8. **Platform Super Admin (main developer account)** sees **global** data across all institutes

---

## 2. Personas & Hierarchy

```
PLATFORM SUPER ADMIN (you / main developer)
 └── INSTITUTE / CLASSES (tenant)  ← paying customer
      ├── Classes Admin / Owner
      ├── Principal (optional, branch or institute scope)
      ├── Branch Manager
      ├── Teacher
      ├── Staff (Accountant, Receptionist, Peon, Custom roles…)
      ├── Student
      └── Parent (linked to student(s))
```

### Who sees what (data scope)

| Role | Scope example |
|------|----------------|
| **Platform Super Admin** | All institutes, subscriptions, demos, global settings, audit logs |
| **Classes Admin** | Entire institute (all branches) |
| **Principal** | Assigned branch(es) or whole institute (config) |
| **Branch Manager** | One branch |
| **Teacher** | Assigned batches only |
| **Accountant** | Fees / income-expense of assigned branch(es) |
| **Receptionist** | Inquiry, follow-up, student add (no delete fees) |
| **Student** | Own profile, homework, exams, attendance, notices |
| **Parent** | Linked children’s fees, attendance, results, notices |

---

## 3. End-to-End User Journeys

### 3.1 Public → Paying Tenant

```
SEO / Google → Website (Home/Features/Pricing)
  → Book Demo → Demo confirmed (Meet)
  → Pricing / Free Plan CTA
  → Register Institute (create tenant + admin user)
  → Choose Plan (Free trial / Starter / Growth / Enterprise)
  → Checkout (Razorpay/Stripe later) OR activate Free trial
  → Email credentials + onboarding checklist
  → Login → Setup wizard (Branch → Standard → Subject → Batch → Staff)
```

### 3.2 Institute daily ops

```
Inquiry → Follow-up → Convert to Student
  → Assign Branch + Batch(es) + Fee structure
  → Collect fees / follow-up dues
  → Mark attendance (student + staff)
  → Homework / Study material / Exam / Result / Notice
  → Leave requests / Holidays / To-do
```

### 3.3 Soft delete & restore

- All business tables use `deleted_at` (soft delete).  
- List screens: default hide deleted; filter “Show trash”.  
- Restore + force-delete (permission-gated).  
- Cascade rule: soft-delete parent does **not** hard-delete children; children stay, filtered by parent status where needed.

---

## 4. Technical Architecture Decisions

| Decision | Choice | Why |
|----------|--------|-----|
| Tenancy | **Shared DB + `institute_id`** + **subdomain** per institute | `{slug}.ivclasses.com` resolves tenant; Super Admin on central domain |
| Auth | Single `users` table + portal/`user_type` + Spatie roles | Separate login URLs per portal (platform / institute / principal / branch / parent) |
| Email | **Global unique** on `users.email` | Standard SaaS; one identity; parent/teacher apps simple (see §16.4) |
| RBAC | **Spatie laravel-permission** + **teams (`institute_id`)** + **Permission Matrix sync** | Package power + your matrix workflow; UI Roles screens stay |
| Trial | **Time-limited trial only** (no forever-free) | Convert to paid; Super Admin can extend |
| Payments | **Razorpay + Stripe** (both) | India + international; abstract gateway interface |
| Parent / Student login | **Parent-only first**; Student app later | Faster Phase 3; parents care about fees/attendance |
| Principal | **Spatie Role** (not separate user_type) + branch scope | Standard RBAC; see §16.6 |
| Mutations | **AJAX JSON** from Controllers | No full page reload on add/edit/delete |
| Future API | Controllers → Actions/Services → Resources; `/api/v1` | Mobile ready |
| IDs | Bigint PK + optional `uuid` / slug | Subdomain uses `institutes.slug` |
| Audit | `activity_logs` | Who changed what |
| Files | `storage` + `media` table | Photos, study material, invoices |

### Subdomain tenancy (locked)

| Host | Who |
|------|-----|
| `ivclasses.com` / `www` | Marketing site, Book Demo, Pricing, Register/Trial |
| `app.ivclasses.com` or central admin | **Platform Super Admin / Developer** only |
| `{institute-slug}.ivclasses.com` | That institute’s Web CRM login + dashboard |

Middleware: resolve subdomain → set current `institute_id` → Spatie `setPermissionsTeamId(institute_id)`.

### AJAX convention (every CRUD)

```
POST   /admin/{resource}          → { success, data, message }
PUT    /admin/{resource}/{id}     → { success, data, message }
DELETE /admin/{resource}/{id}     → { success, message }   // soft delete
POST   /admin/{resource}/{id}/restore
```

- Validate with Form Requests  
- Return 422 JSON validation errors  
- Toast + DataTable row refresh (no reload)  
- CSRF + auth middleware on all

### Global query scope

```php
// Applied automatically for non–super-admin
InstituteScope: where institute_id = auth()->user()->institute_id
// Also must match subdomain-resolved institute
BranchScope:    whereIn branch_id = user assigned branches (if restricted)
```

---

## 5. Phased Delivery Plan

### Phase 0 — Foundation (Week 1–2) ✅ done

- [x] Marketing site + Book Demo  
- [x] Platform demo CRM (inquiries, calendar, availability, settings)  
- [x] Fix schema drift (`demo_status`, conversion fields on `project_inquiries`)  
- [x] Base traits: SoftDeletes, `BelongsToInstitute`, Audit helpers  

**Exit:** Demo funnel solid; coding standards locked. ✅

---

### Phase 1 — Multi-tenant Core + Auth + RBAC (Week 3–5) ✅ done

**Must ship**

1. `institutes` (tenants) + `subscriptions` + `plans`  
2. Extend `users` (institute_id, user_type, soft delete, last login)  
3. **Spatie Permission** (teams) + Roles UI wired + `role_assignable` + `branch_user`  
4. **Permission Matrix file** + `php artisan permissions:sync` → Spatie tables + auto-assign **Developer** — see **§7.2.1**  
5. Platform Super Admin / **Developer** role gets **all** matrix permissions on every sync  
6. **Subdomain** tenant resolver middleware + central Platform Admin host  
7. Platform Super Admin panel: institutes list, plan/trial assign, suspend  
8. Public **Register Institute** + **time-limited trial** activation  
9. Login / Logout + session + failed login log (global unique email)  
10. Middleware: `EnsureSuperAdmin`, `EnsureInstituteActive`, Spatie `role`/`permission`  
11. AJAX pattern scaffold + shared JS helpers  
12. Role templates hierarchy (`RoleTemplateService`) + platform templates  

**Exit:** Super Admin creates/suspends institutes; Classes Admin logs in and sees empty dashboard with role menu; syncing matrix installs new module permissions automatically. ✅  
**Leftover:** Document Laragon `*.ivclasses.test` smoke (optional).

---

### Phase 2 — Academic Master Data (Week 6–8) ✅ done

Wire existing UI screens to real DB:

1. Branch  
2. Standard  
3. Subject  
4. Batch (+ batch_subject / batch_teacher)  
5. Teacher (as user + teacher profile)  
6. Staff (user + staff profile + role)  
7. Classes branding settings (logo, name, GSTIN…)  
8. Module Help (platform CRUD + institute wizard)  

**Exit:** Full hierarchy CRUD via AJAX; data scoped by institute/branch. ✅

---

### Phase 3 — Admissions CRM + Students + Parents (Week 9–11) ✅ done

1. Institute-level **Inquiry** (different from platform `project_inquiries`)  
2. Follow-up  
3. Convert inquiry → Student + Parent user  
4. Student multi-batch enrollment  
5. Parent login auto-create  
6. Student view tabs (profile, fees, attendance, exams, results…)  
7. Student deactivate + admission/inquiry receipts (print)  

**Exit:** Lead → student conversion works end-to-end. ✅

---

### Phase 4 — Fees + GST + Income/Expense (Week 12–14) ✅ done

1. Fee structures / installments (invoice from enrollment)  
2. Fee collection + receipts (print) + payment soft-delete  
3. Fees follow-up + Export PDF (branch / multi-batch / date range)  
4. GST invoice  
5. Income-expense categories + entries  
6. Inventory / assets  

**Exit:** Accountant can collect fees and export basic reports. ✅

---

### Phase 5 — Attendance + Leave + Holidays (Week 15–16) ✅ done

1. Student attendance (batch + date)  
2. Teacher / staff attendance (login time + grace)  
3. Student & staff leave requests (approve/reject)  
4. Holidays calendar  
5. Attendance CSV exports (person + batch monthly report)  

**Exit:** Daily attendance marked without page reload. ✅

---

### Phase 6 — Academics Content (Week 17–19) ✅ done

1. Homework  
2. Study material  
3. Exam + marks + result announcement (+ Export PDF)  
4. Notices  
5. To-do tasks  

**Exit:** Teacher workflow complete on web. ✅

---

### Phase 7 — Billing SaaS Layer (Week 20–22) 🟡 mostly done

1. Public pricing tied to real `plans`  
2. **Time-limited free trial** (e.g. 14 days) — no forever-free tier  
3. Checkout via **Razorpay (India) + Stripe (international)** — gateway abstraction (bypass mode default)  
4. Invoices / payment history; **renewals / dunning cron** — still pending when gateways live  
5. Plan limits (max_branches, max_students, modules)  
6. Super Admin: manual activate / extend trial / mark paid (fallback)  
7. Register OTP + first branch + credentials email  

**Exit:** Self-serve signup → trial → pay (Razorpay or Stripe) → use. (pay path ready; renewals cron open)

---

### Phase 8 — API + Mobile Ready (Week 23–25) ❌ not started

1. Laravel Sanctum  
2. `/api/v1/*` for **Teacher / Parent** apps first (Student app later)  
3. Push notification hooks (FCM)  
4. API docs (OpenAPI)  

---

### Phase 9 — Polish (ongoing) 🟡 partial

- [x] In-module **CSV exports** + **printable PDF/receipts** (no separate Reports sidebar — by design)  
- [x] File download routes for homework / study material under Laragon public path  
- [ ] SEO landing pages per city / niche  
- [ ] WhatsApp / SMS gateways  
- [ ] Data import (Excel)  
- [ ] Activity log UI  
- [ ] Trash / Restore filters on lists  
- [ ] Onboarding wizard (first login)  
- [ ] Automated tests (critical paths)  

---

## 6. Module Catalog (mapped to existing UI)

| # | Module | UI / backend | Phase | Who uses |
|---|--------|--------------|-------|----------|
| 1 | Platform Project Inquiries / Demo | ✅ live | 0 | Super Admin |
| 2 | Plans & Subscriptions | ✅ live | 1,7 | Super Admin, Public |
| 3 | Institutes (Classes) | ✅ live | 1 | Super Admin |
| 4 | Roles & Permissions (+ templates) | ✅ live | 1 | Classes Admin |
| 5 | Branch | ✅ live | 2 | Classes Admin |
| 6 | Standard / Subject | ✅ live | 2 | Classes Admin |
| 7 | Batch | ✅ live | 2 | Classes Admin, Principal |
| 8 | Teacher | ✅ live | 2 | Classes Admin |
| 9 | Staff | ✅ live | 2 | Classes Admin |
| 10 | Inquiry + Follow-up (+ receipt) | ✅ live | 3 | Receptionist, Admin |
| 11 | Student + Parent (+ deactivate/receipt) | ✅ live | 3 | Admin, Staff |
| 12 | Fees + Follow-up (+ receipt/PDF export) | ✅ live | 4 | Accountant |
| 13 | GST Invoice | ✅ live | 4 | Accountant |
| 14 | Income-Expense | ✅ live | 4 | Accountant |
| 15 | Inventory Assets | ✅ live | 4 | Admin, Store |
| 16 | Attendance (+ CSV export) | ✅ live | 5 | Teacher, Admin |
| 17 | Leave / Holiday | ✅ live | 5 | Staff, Student, Admin |
| 18 | Homework / Study Material | ✅ live | 6 | Teacher |
| 19 | Exam / Result / Notice (+ PDF) | ✅ live | 6 | Teacher, Admin |
| 20 | To-Do | ✅ live | 6 | Staff |
| 21 | Module Help | ✅ live | 2 | Admin / roles |
| 22 | Settings / Classes branding | ✅ live | 1–7 | Admin / Super Admin |
| 23 | In-module CSV / print PDF | ✅ live | 9 | Permitted roles |

> **Reports policy (v1.5):** No dedicated Reports sidebar. Export buttons live on each module / detail tab. Deep-link report routes may remain but are not primary nav.

---

## 7. Database Schema (Normalized)

> Conventions: every tenant table has `institute_id` (nullable only for platform tables).  
> Every business table: `created_by`, `updated_by`, `deleted_at`, `timestamps`.  
> Status fields: `tinyInteger` or string enum documented below.

---

### 7.1 Platform / SaaS tables

#### `plans` — subscription products

| Field | Type | Notes | Example |
|-------|------|-------|---------|
| id | bigint PK | | 1 |
| code | string unique | | `starter` |
| name | string | | Starter |
| description | text nullable | | Single branch… |
| price_monthly | decimal(10,2) | 0 = free | 1999.00 |
| price_yearly | decimal(10,2) | | 19990.00 |
| currency | string(3) | | INR |
| trial_days | unsignedInt | | 14 |
| max_branches | unsignedInt nullable | null = unlimited | 1 |
| max_students | unsignedInt nullable | | 200 |
| max_staff | unsignedInt nullable | | 10 |
| modules_json | json | enabled module keys | `["inquiry","fees","attendance"]` |
| is_public | boolean | show on pricing | true |
| sort_order | int | | 1 |
| status | enum | active/inactive | active |
| deleted_at | timestamp nullable | soft delete | |

**Who:** Super Admin manages; Public sees public plans.

#### `institutes` — SaaS tenants (“Classes”)

| Field | Type | Notes | Example |
|-------|------|-------|---------|
| id | bigint PK | | 10 |
| uuid | uuid unique | public id | … |
| code | string unique | short code | `IVC-MEH-001` |
| name | string | Classes name | IV Classes |
| slug | string unique | subdomain/SEO | `iv-classes-mehsana` |
| email | string | billing contact | owner@iv.com |
| phone | string | | 98765… |
| logo_path | string nullable | | |
| address, city, state, pincode | | | |
| gstin | string nullable | | 24AAAAA… |
| timezone | string | | Asia/Kolkata |
| status | enum | trial/active/suspended/cancelled | trial |
| trial_ends_at | datetime nullable | | |
| onboarded_at | datetime nullable | wizard done | |
| settings_json | json | branding, fee prefs | |
| deleted_at | | soft delete | |

**Who:** Super Admin CRUD; Classes Admin updates own profile.

#### `subscriptions`

| Field | Type | Notes | Example |
|-------|------|-------|---------|
| id | | | |
| institute_id | FK → institutes | | 10 |
| plan_id | FK → plans | | 2 |
| billing_cycle | enum | monthly/yearly | monthly |
| starts_at / ends_at | datetime | | |
| status | enum | trial/active/past_due/cancelled | active |
| amount | decimal | | 1999 |
| payment_gateway | string nullable | razorpay | |
| gateway_subscription_id | string nullable | | |
| meta_json | json | | |
| deleted_at | | | |

#### `payments` (platform billing)

| Field | Type | Example |
|-------|------|---------|
| institute_id, subscription_id | FK | |
| amount, currency, status | | paid |
| gateway, gateway_payment_id, invoice_no | | |
| paid_at | | |
| raw_response_json | | webhook payload |

#### `project_inquiries` — **existing** (platform demo leads)

Keep + complete missing columns:

| Extra fields to add | Purpose |
|---------------------|---------|
| demo_status | requested/confirmed/declined/completed/no_show |
| demo_scheduled_at | datetime |
| demo_completed_at | | |
| converted_at | when became institute |
| converted_institute_id | FK → institutes |
| deleted_at | ✅ already |

`demo_availability` — keep as platform-level.

---

### 7.2 Auth & RBAC

#### `users` — extend existing

| Field | Type | Notes | Example |
|-------|------|-------|---------|
| id | | existing | |
| institute_id | FK nullable | null = platform user | 10 |
| user_type | enum | platform_admin, institute_admin, principal*(role), teacher, institute_staff, branch_manager, student, parent | staff |
| name | string | | Ramesh Patel |
| email | string **global unique** | one identity across platform | owner@iv.com |
| phone | string nullable | | |
| password | hashed | | |
| avatar_path | | | |
| must_change_password | boolean | first login | true |
| is_active | boolean | | true |
| last_login_at, last_login_ip | | | |
| email_verified_at | | | |
| deleted_at | soft delete | | |
| remember_token, timestamps | | | |

#### `roles`

| Field | Type | Notes | Example |
|-------|------|-------|---------|
| institute_id | FK nullable | null = system template role | 10 |
| name | string | | Accountant |
| slug | string | | accountant |
| parent_role_id | FK nullable | hierarchy UI | |
| image_path | | | |
| is_system | boolean | cannot delete | false |
| status | active/inactive | | |
| deleted_at | | | |

#### `permissions`

| Field | Type | Example |
|-------|------|---------|
| module | string | `fees` |
| action | string | `collect` |
| slug | unique | `fees.collect` |
| name | string | Collect Fees |
| description | string nullable | optional help text |
| is_active | boolean | true when module dynamic; false = planned only |
| sort_order | int | UI order inside module |
| deleted_at | nullable | soft-retire unused permission |

> **Source of truth is NOT hand-seeded rows** — it is the **Permission Matrix file**. DB is synced from that file. See **§7.2.1**.

#### `role_permission` — pivot

`role_id`, `permission_id`

---

### 7.2.1 Permission Matrix File (auto-install as modules go live)

**Yes — this is the correct approach.**  
Jyaare tame aek-aek module dynamic banavo (e.g. Branch), tyare matrix file ma ae module + actions define kari ne sync chalao → permissions DB ma insert thay → **Developer** role ne auto assign thay. Extra actions (`convert`, `collect`, `mark`, `export`…) pan same rite.

#### Why this is right

| Benefit | Detail |
|---------|--------|
| Single source of truth | Developers edit one file, not 50 seeder rows |
| Incremental modules | Only enable/sync modules when wired — no orphan permissions |
| Developer always full access | Sync always grants **all active** matrix permissions to Developer role |
| Extra actions supported | Not only CRUD — any named action per module |
| Safe re-runs | Idempotent upsert by `slug` — run sync anytime |
| Plan / sidebar ready | Same matrix drives menu visibility + plan `modules_json` later |

#### File location (proposed)

```
config/permission_matrix.php
```

Optional companion (docs / non-PHP): `docs/permission-matrix.md` — but **runtime source = PHP config** so Artisan can sync without parsers.

#### Matrix structure (example)

```php
<?php
// config/permission_matrix.php

return [

    /*
    | Default CRUD actions applied when a module sets 'use_defaults' => true
    | Soft-delete modules also get 'restore' when 'soft_delete' => true
    */
    'default_actions' => [
        'view'   => 'View',
        'add'    => 'Add',
        'edit'   => 'Edit',
        'delete' => 'Delete',
    ],

    /*
    | Roles that ALWAYS receive every active permission on sync
    | Developer = main developer / platform god-mode role
    */
    'auto_assign_roles' => [
        'developer',        // platform main developer account role
        // 'platform_admin', // optional alias
    ],

    'modules' => [

        // ---- already / soon dynamic ----
        'dashboard' => [
            'name'          => 'Dashboard',
            'enabled'       => true,   // module dynamic → permissions install
            'use_defaults'  => false,
            'actions'       => [
                'view' => 'View Dashboard',
            ],
        ],

        'branch' => [
            'name'          => 'Branch',
            'enabled'       => true,   // jem jem dynamic → true karo
            'use_defaults'  => true,   // view, add, edit, delete
            'soft_delete'   => true,   // + restore
            'actions'       => [],     // extras empty
        ],

        'batch' => [
            'name'         => 'Batch',
            'enabled'      => false,  // haju mock — sync skip / inactive
            'use_defaults' => true,
            'soft_delete'  => true,
            'actions'      => [
                'attendance' => 'Mark Batch Attendance', // extra when ready
            ],
        ],

        'inquiry' => [
            'name'         => 'Inquiry',
            'enabled'      => false,
            'use_defaults' => true,
            'soft_delete'  => true,
            'actions'      => [
                'convert' => 'Convert to Student',  // EXTRA action
            ],
        ],

        'fees' => [
            'name'         => 'Fees',
            'enabled'      => false,
            'use_defaults' => true,
            'soft_delete'  => true,
            'actions'      => [
                'collect'  => 'Collect Fees',
                'export'   => 'Export Fees',
                'followup' => 'Fees Follow-up',
            ],
        ],

        'student' => [
            'name'         => 'Student',
            'enabled'      => false,
            'use_defaults' => true,
            'soft_delete'  => true,
            'actions'      => [
                'export' => 'Export Students',
            ],
        ],

        'attendance' => [
            'name'         => 'Attendance',
            'enabled'      => false,
            'use_defaults' => false,
            'actions'      => [
                'view'  => 'View Attendance',
                'mark'  => 'Mark Attendance',
                'edit'  => 'Edit Attendance',
            ],
        ],

        'exam' => [
            'name'         => 'Exam',
            'enabled'      => false,
            'use_defaults' => true,
            'soft_delete'  => true,
            'actions'      => [
                'result' => 'Manage Results',
            ],
        ],

        'settings' => [
            'name'         => 'Settings',
            'enabled'      => true,
            'use_defaults' => false,
            'actions'      => [
                'view' => 'View Settings',
                'edit' => 'Edit Settings',
            ],
        ],

        // … add every module here as you build it:
        // classes, teacher, staff, subject, standard, follow_up,
        // homework, study_material, notice, holiday, leave,
        // income_expense, inventory, gst_invoice, todo, roles, …
    ],
];
```

#### Sync behaviour (`php artisan permissions:sync`)

Run on deploy / after enabling a module / in `DatabaseSeeder`.

```
For each module in matrix:
  if enabled = false:
      → upsert permissions with is_active=false (optional) OR skip insert
  if enabled = true:
      → build action list = default_actions (+ restore if soft_delete) + custom actions
      → for each action:
          slug = "{module}.{action}"   e.g. branch.view, inquiry.convert
          UPSERT into permissions (by slug)
          is_active = true
  Then for each role slug in auto_assign_roles (e.g. developer):
      → attach ALL active permissions (sync without detaching extras from other roles)
```

**Idempotent:** baar-baar sync safe — duplicate rows nahi; new actions only insert; renamed labels update.

#### Developer workflow (module-by-module)

```
1. Branch UI + Controller + Model ready
2. config/permission_matrix.php → branch.enabled = true
3. php artisan permissions:sync
4. permissions table:
     branch.view, branch.add, branch.edit, branch.delete, branch.restore
5. role_permission: all attached to role slug = developer
6. Middleware/Policy: @can('branch.add') / permission:branch.add
7. Sidebar shows Branch only if user has branch.view
```

Next module (e.g. Inquiry) same steps — matrix ma `convert` already define hoy to sync thi `inquiry.convert` auto insert + Developer ne assign.

#### Full starter matrix (all known UI modules)

| Module key | Default CRUD | Soft restore | Extra actions |
|------------|--------------|--------------|---------------|
| dashboard | view only | — | — |
| classes | ✓ | ✓ | — |
| branch | ✓ | ✓ | — |
| batch | ✓ | ✓ | attendance |
| teacher | ✓ | ✓ | — |
| staff | ✓ | ✓ | — |
| subject | ✓ | ✓ | — |
| standard | ✓ | ✓ | — |
| student | ✓ | ✓ | export |
| inquiry | ✓ | ✓ | convert |
| follow_up | ✓ | ✓ | — |
| fees | ✓ | ✓ | collect, export, followup |
| gst_invoice | ✓ | ✓ | generate, download |
| attendance | view, mark, edit | — | — |
| exam | ✓ | ✓ | result |
| result_announcement | ✓ | ✓ | publish |
| homework | ✓ | ✓ | — |
| study_material | ✓ | ✓ | download |
| notice | ✓ | ✓ | publish |
| holiday | ✓ | ✓ | — |
| student_leave | ✓ | ✓ | approve, reject |
| staff_leave | ✓ | ✓ | approve, reject |
| income_expense | ✓ | ✓ | — |
| inventory | ✓ | ✓ | — |
| todo | ✓ | ✓ | — |
| roles | ✓ | ✓ | assign_permissions |
| permissions | view | — | sync (Super Admin / Developer only) |
| settings | view, edit | — | — |
| project_inquiry | ✓ | ✓ | confirm_demo, decline_demo |
| demo_calendar | view | — | — |
| demo_availability | ✓ | ✓ | — |

`enabled: false` = haju mock; true kari ne sync = live permissions.

#### Rules

1. **Never** manually insert production permissions in DB — always via matrix + sync.  
2. New extra action? Matrix ma add → sync → done.  
3. Module disable? `enabled=false` + sync → `is_active=false` (optional detach from non-developer roles; Developer keeps history or force re-sync policy — recommend: deactivate permission, hide from UI, keep role_permission rows).  
4. Institute custom roles: Admin assigns subset from **active** permissions only.  
5. Platform **Developer** role: always full matrix (god mode).  
6. Controllers declare required permission once; Policies read same slug.  

#### Code touchpoints

| Piece | Path |
|-------|------|
| Matrix config | `config/permission_matrix.php` |
| Sync command | `app/Console/Commands/SyncPermissionsCommand.php` |
| Seeder calls sync | `DatabaseSeeder` / `PermissionSeeder` |
| Gate | `AppServiceProvider` or `PermissionService` |
| Middleware | `permission:branch.add` |
| Blade | `@can('branch.edit')` |

#### `role_permission` note with matrix (Spatie `role_has_permissions`)

- **Developer / platform_admin:** full sync from matrix (auto).  
- **Institute roles (Admin, Teacher, Principal…):** assigned manually in Roles UI from active permissions — OR optional `default_role_grants` block in matrix later (e.g. Teacher gets `attendance.mark` by default on new institute).  

Optional Phase 1.5 extension:

```php
'default_role_grants' => [
    'institute_admin' => ['*'],  // all enabled modules
    'principal' => ['branch.view', 'batch.*', 'student.*', 'attendance.*', 'exam.*', 'notice.*'],
    'teacher' => ['attendance.*', 'homework.*', 'exam.view', 'exam.add', 'exam.result'],
    'accountant' => ['fees.*', 'gst_invoice.*', 'income_expense.*'],
],
```

---

#### Spatie Permission — package tables + our modifications

Use `spatie/laravel-permission` with **teams** enabled (`team_id` = `institute_id`).

| Spatie / custom | Modification |
|-----------------|--------------|
| `permissions` | Add columns: `module`, `action`, `is_active`, `sort_order`, `description` |
| `roles` | Add: `parent_role_id`, `image_path`, `is_system`, `status`, `deleted_at`; `team_id` null = platform roles (`developer`) |
| `model_has_roles`, `role_has_permissions`, … | Spatie default + team_id |
| `role_assignable` | **Custom pivot** (Spatie nathi) — UI “Assignable Roles” |
| `branch_user` | **Custom** — data scope (Principal / Branch Manager) |

**Matrix sync** upserts Spatie `permissions` by `name`/`slug` (= `branch.view`) and syncs `developer` role permissions. Blade still uses `@can('branch.edit')` / `$user->can(...)`.

Do **not** create a parallel custom permissions system — only extend Spatie.

#### `branch_user` — branch scope

| Field | Notes |
|-------|-------|
| user_id, branch_id | User can access these branches |
| is_primary | default branch |

#### `login_logs`

| Field | Example |
|-------|---------|
| user_id, institute_id | |
| event | login / logout / failed |
| ip, user_agent, logged_at | |

---

### 7.3 Academic masters

#### `branches`

| Field | From UI | Example |
|-------|---------|---------|
| institute_id | Classes Name | 10 |
| name | Branch Name | Mehsana Main |
| code | optional | MEH-01 |
| contact_person | Branch Contact Person | Suresh |
| email, phone | | |
| address, city, state, pincode | | |
| manager_user_id | FK users nullable | |
| status | Active/Inactive | |
| deleted_at | | |

**Who adds:** Classes Admin · **Who updates:** Admin / Branch Manager · **Who deletes (soft):** Admin only

#### `standards`

| Field | Example |
|-------|---------|
| institute_id | |
| name | 10th |
| sort_order | 10 |
| status | active |
| deleted_at | |

#### `subjects`

| Field | Example |
|-------|---------|
| institute_id | |
| name | Maths |
| code | MTH |
| status | |
| deleted_at | |

#### `batches`

| Field | From UI | Example |
|-------|---------|---------|
| institute_id, branch_id | | |
| name | Morning A | |
| standard_id | | |
| start_time, end_time | or timing text | 08:00–09:00 |
| capacity | optional | 40 |
| academic_year | | 2026-27 |
| status | | |
| deleted_at | | |

#### `batch_subject` (pivot)

`batch_id`, `subject_id`, `teacher_user_id` nullable, `fees_amount` nullable

#### `batch_teacher` (if multi-teacher)

`batch_id`, `teacher_user_id`, `is_primary`

---

### 7.4 People profiles

#### `teachers` (profile; login via `users`)

| Field | From UI | Example |
|-------|---------|---------|
| user_id | unique FK | |
| institute_id | | |
| specialization | Maths | |
| address | | |
| expected_login_time | Login Time | 08:00 |
| grace_minutes | Grace Time | 10 |
| joining_date | | |
| salary meta optional | | |
| deleted_at | | |

Branch/batch via `branch_user` + `batch_teacher`.

#### `staff` (non-teacher employees)

| Field | From UI | Example |
|-------|---------|---------|
| user_id | FK | |
| institute_id, branch_id | | |
| designation | Peon / Receptionist | |
| address, city, state, pincode | | |
| expected_login_time, grace_minutes | | |
| deleted_at | | |

Role via `user_role` (Accountant, Peon…).

#### `parents`

| Field | Example |
|-------|---------|
| user_id | FK nullable (if has login) |
| institute_id | |
| father_name, mother_name | |
| email, phone | used to match existing parent |
| occupation | |
| address | |
| deleted_at | |

#### `students`

| Field | From UI | Example |
|-------|---------|---------|
| user_id | optional (if student login) | |
| institute_id | | |
| parent_id | FK parents | |
| admission_no | unique per institute | ADM-2026-001 |
| name | Student Name | |
| dob, gender | | |
| photo_path | | |
| medical_notes | | |
| aadhaar | encrypted/masked preferred | |
| category | General/OBC/SC/ST | |
| admission_date | | |
| school_name | | |
| standard_id | current standard | |
| form_fees, form_fees_payment_method | | |
| address | | |
| status | active/left/alumni | |
| deleted_at | | |

#### `student_batch` — enrollment (student can join many batches)

| Field | From UI Step 2 | Example |
|-------|----------------|---------|
| student_id, batch_id, subject_id | | |
| roll_no | | 12 |
| total_fees | | 15000 |
| discount | | 500 |
| status | active/completed/dropped | |
| enrolled_at | | |

#### `student_parent` — if multiple guardians needed later

`student_id`, `parent_id`, `relation` (father/mother/guardian)

---

### 7.5 Inquiry CRM (institute-level — separate from platform demos)

#### `inquiries`

| Field | Example |
|-------|---------|
| institute_id, branch_id | |
| inquiry_code | INQ-001 |
| student_name, parent_name, phone, email | |
| standard_id, interested_subject | |
| source | walk-in/website/referral |
| status | new/followup/converted/lost |
| assigned_to | user_id |
| converted_student_id | nullable |
| notes | |
| deleted_at | |

#### `inquiry_follow_ups`

| Field | Example |
|-------|---------|
| inquiry_id | |
| follow_up_at | |
| remark | Called, visit next week |
| next_follow_up_at | |
| created_by | |

---

### 7.6 Fees

#### `fee_structures` (optional templates)

`institute_id`, `batch_id` or `standard_id`, `name`, `amount`, `installments_json`

#### `fee_invoices` (student fee ledger)

| Field | Example |
|-------|---------|
| institute_id, branch_id, student_id, student_batch_id | |
| total_amount, discount, net_amount | 15000 |
| paid_amount, due_amount | |
| status | unpaid/partial/paid |
| due_date | |
| deleted_at | |

#### `fee_payments`

| Field | From Add Fees UI | Example |
|-------|------------------|---------|
| fee_invoice_id, student_id | | |
| amount | | 2000 |
| payment_date | | |
| payment_method | Cash/UPI/Card/Bank | |
| receipt_no | | RCP-001 |
| transaction_ref | | |
| collected_by | user_id | |
| note | | |
| deleted_at | | |

#### `fee_follow_ups`

`fee_invoice_id`, `follow_up_at`, `remark`, `created_by`, `status`

#### `gst_invoices`

`institute_id`, `fee_payment_id` nullable, `gstin`, `invoice_no`, `taxable`, `cgst`, `sgst`, `igst`, `total`, `pdf_path`, `deleted_at`

---

### 7.7 Attendance & Leave

#### `student_attendances`

| Field | Example |
|-------|---------|
| institute_id, branch_id, batch_id | |
| student_id, date | |
| status | present/absent/late/half_day |
| marked_by | user_id |
| remark | |
| unique(student_id, batch_id, date) | |
| deleted_at | |

#### `staff_attendances`

| Field | Example |
|-------|---------|
| institute_id, branch_id, user_id | teacher/staff |
| date | |
| check_in_at, check_out_at | |
| status | present/absent/late |
| late_minutes | vs expected_login + grace |
| marked_by / source | auto_login / manual |
| deleted_at | |

#### `leave_requests`

| Field | Example |
|-------|---------|
| institute_id | |
| requester_user_id | |
| requester_type | student/staff |
| student_id nullable | if student leave |
| from_date, to_date | |
| reason | |
| status | pending/approved/rejected |
| reviewed_by, reviewed_at, review_note | |
| deleted_at | |

#### `holidays`

`institute_id`, `branch_id` nullable (null=all), `title`, `date` or from/to, `deleted_at`

---

### 7.8 Academics content

#### `homeworks`

`institute_id`, `batch_id`, `subject_id`, `teacher_user_id`, `title`, `description`, `due_date`, `attachment_path`, `deleted_at`

#### `study_materials`

`institute_id`, `batch_id` nullable, `subject_id`, `title`, `file_path`, `uploaded_by`, `deleted_at`

#### `exams`

`institute_id`, `batch_id`, `subject_id`, `name`, `exam_date`, `total_marks`, `status`, `deleted_at`

#### `exam_results`

`exam_id`, `student_id`, `marks_obtained`, `grade`, `remark`, `deleted_at`

#### `result_announcements`

`institute_id`, `title`, `body`, `exam_id` nullable, `published_at`, `deleted_at`

#### `notices`

`institute_id`, `branch_id` nullable, `title`, `body`, `audience` (all/teachers/parents/students), `publish_at`, `deleted_at`

#### `todos`

`institute_id`, `assigned_to`, `title`, `due_at`, `status`, `created_by`, `deleted_at`

---

### 7.9 Finance ops & inventory

#### `ie_categories` (income/expense categories)

`institute_id`, `type` (income/expense), `name`, `deleted_at`

#### `income_expenses`

`institute_id`, `branch_id`, `category_id`, `type`, `amount`, `entry_date`, `payment_method`, `note`, `attachment`, `created_by`, `deleted_at`

#### `asset_categories`

`institute_id`, `name`, `deleted_at`

#### `inventory_assets`

`institute_id`, `branch_id`, `category_id`, `name`, `quantity`, `purchase_date`, `purchase_amount`, `status`, `note`, `deleted_at`

---

### 7.10 System helpers (development ease)

#### `media`

`institute_id` nullable, `model_type`, `model_id`, `collection`, `disk`, `path`, `mime`, `size`, `uploaded_by`, `deleted_at`

#### `activity_logs`

`institute_id` nullable, `user_id`, `action`, `subject_type`, `subject_id`, `old_values` json, `new_values` json, `ip`, `created_at`

#### `notifications` (DB channel)

`id` uuid, `type`, `notifiable_type/id`, `data` json, `read_at`

#### `settings` (DB) — optional later replace JSON file

`institute_id` nullable (null=platform), `group`, `key`, `value` (encrypted if secret)

#### `api_tokens` — Sanctum (Phase 8)

Built-in `personal_access_tokens`

---

## 8. Entity Relationship (simplified)

```
plans 1───* subscriptions *───1 institutes
institutes 1───* users
institutes 1───* roles *───* permissions
institutes 1───* branches 1───* batches
batches *───* subjects (batch_subject)
batches *───* teachers (batch_teacher)
institutes 1───* students *───* batches (student_batch)
students *───1 parents → users (parent login)
students 1───* fee_invoices 1───* fee_payments
batches 1───* student_attendances
users (staff/teacher) 1───* staff_attendances
```

---

## 9. Field-level CRUD ownership (examples)

| Table | Create | Update | Soft Delete | Restore | View |
|-------|--------|--------|-------------|---------|------|
| institutes | Super Admin / Self-register | Classes Admin (limited) / Super Admin | Super Admin | Super Admin | Super Admin global; Admin own |
| branches | Classes Admin | Admin, Branch Manager | Admin | Admin | Scoped users |
| batches | Admin | Admin, Principal | Admin | Admin | Branch-scoped |
| students | Admin, Receptionist (if perm) | same | Admin only | Admin | Branch/batch scoped |
| fee_payments | Accountant, Admin | Admin (limited) | Admin | Admin | Accountant scoped |
| attendance | Teacher (own batch), Admin | Teacher (same day), Admin | Admin | Admin | Teacher/Admin/Parent(own child) |
| roles | Classes Admin | Admin | Admin (non-system) | Admin | Admin |
| plans | Super Admin only | Super Admin | Super Admin | Super Admin | Public (is_public) |

---

## 10. API Design (future-ready now)

Even while building Blade+AJAX, structure Controllers so API reuse is easy:

```
app/Http/Controllers/Admin/...     ← session + JSON for AJAX
app/Http/Controllers/Api/V1/...    ← Sanctum later (thin wrappers / shared Actions)
app/Actions/... or app/Services/   ← business logic
app/Http/Resources/...             ← JSON shape
```

### Suggested `/api/v1` groups

| Prefix | Consumers |
|--------|-----------|
| `/api/v1/auth` | All apps |
| `/api/v1/parent/*` | Parent app |
| `/api/v1/teacher/*` | Teacher app |
| `/api/v1/student/*` | Student app |
| `/api/v1/admin/*` | Optional mobile admin |

Same permissions + institute scopes apply.

---

## 11. Website / Marketing enhancements (beyond current UI)

Already have: Home, About, Features, Pricing, Privacy, Book Demo.

**Recommended extras for SaaS growth & easier sales:**

| Extra | Why |
|-------|-----|
| **Register / Start Free Trial** page | Convert after demo without manual setup |
| **SEO city pages** (`/coaching-software-mehsana`) | Organic leads |
| **Blog / Case studies** | SEO + trust |
| **Compare plans interactive** (module matrix) | Tied to `plans.modules_json` |
| **Customer login** | `{slug}.ivclasses.com/login` (subdomain) |
| **Status page / changelog** | Trust |
| **Onboarding wizard** inside admin | Reduces support load |
| **Referral code** on register | Growth |

---

## 12. Development Standards (so build stays easy)

1. **One Service per domain** — e.g. `StudentService`, `FeeCollectionService`  
2. **Form Requests** for every write  
3. **Policies** mirror permissions  
4. **Global scopes** for `institute_id`  
5. **SoftDeletes** everywhere business-critical  
6. **AJAX** responses: `{ success, message, data, errors? }`  
7. **Seeders:** Super Admin, Developer role, `permissions:sync` from matrix, 3 plans, demo institute  
8. **Permission Matrix** is source of truth — never hand-insert permissions in prod  
9. **Feature flags** per plan modules (hide sidebar items) — align keys with matrix module keys  
10. **Factories + Feature tests** for Phase 1–3 critical paths + sync idempotency test  
11. **Enum PHP classes** for statuses  
12. **Observer/Activity log** on key models  
13. **Queue** emails (already Mailables pattern exists)  
14. **Never** expose other institute data — automated test for tenancy leak  
15. **Always update `TRACKER.md`** after every work session (small or big) — see **§12.1**

### 12.1 Development Tracker (`TRACKER.md`) — project memory

**File:** repo root `TRACKER.md` (aek jat nu — single source of work memory).

Jyaare phase-wise kam chalu thay, chat / dimag par rely nathi — **har change TRACKER ma update**. Small fix, big feature, error, retry — badhu aek file ma rahe.

#### Why

| Problem | Tracker solves |
|---------|----------------|
| AI / human session bhooli jay | Phase status + last decisions file ma |
| “Shu thayu hato?” | Changelog by date |
| Same error farithi | Errors & fixes log |
| PRD vs reality drift | Tracker = what shipped; PRD = what planned |

#### Rules (mandatory)

1. **Phase start** → tracker ma phase `In Progress`, checklist unchecked items list  
2. **After every change** (small/big) → navi entry: date, phase, type, summary, files touched  
3. **Error / fail / retry** → Errors section ma: symptom → cause → fix → status  
4. **Phase complete** → mark Done + exit criteria note  
5. **Do not** scatter progress across random notes — only `TRACKER.md`  
6. Agent/developer: kam complete kare tyare **same turn** ma tracker update (bhoolvu nahi)

#### Entry types

| Type | Example |
|------|---------|
| `feat` | Branch CRUD AJAX wired |
| `fix` | Soft delete + restore on students |
| `fix` | Login redirect for subdomain |
| `refactor` | Fee service extracted |
| `chore` | Spatie installed, matrix seeded |
| `error` | 500 on permissions:sync — fixed missing team_id |
| `decision` | Used Stripe test mode only for Phase 7 local |

#### Tracker structure (required sections)

```markdown
# IVClasses — Development Tracker

## Current focus
- Phase: …
- Status: Not started | In Progress | Blocked | Done
- Last updated: …

## Phase checklist
### Phase 0 … 
### Phase 1 …
(checkboxes — mirror PRD phases)

## Change log (newest first)
| Date | Phase | Type | Summary | Files / notes |
|------|-------|------|---------|---------------|

## Errors & fixes
| Date | Phase | Error | Cause | Fix | Status |
|------|-------|-------|-------|-----|--------|

## Open blockers
- …

## Notes / memory
- Short bullets AI + human both read next session
```

Full template lives in root **`TRACKER.md`** and stays updated for the life of the project.

### Suggested folder layout addition

```
app/
  Domain/ or Modules/
    Institute/
    Academic/
    Fees/
    Attendance/
    Crm/
  Console/Commands/SyncPermissionsCommand.php
  Support/
    Traits/BelongsToInstitute.php
    Traits/HasSoftDeleteWithUser.php
    Scopes/InstituteScope.php
    Services/PermissionSyncService.php
config/
  permission_matrix.php          ← MASTER matrix
TRACKER.md                       ← living phase/change/error memory (always update)
prd.md                           ← product requirements (plan)
```
---

## 13. Plan limits enforcement (examples)

| Plan | max_branches | max_students | Modules |
|------|--------------|--------------|---------|
| Free Trial | 1 | 50 | Core CRM |
| Starter | 1 | 200 | inquiry, student, fees, attendance, homework |
| Growth | 5 | 1000 | + exam, GST, leave, staff, follow-up |
| Enterprise | unlimited | unlimited | all + inventory + custom roles |

On create Branch/Student: check subscription limits → 403 JSON with upgrade CTA.

---

## 14. Non-Functional Requirements

| Area | Target |
|------|--------|
| Security | CSRF, hashed passwords, signed URLs (existing), permission checks, encrypted secrets |
| Privacy | Soft delete; Aadhaar masked; parent/student data institute-isolated |
| Performance | Index `institute_id`, `branch_id`, `student_id`, dates; DataTables server-side later |
| UX | No full reload on CRUD; **SweetAlert2 via `AdminAjax`** — confirm before delete, success after create/update/delete, error on failure (see §16.8) |
| Audit | Login logs + activity logs |
| Backup | Daily DB backup (ops) |

---

## 15. Test Cases (high-level — expand per phase)

### Tenancy

- [ ] User of Institute A cannot GET/PUT Institute B student  
- [ ] Super Admin can list all institutes  
- [ ] Suspended institute cannot login  

### RBAC

- [ ] Accountant without `student.delete` cannot soft-delete  
- [ ] Teacher marks attendance only for assigned batches  
- [ ] Role module matrix hides sidebar items  
- [ ] `permissions:sync` is idempotent (run twice → same row count)  
- [ ] Enabling `branch` in matrix + sync creates `branch.view|add|edit|delete|restore`  
- [ ] Developer role auto-receives new permissions after sync  
- [ ] Extra action `inquiry.convert` appears only when defined + module enabled  

### SaaS funnel

- [ ] Book demo → inquiry created → admin confirm → Meet mail  
- [ ] Register → trial subscription → institute admin login  
- [ ] Trial expiry → read-only or block write  

### Soft delete

- [ ] Deleted student hidden from default list  
- [ ] Restore brings back enrollments visibility rules  

### Fees

- [ ] Partial payment updates due correctly  
- [ ] Follow-up-only does not create payment  

### Attendance

- [ ] Duplicate mark same day upserts, not duplicates  
- [ ] Late calculated using grace_minutes  

---

## 16. Locked Decisions (answers confirmed)

| # | Question | Decision |
|---|----------|----------|
| 1 | Tenant URL | **Subdomain** — `{slug}.ivclasses.com` for institute CRM; central domain for marketing + Platform Admin |
| 2 | Free plan | **Time-limited trial only** (e.g. 14 days) — no forever-free |
| 3 | Payment | **Razorpay + Stripe both** (gateway interface; India + international) |
| 4 | Email uniqueness | **Global unique** (AI recommendation adopted — see §16.4) |
| 5 | Student login | **Parent-only first**; Student portal/app later phase |
| 6 | Principal / Branch Manager | **Separate login portals** (`/principal/login`, `/branch/login`) + Spatie role / manager_user_id — not same login; not separate user_type tables (see §16.6 v1.4) |
| 7 | UI | **Keep current UI look**; wire backend; **allowed deltas** listed in §16.7 |
| 8 | Permissions package | **Spatie laravel-permission** + teams + matrix sync + small schema mods |

---

### 16.4 Email: global unique vs per-institute — recommendation

| Approach | Pros | Cons |
|----------|------|------|
| **Global unique** ✅ chosen | One login identity; parent app simple; password reset simple; Spatie/Sanctum simple; industry default (Notion, Slack-style email) | Same person cannot be “two different users” with same email at two institutes without multi-membership design |
| Unique per institute | Same email at Institute A and B as separate accounts | Confusing logins on subdomain; reset/token hell; duplicate parent rows |

**Best practice for this SaaS:** global unique email.

**If one teacher works at 2 institutes later:** support **multi-institute membership** (same `users` row + multiple `model_has_roles` with different `team_id`), not duplicate emails. Subdomain picks active institute context.

---

### 16.6 Principal vs Branch Manager — roles + separate logins

**Confusion:** Principal ane Branch Manager same login? Alag user_type?

**Samjan (v1.4 locked):**

- `user_type` stays shared for CRM staff (`institute_staff`) — no `principals` table  
- **Spatie Role** = authority (`principal`, accountant, receptionist…)  
- **Branch Manager** = `branches.manager_user_id` (+ staff checkbox) — ops for one branch  
- **Login portal** = separate secure entry (wrong portal = generic auth failure)

| Who | Login URL | How identified |
|-----|-----------|----------------|
| Platform admin | `/platform/login` | `user_type=platform_admin` |
| Institute admin / accountant / receptionist / teacher | `/institute/login` | CRM users who are **not** Principal and **not** Branch Manager |
| Principal | `/principal/login` | Spatie role `principal` only |
| Branch Manager | `/branch/login` | Created on Add Branch (`manager_user_id` + login password) |
| Parent | `/parent/login` | `user_type=parent` |

**Hard rule:** Same person **cannot** be Principal + Branch Manager. Staff form + Branch manager picker enforce this. Permissions still via Roles UI; data scope still via `branch_user` / `BranchScope`.

```
User (email unique, institute_staff)
  ├── Spatie role: principal     → /principal/login
  └── OR manager_user_id on branch → /branch/login
      (never both)
```

Classes Admin = almost all permissions + all branches (`/institute/login`).  
Principal = high academic/ops permissions + assigned branch(es) (`/principal/login`).  
Branch Manager = created on **Add Branch** (name + email + password) → `/branch/login` — not from Staff form.

---

### 16.7 UI policy + advanced additions (keep base UI)

**Policy:** Existing Blade admin theme / page layouts **rahe** — pehla backend wire. Full redesign nathi.

**Allowed changes / new screens** (prd-approved deltas — jem jem phase ave):

| Item | Type | Why |
|------|------|-----|
| AJAX modals / SweetAlert confirm + success | Change | No page reload CRUD — **`AdminAjax` standard (§16.8)** |
| Trash / Restore filters on list pages | Add | Soft delete UX — **still open** |
| Permission-based sidebar hide/show | Change | Spatie `@can` |
| Branch switcher (header) for multi-branch users | Add | Principal / staff scope — optional |
| Subdomain-aware login page (institute logo/name) | Add | Tenant branding |
| **Register / Start Trial** website page | Add | Funnel after demo ✅ |
| Trial countdown banner + upgrade CTA | Add | Conversion |
| Checkout pages (Razorpay + Stripe) | Add | Phase 7 ✅ (bypass + gateways) |
| Gateway settings (keys) in Platform Settings | Add | Super Admin |
| Plan limits warning modals | Add | max branches/students |
| Spatie-backed Roles UI (keep design, real API) | Change | Replace mock ✅ |
| Permission sync status (Developer only) | Add | Last sync time / matrix version |
| Onboarding wizard (first login) | Add | Branch → Standard → Batch checklist — **still open** |
| Activity / login log screens | Add | Audit — login history on teacher/staff view ✅; full activity UI open |
| **In-module Export CSV / Print PDF** | Add | Phase 9 — **no separate Reports menu** ✅ |
| Fee / inquiry / student receipts | Add | Printable Blade + browser print ✅ |
| Parent app screens (API consumers) | Later | Phase 8 — not admin Blade |
| Student login UI | Later | After parent-first |
| Server-side DataTables (when data large) | Change | Performance |
| Empty states + “module not in plan” upgrade | Add | SaaS UX |

**Not in V1 unless asked:** full theme redesign, new card layouts that break Sneat admin look, Student web portal, dedicated Reports hub in sidebar.

---

### 16.8 Admin SweetAlert / AJAX UX standard (locked)

All admin list/form CRUD feedback **must** use shared helpers in `public/admin-assets/js/admin-ajax.js` (`window.AdminAjax`) + SweetAlert2 (already in layout). Do **not** use browser `confirm()` / `alert()` for delete or save success on wired modules.

| Action | Required UX |
|--------|-------------|
| **Delete** | `AdminAjax.confirm({ title, text?, confirmText: 'Yes, delete', icon: 'warning' })` → `AdminAjax.destroy(url)` → remove row (`AdminAjax.removeRow`) → `AdminAjax.success(message)` |
| **Create / Update** (AJAX form or modal) | `AdminAjax.submitForm` **or** fetch + `AdminAjax.success(message)` then redirect/reload/close modal |
| **Error** | `AdminAjax.error(message)` (validation / permission / server) |
| **Backend** | JSON `{ success, message, data }` via `App\Support\Ajax`; `expectsJson()` → JSON, else redirect flash |

**Do not:** delete without confirm; delete with confirm but skip success Swal; mix native `confirm` with Swal on the same page; return HTML redirect-only for AJAX delete when the front expects JSON.

**Reference modules:** Module Help, Standard/Subject, Email Templates, Exam, Result Announcement, Holiday, Staff/Student Leave (approve/reject).

---

### 16.8 Spatie + matrix — modification checklist

1. Install `spatie/laravel-permission`  
2. Enable **teams**; `team_foreign_key` = institute  
3. Migrate extra columns on `permissions` / `roles`  
4. Keep `config/permission_matrix.php` + `permissions:sync` writing **into Spatie tables**  
5. Platform role `developer` (`team_id` null) auto-gets all permissions  
6. On institute subdomain request: `setPermissionsTeamId($institute->id)`  
7. Existing Roles / Permissions Blade → AJAX to Spatie APIs  
8. Custom: `role_assignable`, `branch_user` only  

---

## 17. Success Metrics

| Metric | Target (6 months) |
|--------|-------------------|
| Demo → Trial conversion | ≥ 30% |
| Trial → Paid | ≥ 25% |
| Time to first branch+batch setup | < 30 min |
| Daily active institute admins | Growing MoM |
| Critical tenancy bugs | 0 in production |

---

## 18. Confirmation Checklist

- [x] §16 Open Decisions — **answered & locked in v1.2**  
- [x] Hierarchy & Principal explanation OK (§16.6)  
- [x] Phases 0–6 shipped (see TRACKER); Phase 7 mostly done; Phase 8–9 next  
- [x] Schema tables largely implemented; gaps tracked in TRACKER Remaining  
- [x] UI delta list OK (§16.7) — exports/receipts added in v1.5  
- [ ] Anything **must-have** still missing — renewals cron, Sanctum API, SEO/import/WhatsApp/tests  

**Next step:** Phase 7 renewals/dunning when gateways live **or** Phase 8 Sanctum Parent/Teacher API **or** Phase 9 leftovers (import, trash UI, tests).  
**Every work session:** update root **`TRACKER.md`** (phase status + changelog + errors) — see §12.1.

---

*End of PRD v1.5 — IVClasses Dynamic SaaS*
