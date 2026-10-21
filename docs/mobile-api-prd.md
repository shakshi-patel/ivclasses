# Class Flow Mobile API — Full PRD & Build Plan

As of 19 Oct 2026. This supersedes `mobile-api-prd-phase-1-branch.md` (that file only covered the Branch Manager role; this one covers the complete mobile API surface for every role except Platform Admin and Institute Admin, module by module, so nothing on web is missed).

**App developers:** hand them `docs/mobile-api-app-developer-guide.md` (screens, auth, request bodies, mistakes). This PRD is the product/build plan.

## 0. How to Use This Document

This is a PRD and a build plan in one file:

- **§1–§4** = the PRD part: why, who, the architecture decisions, and the access rules.
- **§5** = the build plan's core: every module in the system, grouped logically, each with its proposed REST endpoints. Build straight off this section, module by module.
- **§6–§9** = scope boundary, phase order, non-functional requirements, and decisions still needed from you before/while building.

## 1. Goal & Scope Boundary

Build a versioned REST API (Laravel Sanctum) that gives the mobile app **everywhere near full parity with the web app** — every module, every action, every "small thing" (receipts, PDF/CSV exports, status toggles, tab data) — for every role **except**:

- **Platform Admin** — stays web-only. Runs the whole network (institutes, plans, billing, demo pipeline); not a mobile use case.
- **Institute Admin** — stays web-only, by your explicit instruction. Admin-only surfaces (Roles & Permissions, Settings, Classes Branding, Google Calendar, Email Templates, institute-level Billing) are therefore out of scope too, because no mobile-logged-in role reaches them by default (§6 has the full list and the one caveat that matters: custom role permissions).

Every other role — **Branch Manager, Principal, Accountant, Receptionist, Teacher, Parent** — gets a mobile login and, module by module, the same capability they have on web today.

**Key principle: the API is permission-driven, not role-hardcoded.** This codebase's Role & Permissions screen lets an Institute Admin grant almost any permission to almost any custom role — the six role "templates" in `RoleTemplateService` are each institute's *starting point*, not a hard ceiling. So every endpoint below is gated the exact same way its web counterpart is gated — `$user->can('module.action')`, same permission matrix, same per-user overrides (`User::isPermissionDenied()`) — never "if role = branch_manager, show this." That means: build the full module surface once, and whatever an institute's admin has actually granted to a role (default or customized) is what that role's mobile app can do. Nothing needs to be re-built when an institute changes its permission setup.

## 2. Roles in Mobile Scope

| Role | Who they are | Scoping | Default capability (today, on web) |
| --- | --- | --- | --- |
| Branch Manager | Runs one branch (or a few assigned branches) day to day | `BranchScope` — assigned branch(es) via `branch_user` pivot | Widest non-admin role: students, inquiries, attendance, batches, fees (view), exams/homework/notices, directory |
| Principal | Same shape as Branch Manager today (identical default template) | Same as Branch Manager | Identical to Branch Manager by default — institutes can diverge the two roles later |
| Accountant | Owns money — fee collection, GST, income/expense | Institute-wide (not branch-restricted by default) | Fees (incl. `fees.add` — the one thing Branch Manager can't do), GST invoices, Income & Expense (+ category), student view |
| Receptionist | Front desk — inquiries, walk-ins, new admissions | Institute-wide by default | Inquiry, Follow Up, Student (view + add), Batch (view) |
| Teacher | Runs their own assigned batches/subjects | **Own-batches scoping** (`batch_teacher`/`batch_subject` pivot), not `BranchScope` | **Minimal (option B, decided 2026-10-20)** — `teacher_dashboard.*` + `batch.view` only. Attendance/homework/exam-result stay per-institute via Role & Permissions. API still exposes the full teacher surface, scoped to own batches, so granted perms work. |
| Parent | Views one (or more) child's progress | Scoped to their own child/children via `parents`/`students.parent_id` | Read-mostly: attendance, homework, study material, notices, exams, results, can apply student leave |

## 3. Architecture

**Auth — Laravel Sanctum (new dependency).** `composer.json` has no API auth package yet. Add `laravel/sanctum`, migrate `personal_access_tokens`, add `HasApiTokens` to `User`. Mobile authenticates with email + password against a dedicated endpoint (same credential check as `LoginRequest::authenticate()`) and gets a bearer token instead of a session cookie. Token abilities are scoped per login role so a leaked token can't be replayed against a different portal.

**Multi-tenancy (unchanged).** Every table carries `institute_id`. Reuse `Tenancy` + Spatie `PermissionRegistrar::setPermissionsTeamId()` exactly as every web controller does — no parallel scoping logic.

**Two different scoping shapes — don't conflate them:**
- **Branch scoping** (`App\Support\BranchScope`) — Branch Manager and Principal see data by branch assignment (`branch_user` pivot; a user can be assigned more than one branch). Accountant and Receptionist are institute-wide unless they have assigned branches.
- **Own-batch scoping** (Teacher) — not branch-wide; scoped to batches the teacher is assigned to via `batch_teacher` (class teacher) or `batch_subject` (subject teacher). `TeacherDashboardService` is the reference implementation — reuse its query pattern for every teacher-facing endpoint, don't reuse `BranchScope` for teachers.
- **Own-child scoping** (Parent) — scoped to `students.parent_id` for the logged-in parent's `ParentGuardian` record; a parent with multiple children needs a child-switcher the API supports via a `student_id` parameter, validated against the parent's own children.

**Permissions (unchanged).** `config/permission_matrix.php` + `$user->can('module.action')` gate every endpoint, including per-user denials. See the principle in §1 — this is the load-bearing design decision of this whole document.

**Plan / module gating (unchanged).** `App\Support\PlanModules` resolves `effective modules = CORE + plan + extras − blocked` per institute; an endpoint for a module outside the institute's plan returns 403, same as web's Gate.

**Response envelope.** Reuse `App\Support\Ajax::success()/error()`'s `{success, message, data}` shape, already standard across web AJAX — one convention for both clients.

**Versioning.** All new routes under `/api/v1/...`. `routes/api.php` currently holds one public endpoint (`/demo/available-slots`); the new routes sit alongside it in their own middleware group.

## 4. Role × Module Access Matrix

Quick reference, built from `RoleTemplateService::defaultPermissionIdsFor()` — the live, enforced defaults. "✓" = default access today; "view" = read-only; blank = not granted by default (but grantable via Role & Permissions — §1's principle). BM = Branch Manager, P = Principal (identical to BM by default), Ac = Accountant, R = Receptionist, T = Teacher, Pa = Parent.

| Module | BM / P | Accountant | Receptionist | Teacher | Parent |
| --- | --- | --- | --- | --- | --- |
| Dashboard | ✓ (institute sections) | ✓ (institute sections) | ✓ (institute sections) | ✓ (teacher sections) | ✓ (parent sections) |
| Branch | view | | | | |
| Batch | view, add, edit | | view | view (own batches only) | |
| Teacher / Staff directory | view | | | | |
| Users (merged directory) | view (5 tabs) | | | | |
| Student | view, show, add, edit, status, receipt | view | view, add | | |
| Inquiry | view, show, add, edit, receipt | | view, show, add, edit, receipt | | |
| Follow Up | view, show, add | | view, show, add | | |
| Student Attendance | view, show, add, edit | | | | view |
| Teacher Attendance | view, show, add, edit | | | | |
| Fees | view, show, receipt | **view, show, add, receipt** | | | view (fees due) |
| Fees Follow Up | | view, show, add | | | |
| GST Invoice | | view, add | | | |
| Income & Expense (+ category) | | view, add, edit, status | | | |
| Exam | view, show, download | | | | view, show, download |
| Result Announcement | | | | | view, show, export |
| Homework | view, show, download, add | | | | view, show, download |
| Study Material | | | | | view, show, download |
| Notice | view, show, add | | | | view, show |
| To Do | view, add | | | | |
| Student Leave | | | | | show, add |
| Holiday | | | | | view (list) |

Blank cells for Teacher are the §5.9 gap. **Decision (option B, 2026-10-20):** leave the default `teacher` template minimal; institutes grant extra perms themselves. Phase 2 still built the full own-batch API so those grants work on mobile.

## 5. Complete API Catalog by Module

All endpoints under `/api/v1`, bearer-token auth, every list paginated, scoped automatically per §3's rules, gated per-action by the same permission name shown.

### 5.1 Auth & Profile

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| POST | /auth/login | Email + password → bearer token | — (credential check) |
| POST | /auth/logout | Revoke current token | auth only |
| GET | /auth/me | Profile, institute, assigned branch(es)/batches/children, effective permission list | auth only |
| POST | /auth/change-password | Required when `must_change_password` is true | auth only |
| PATCH | /profile | Update own name/phone/avatar | auth only |

### 5.2 Dashboard

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /dashboard | Role-appropriate dashboard (institute/teacher/parent sections) per granted `*_dashboard.*` actions | `dashboard.view` + per-section |
| GET | /dashboard/data/{widget} | One chart's data (mirrors web's `dashboard.data` route, same widget keys) | per-section |

### 5.3 Branch & Batch

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /branch | Own assigned branch(es) | `branch.view` |
| GET | /batches | List, filter by standard/branch/status | `batch.view` (teacher: own batches only) |
| GET | /batches/{id} | Detail | `batch.view` |
| POST | /batches | Create | `batch.add` |
| PUT | /batches/{id} | Update | `batch.edit` |
| POST | /batches/{id}/status | Activate/deactivate | `batch.status` |
| GET | /batches/{id}/students | Tab: enrolled students | `batch.tab_students` |
| GET | /batches/{id}/attendance | Tab: attendance | `batch.tab_attendance` |
| GET | /batches/{id}/exams | Tab: exams | `batch.tab_exam` |
| GET | /batches/{id}/homework | Tab: homework | `batch.tab_homework` |
| GET | /batches/{id}/attendance-report | Tab: attendance report | `batch.tab_attendance_report` |
| GET | /batches/{id}/notices | Tab: notices | `batch.tab_notice` |
| GET | /export/batches/{id}/students | CSV: batch roster | `batch.show` |
| GET | /export/batches/{id}/attendance-report | CSV: monthly attendance report | `batch.tab_attendance_report` |

Branch **create/edit/delete/status stay web-only**. Branches are created from institute/branch web login, not the mobile app.

### 5.4 People Directory

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /teachers | Directory list | `teacher.view` |
| GET | /teachers/{id} | Detail | `teacher.show` |
| POST | /teachers | Create | `teacher.add` or `users.add_teacher` |
| PUT | /teachers/{id} | Update | `teacher.edit` or `users.edit_teacher` |
| DELETE | /teachers/{id} | Delete | `teacher.delete` or `users.delete_teacher` |
| POST | /teachers/{id}/status | Activate/deactivate | `teacher.status` or `users.status_teacher` |
| GET | /teachers/{id}/attendance | Tab | `teacher.tab_attendance` |
| GET | /teachers/{id}/batches | Tab | `teacher.tab_batches` |
| GET | /teachers/{id}/assigned-work | Tab | `teacher.tab_assigned_work` |
| GET | /teachers/{id}/login-history | Tab | `teacher.tab_login_history` |
| GET | /staff | Directory list | `staff.view` |
| GET | /staff/roles | Assignable roles for staff create/edit | `staff.add` / `staff.edit` or matching `users.add_*` / `users.edit_*` |
| POST | /staff | Create (not branch_manager — that comes from web branch create) | `staff.add` or `users.add_{role}` |
| PUT | /staff/{id} | Update | `staff.edit` or `users.edit_{tab}` |
| DELETE | /staff/{id} | Delete | `staff.delete` or `users.delete_{tab}` |
| POST | /staff/{id}/status | Activate/deactivate | `staff.status` or `users.status_{tab}` |
| GET | /staff/{id} | Detail + same 4 tabs as teacher | `staff.show` |
| GET | /users?tab=teacher\|branch_manager\|principal\|receptionist\|accountant | Merged role-tab directory | `users.view_{tab}` |
| GET | /users/{id}/permissions | Per-user permission override view | `roles.assign_permissions`\* |
| POST | /users/{id}/permissions | Grant/deny an override | `roles.assign_permissions`\* |

\* Per-user permission overrides are currently an Institute Admin tool; keep behind that permission even on mobile (any role an admin grants it to can use it — see §1).

### 5.5 Students

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /students | List, filter by batch/standard/branch/status | `student.view` |
| GET | /students/{id} | Detail | `student.show` |
| POST | /students | Create (admission) | `student.add` |
| PUT | /students/{id} | Update | `student.edit` |
| POST | /students/{id}/status | Activate/deactivate | `student.status` |
| GET | /students/{id}/receipt | Form-fee receipt (view) | `student.receipt` |
| GET | /students/{id}/receipt-pdf | Form-fee receipt PDF | `student.receipt` |
| GET | /export/students | CSV export | `student.export` |
| GET | /export/students/{id}/attendance | CSV: one student's attendance | `student.show` |
| GET | /export/students/{id}/results | CSV: one student's exam results | `student.show` |

### 5.6 Admissions CRM

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /inquiries | List | `inquiry.view` |
| GET | /inquiries/{id} | Detail | `inquiry.show` |
| POST | /inquiries | Create | `inquiry.add` |
| PUT | /inquiries/{id} | Update | `inquiry.edit` |
| DELETE | /inquiries/{id} | Delete | `inquiry.delete`\* |
| GET | /inquiries/{id}/receipt | Form-fee receipt | `inquiry.receipt` |
| GET | /inquiries/{id}/receipt-pdf | Form-fee receipt PDF | `inquiry.receipt` |
| GET | /inquiries/{id}/convert | Convert-to-student prefill data | `inquiry.convert` |
| POST | /inquiries/{id}/convert | Convert to student | `inquiry.convert` |
| GET | /export/inquiries | CSV export | `inquiry.export` |
| GET | /follow-ups | List (own module view, same data as inquiry follow-ups) | `follow_up.view` |
| POST | /follow-ups/{inquiryId} | Add a follow-up | `follow_up.add` |
| GET | /export/follow-ups | CSV export | `follow_up.export` |

\* `inquiry.delete` isn't in any default non-admin template today — included for completeness since the web route exists; flag in §9 if it should stay admin-only.

### 5.7 Fees & Finance

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /fees | Payment list, filters (branch/batch/method/date range) | `fees.view` |
| GET | /fees/student-summary?student_id= | One student's invoice + payment summary | `fees.show` |
| POST | /fees | Record a payment | `fees.add` |
| DELETE | /fees/{paymentId} | Delete a payment | `fees.delete` |
| GET | /fees/{paymentId}/receipt | Receipt (view) | `fees.receipt` |
| GET | /fees/{paymentId}/receipt-pdf | Receipt PDF | `fees.receipt` |
| GET | /fees/follow-ups | Due-invoice follow-up list | `fees_follow_up.view` |
| POST | /fees/follow-ups/{invoiceId} | Log a follow-up | `fees_follow_up.add` |
| GET | /fees/follow-ups/export-pdf | PDF export | `fees_follow_up.export`\* |
| GET | /gst-invoices | List | `gst_invoice.view` |
| POST | /gst-invoices/generate/{paymentId} | Generate from a payment | `gst_invoice.add` |
| GET | /gst-invoices/{id} | Detail / PDF | `gst_invoice.view` |
| GET | /income-expense | List (type=income\|expense) | `income_expense.view` |
| POST | /income-expense | Record an entry | `income_expense.add` |
| DELETE | /income-expense/{id} | Delete | `income_expense.delete` |
| GET | /income-expense-categories | List | `income_expense_category.view` |
| POST | /income-expense-categories | Create | `income_expense_category.add` |
| PUT | /income-expense-categories/{id} | Update | `income_expense_category.edit` |
| POST | /income-expense-categories/{id}/status | Toggle | `income_expense_category.status` |
| GET | /inventory-assets | List | `inventory.view` |
| POST | /inventory-assets | Create | `inventory.add` |
| PUT | /inventory-assets/{id} | Update | `inventory.edit` |
| DELETE | /inventory-assets/{id} | Delete | `inventory.delete` |
| GET | /asset-categories | List | `assets_category.view` |
| POST | /asset-categories | Create | `assets_category.add` |
| PUT | /asset-categories/{id} | Update | `assets_category.edit` |
| DELETE | /asset-categories/{id} | Delete | `assets_category.delete` |
| POST | /asset-categories/{id}/status | Toggle | `assets_category.status` |

\* `fees_follow_up.export` uses generic CRUD in the matrix and isn't in the default accountant template. Inventory / asset categories are on mobile and permission-gated; default accountant still has no `inventory.*`. Plan still gates `inventory` like web.

### 5.8 Attendance

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /attendance/student?batch_id= | Batch roster for a date (web's "mark" screen data) | `attendance.view`, `attendance.add`, or `attendance.edit` |
| POST | /attendance/student/{batchId} | Bulk mark/update for a date | `attendance.add` or `attendance.edit` |
| GET | /attendance/student/reports/batch/{batchId}?year=&month= | Monthly batch report | `attendance.view` or `batch.tab_attendance_report` |
| GET | /attendance/student/reports/student/{studentId}?batch_id=&year=&month= | Monthly per-student calendar | `attendance.view` or `student.show` |
| GET | /attendance/teacher?date= | Staff/teacher roster for a date | `teacher_attendance.view/add/edit` |
| POST | /attendance/teacher | Bulk mark/update | `teacher_attendance.add/edit` |
| GET | /attendance/teacher/reports/teacher/{id} | Monthly report | `teacher_attendance.view` |
| GET | /attendance/teacher/reports/staff/{id} | Monthly report | `teacher_attendance.view` |
| GET | /export/teachers/{id}/attendance | CSV | `teacher.tab_attendance` |
| GET | /export/staff/{id}/attendance | CSV | `staff.tab_attendance` |

### 5.9 Academics

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /exams | List, filter by batch | `exam.view` |
| GET | /exams/{id} | Detail | `exam.show` |
| POST | /exams | Create | `exam.add` |
| PUT | /exams/{id} | Update | `exam.edit` |
| DELETE | /exams/{id} | Delete | `exam.delete` |
| GET | /exams/{id}/download | Attachment | `exam.download` |
| GET | /exams/{id}/result-sheet | Present/absent roster for marks entry | `result_announcement.add` or `.edit` |
| GET | /result-announcements | List | `result_announcement.view` |
| GET | /result-announcements/{id} | Detail | `result_announcement.show` |
| POST | /result-announcements | Save/publish results for an exam | `result_announcement.add` |
| GET | /result-announcements/{id}/preview | Preview before publish | `result_announcement.show` |
| GET | /result-announcements/{id}/export-pdf | PDF export | `result_announcement.export` |
| DELETE | /result-announcements/{id} | Delete | `result_announcement.delete` |
| GET | /homework | List, filter by batch | `homework.view` |
| GET | /homework/{id} | Detail | `homework.show` |
| POST | /homework | Create | `homework.add` |
| PUT | /homework/{id} | Update | `homework.edit` |
| DELETE | /homework/{id} | Delete | `homework.delete` |
| GET | /homework/{id}/download | Attachment | `homework.download` |
| GET | /export/teachers/{id}/homework | CSV | `teacher.tab_assigned_work` |
| GET | /study-materials | List, filter by batch | `study_material.view` |
| GET | /study-materials/{id}/download | Download | `study_material.download` |
| POST | /study-materials | Upload | `study_material.add` |
| PUT | /study-materials/{id} | Update | `study_material.edit` |
| DELETE | /study-materials/{id} | Delete | `study_material.delete` |

> **§5.9 decision (option B, 2026-10-20):** default `teacher` template stays minimal (`dashboard.view` + `teacher_dashboard.*` + `batch.view`). Do **not** expand `RoleTemplateService`. Phase 2 still ships the full own-batch API (attendance, homework, exams, results, study materials, notices, leave, CSV). If an institute grants those permissions, mobile works, scoped via `OwnBatchScope` (`batch_teacher` OR `batch_subject`) — never `BranchScope` for teachers.

### 5.10 Communication

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /notices | List | `notice.view` |
| GET | /notices/{id} | Detail | `notice.show` |
| POST | /notices | Create | `notice.add` |
| PUT | /notices/{id} | Update | `notice.edit` |
| POST | /notices/{id}/status | Toggle | `notice.status` |
| DELETE | /notices/{id} | Delete | `notice.delete` |
| GET | /holidays | List | `holiday.view` |
| POST | /holidays | Create | `holiday.add` |
| PUT | /holidays/{id} | Update | `holiday.edit` |
| DELETE | /holidays/{id} | Delete | `holiday.delete` |

### 5.11 Workflow

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /todos | My tasks | `todo.view` |
| POST | /todos | Create | `todo.add` |
| PUT | /todos/{id} | Update | `todo.edit`\* |
| DELETE | /todos/{id} | Delete | `todo.delete`\* |
| POST | /todos/{id}/approve | Approve | `todo.approve` |
| POST | /todos/{id}/reject | Reject | `todo.reject` |
| POST | /todos/carry-forward | Carry forward overdue tasks | `todo.carry_forward` |
| GET | /export/todos | CSV | `todo.view` |
| GET | /leave-requests/staff | Staff leave list | admin-side today\* |
| POST | /leave-requests/staff | Apply | admin-side today\* |
| POST | /leave-requests/{id}/approve | Approve | `staff_leave.approve` |
| POST | /leave-requests/{id}/reject | Reject | `staff_leave.reject` |
| GET | /leave-requests/student | Student leave list | `student_leave.view` |
| POST | /leave-requests/student | Apply (Parent) | `student_leave.add` |
| POST | /leave-requests/{id}/approve | Approve | `student_leave.approve` |
| POST | /leave-requests/{id}/reject | Reject | `student_leave.reject` |

\* `staff_leave` isn't in any non-admin default template. Endpoints exist anyway — permission-gated like web.

### 5.12 Reports & Exports (cross-cutting)

Already folded into each module's table above (every `GET /export/...` row) rather than a separate section — so a report for Students lives with Students, not in a disconnected "Reports" module, matching how the web app already treats exports as part of each module's page, not a separate menu (see `routes/web.php`'s own comment: *"CSV exports (module pages — not a separate Reports menu)"*). The admin `/reports` hub (`ReportController`) is a print/deep-link convenience over the same data — not a new capability, so no separate mobile endpoints needed; same `export.*`/attendance/batch/student endpoints above cover it.

### 5.13 Academic master data

Same as web: not in non-admin default templates, but the API is permission-driven. If an institute grants `subject.*` / `standard.*` to a mobile role, these work.

| Method | Endpoint | Purpose | Permission |
| --- | --- | --- | --- |
| GET | /subjects | List | `subject.view` |
| POST | /subjects | Create (restores soft-deleted same name) | `subject.add` |
| PUT | /subjects/{id} | Update | `subject.edit` |
| DELETE | /subjects/{id} | Soft delete | `subject.delete` |
| POST | /subjects/{id}/status | Activate/deactivate | `subject.status` |
| GET | /standards | List | `standard.view` |
| POST | /standards | Create (restores soft-deleted same name) | `standard.add` |
| PUT | /standards/{id} | Update | `standard.edit` |
| DELETE | /standards/{id} | Soft delete (blocked if batches/students/inquiries use it) | `standard.delete` |
| POST | /standards/{id}/status | Activate/deactivate | `standard.status` |

## 6. Out of Scope — Platform & Institute-Admin-Only

Not built for mobile. Listed so the boundary is explicit, not just implied:

| Area | Controllers | Why excluded |
| --- | --- | --- |
| Platform console | Institutes, Plans, Module Help, Email Templates, Project Inquiries, Demo Calendar, Demo Availability, Google Calendar Test | Platform Admin only — no mobile login at all (§1) |
| Institute admin-only setup | Roles & Permissions, Settings (email/Google/website), Classes Branding, Google Calendar connect | Institute Admin only — no mobile login (§1); also structurally tied to running the *system*, not day-to-day coaching ops |
| Billing | Institute's own Billing/checkout page | Admin-only; payment/subscription management stays on web |
| Reports hub page itself | `ReportController` print views | Superseded by module-level exports (§5.12) — no separate endpoints |

**Permission principle (repeating §1):** if an institute grants `subject.add` (or `inventory.view`, `fees.add`, …) to a mobile-logged-in role, `$user->can(...)` allows it. Default templates are the starting point, not a mobile ceiling. Platform Admin and Institute Admin still have no mobile login.

## 7. Phase Roadmap

| Phase | Role | Scope | Depends on |
| --- | --- | --- | --- |
| Phase 0 | Foundation | Sanctum install, `/api/v1` skeleton, auth/me/logout, response envelope, permission + branch-scope middleware | — |
| Phase 1 | Branch Manager | §5.1–§5.6, §5.8–§5.10 minus fee-collection, §5.11 (own tasks) — the widest single role, good first full build | Phase 0 |
| Phase 2 | Teacher | §5.2 (teacher sections), §5.3 (own batches), §5.9, §5.8 (own batches) — **done (option B)** | Phase 0, §9 option B |
| Phase 3 | Parent | §5.2 (parent sections), §5.9 (view-only), §5.10 (view), §5.11 (student leave) — **done** | Phase 0 |
| Phase 4 | Receptionist | §5.6 (inquiry/follow-up), §5.5 (view+add) subset of Phase 1 — **done** (reuses Phase 1 endpoints; convert stays `inquiry.convert`) | Phase 1 |
| Phase 5 | Accountant | §5.7 in full (inventory permission-gated; default accountant still has no `inventory.*`) — **done** | Phase 0 |
| Phase 6 | Principal | Identical to Phase 1's endpoints; ships "free" once Phase 1 is done (same default template) — **done** | Phase 1 |
| Phase 7 | Cross-cutting | Push notifications (FCM device registration + events), offline polish, OpenAPI docs — **not built yet** (removed until requested) | Phases 1–6 |

Phases 1, 3 and 5 can build in parallel once Phase 0 lands, since they touch almost entirely disjoint modules (Branch Manager/Receptionist/Principal vs. Parent vs. Accountant). Phase 2's default-permission decision is locked as option B.

## 8. Non-Functional Requirements

| Concern | Approach |
| --- | --- |
| Pagination | Laravel `paginate()` on every list — `page`, `per_page`, `meta.total/current_page/last_page` |
| Error format | `{"success": false, "message": "...", "errors": {"field": [...]}}` for 422; `{"success": false, "message": "..."}` for 403/404/500 — same as `Ajax`/`AdminAjax.js` convention |
| Validation | Reuse existing `$request->validate()` rules from each web controller — one rule set, not a duplicate |
| File uploads | `multipart/form-data`; same `public` disk + paths as web (`students`, `homeworks`, `staff`, etc.) so both clients read the same files |
| Timezone | `Asia/Kolkata` app-wide (`config/app.php`); API returns ISO-8601 with offset |
| Rate limiting | `throttle:` on `/auth/login`; sane default (e.g. 120/min) elsewhere |
| Soft deletes | Excluded by default everywhere, matching web |
| Plan-gated modules | 403 with a clear message when a module is outside the institute's effective plan (`PlanModules`), not a bare 404 |
| Push notifications | Phase 7 — `device_tokens` table (user_id, fcm_token, platform) + registration endpoint; event candidates: new inquiry, to-do assigned, fee due, leave approved/rejected, new notice |
| Offline support | Not assumed before Phase 7 — online-only, standard loading/error/retry states |
| API docs | OpenAPI/Swagger generated alongside routes |

## 9. Open Questions Needing a Decision

- [x] **Teacher default permissions (§5.9):** **option B** — stay minimal (`dashboard.view` + `teacher_dashboard.*` + `batch.view`); each institute customizes via Role & Permissions. Phase 2 API is own-batch scoped so extra grants work. `RoleTemplateService` teacher defaults unchanged.
- [x] **Fee collection on Branch Manager/Principal mobile:** same as web — `POST /fees` exists; no extra default `fees.add`. Grant the perm and mobile can collect.
- [x] **Inventory/Assets and Staff Leave on mobile:** both on mobile, permission-gated. Default templates unchanged. Inventory was skipped; now shipped. Staff leave APIs already existed.
- [x] **`inquiry.delete`:** keep available, permission-gated (web parity). Not hard-locked.
- [x] **Subject/Standard delegation (§6):** permission-gated on mobile (`/subjects`, `/standards`). Not permanently admin-only.
- [ ] Push notification priority — pull earlier than Phase 7 for any specific event (e.g. fee due reminders)?
- [ ] Native (iOS + Android separately) vs. cross-platform (Flutter/React Native)? Doesn't change the API, but affects token/refresh and upload handling client-side.
- [ ] Minimum OS versions / offline tolerance for spotty branch wifi — affects whether an earlier phase needs client-side request retry/queueing.
