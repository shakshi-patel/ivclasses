# Class Flow Mobile API — PRD (Phase 1: Branch)

As of 19 Oct 2026. Scope: the REST API that will power the Class Flow mobile app, starting with the **Branch Manager** role. Platform Admin and Institute Admin continue on web only; the API is designed so Teacher, Parent and Principal mobile access can be added in later phases on the same foundation.

## 1. Goals & Scope Boundary

Build a versioned REST API (Laravel Sanctum token auth) that powers the Class Flow mobile app, so field staff can work from a phone instead of the desktop CRM.

**Why Branch first.** Platform Admin and Institute Admin keep using the web CRM only — no mobile client is planned for them. Every other role (Teacher, Parent, Principal) is also currently web-only, but the mobile app's first release targets the **Branch Manager** role specifically, because that role's day-to-day work — marking attendance, logging inquiries, checking fee dues, approving to-dos — happens on the move, inside the branch, not at a desk.

**Phase 1 boundary.** The API's first phase matches, module for module, what a Branch Manager can already do on the web app today — no new business logic, no new permissions. This is deliberate: the web app's `branch_manager` / `principal` role template (`RoleTemplateService`) is the single source of truth for scope, so Phase 1 is a faithful mobile surface over proven, already-shipped behaviour rather than a parallel feature set that drifts from web over time.

**Beyond Phase 1.** The same auth, tenancy and permission layer is reused, unchanged, for Teacher, Parent and Principal mobile access in later phases — only the endpoint set per role grows.

## 2. Roles & Phase Roadmap

| Phase | Role / Target | Goal | Key modules | Depends on |
| --- | --- | --- | --- | --- |
| Phase 0 | — (foundation) | Stand up the API itself: auth, tenancy, envelope, versioning | Sanctum install, `/api/v1` skeleton, login/me/logout | — |
| Phase 1 | Branch Manager | Branch-scoped day-to-day ops app | Dashboard, Batches, Students, Teachers/Staff directory, Inquiries, Attendance (student + teacher), Fees (view), Exams, Homework, Notices, To-Do | Phase 0 |
| Phase 2 | Teacher | Class-facing app — my batches, attendance, homework, exam marks entry | Teacher dashboard, assigned batches, attendance, homework, exam results entry | Phase 0 |
| Phase 3 | Parent | Read-mostly app for one child's progress | Parent dashboard, attendance, homework, results, fees due, notices | Phase 0 |
| Phase 4 | Principal | Same shape as Branch Manager, wider (multi-branch) scope | Reuses Phase 1 endpoints; branch filter becomes multi-select | Phase 1 |
| Phase 5 | Cross-cutting | Push notifications, offline polish, deep links | FCM device registration, notification events | Phase 1–4 |

Platform Admin and Institute Admin are **not** on this roadmap — confirmed out of scope for mobile; they keep the existing web CRM only.

## 3. Architecture

**Auth — Laravel Sanctum (new dependency).** The codebase has no API auth package yet (`composer.json` has no Sanctum/Passport/JWT). Phase 0 adds `laravel/sanctum`, migrates `personal_access_tokens`, and adds `HasApiTokens` to the `User` model. Mobile logs in with email + password against a dedicated endpoint (reusing the same `LoginRequest::authenticate()` credential check the web `AuthenticatedSessionController` already uses) and receives a bearer token instead of a session cookie. Token abilities are scoped per login (e.g. `branch-manager`) so a leaked token can't be replayed against a different portal's endpoints.

**Multi-tenancy (unchanged).** Every table already carries `institute_id`; the existing `Tenancy` support class and Spatie `PermissionRegistrar::setPermissionsTeamId()` team-scoping pattern used by every web controller is reused as-is for API controllers — no parallel scoping logic.

**Branch scoping (unchanged).** `App\Support\BranchScope` already encodes the exact rule the PRD needs: Institute Admin sees all branches, everyone else (Principal, Branch Manager, Staff, Teacher) sees only their assigned branch(es) via the `branch_user` pivot (`is_primary` flag — a user *can* be assigned to more than one branch, so "one branch" in the UI copy is really "assigned branch(es)"). API controllers call the same `BranchScope::for()->applyToBatches()/applyToBranchMembers()` helpers the web controllers call.

**Permissions (unchanged).** The same Spatie permission matrix (`config/permission_matrix.php`) and `$user->can('module.action')` checks gate API endpoints. No new permission model — a Branch Manager's mobile access is exactly their existing granted permission set, including any per-user permission denials (`User::isPermissionDenied()`).

**Plan / module gating (unchanged).** `App\Support\PlanModules` resolves `effective modules = CORE + plan + extras − blocked` per institute. An endpoint for a module outside the institute's effective plan returns 403, mirroring the web app's Gate.

**Response envelope.** Reuse the existing `App\Support\Ajax::success()/error()` `{success, message, data}` shape already standardised across the web AJAX endpoints, so both clients share one mental model and one error-handling convention on the frontend/mobile side.

**Versioning.** All endpoints under `/api/v1/...`; `routes/api.php` currently holds a single public endpoint (`/demo/available-slots`) — the new routes are added alongside it, namespaced and middleware-grouped separately.

## 4. Phase 1 — Branch Manager Feature Scope

Taken directly from the `branch_manager` / `principal` default role template (`RoleTemplateService::defaultPermissionIds()`), which is the live, enforced definition of what this role can do today.

| Module | Allowed actions | Notes |
| --- | --- | --- |
| Dashboard | view + institute dashboard sections | Branch-scoped KPIs, lists, charts |
| Branch | view | Own assigned branch(es) only — no create/edit/delete |
| Batch | view, add, edit | No delete, no status toggle |
| Teacher | view | Directory only, no add/edit |
| Staff | view | Directory only |
| Users (merged list) | view teacher / branch manager / principal / receptionist / accountant tabs | Read-only directory across roles |
| Student | view, show, add, edit, status, receipt | Full day-to-day student management |
| Inquiry | view, show, add, edit, receipt | No convert-to-student, no export |
| Follow Up | view, show, add | Inquiry follow-up log |
| Attendance (student) | view, show, add, edit | |
| Teacher Attendance | view, show, add, edit | |
| Fees | view, show, receipt | **No `fees.add`** — Branch Manager can see dues and print receipts but cannot record a new payment; that stays with Accountant/Receptionist. Flagged in §9 as worth confirming before mobile launch. |
| Exam | view, show, download | No add/edit/delete, no result entry |
| Homework | view, show, download, add | No edit/delete |
| Notice | view, show, add | No edit/delete/status |
| To Do | view, add | No approve/reject (that's the task *owner's* manager action, separate from Branch Manager's own task list) |

**Explicitly not in Phase 1** (absent from the role template, so left out of the API too): Result Announcement, GST Invoice, Income & Expense (+ category), Inventory / Assets, Holiday, Student Leave, Staff Leave, Subject/Standard management, Roles & Permissions, Settings, Reports. See §8.

## 5. Phase 1 — Data Model Reference

All entities already exist in the live schema (confirmed against the current database, not just model code). Every row below also carries `institute_id`; soft-deletes (`deleted_at`) are standard and excluded by default in list queries.

| Entity | Table | Key fields the API will expose |
| --- | --- | --- |
| Branch | `branches` | name, code, contact_person, phone, city, manager_user_id, status |
| Batch | `batches` | branch_id, standard_id, name, start_time, end_time, capacity, academic_year, status |
| Student | `students` | user_id, parent_id, branch_id, admission_no, name, dob, gender, standard_id, admission_date, photo_path, status |
| Parent/Guardian | `parents` | father_name, mother_name, phone, phone_2, occupation, address |
| Student – Batch enrollment | `student_batch` | student_id, batch_id, subject_id, roll_no, total_fees, discount, status |
| Teacher | `teachers` | user_id, employee_code, specialization, expected_login_time, grace_minutes, joining_date |
| Staff | `staff` | user_id, employee_code, designation, expected_login_time, grace_minutes |
| Inquiry | `inquiries` | inquiry_code, student_name, phone, phone_2, standard_id, source, status, assigned_to, next_follow_up_at |
| Inquiry Follow-up | `inquiry_follow_ups` | inquiry_id, follow_up_at, remark, next_follow_up_at |
| Student Attendance | `student_attendances` | branch_id, batch_id, student_id, attendance_date, status, remark, marked_by |
| Staff/Teacher Attendance | `staff_attendances` | branch_id, user_id, attendance_date, status, check_in_at, check_out_at, late_minutes |
| Fee Invoice | `fee_invoices` | branch_id, student_id, total_amount, discount, net_amount, paid_amount, due_amount, status, due_date |
| Fee Payment | `fee_payments` | fee_invoice_id, student_id, amount, payment_date, payment_method, receipt_no |
| Exam | `exams` | branch_id, batch_id, subject_id, title, exam_date, start_time, end_time, total_marks, attachment_path |
| Homework | `homeworks` | branch_id, batch_id, subject_id, teacher_user_id, title, type, homework_date, due_date, attachment_path |
| Notice | `notices` | title, body, notice_date, audience, status, image_path |
| To Do | `todos` | assigned_to_user_id, title, task_items (json), due_date, status, remark, reviewed_by |

User identity: `users` (institute_id, user_type, name, email, phone, avatar_path, is_active) + `branch_user` pivot (user_id, branch_id, is_primary) for branch assignment.

## 6. Phase 1 — API Endpoints

All under `/api/v1`, bearer-token auth, every list endpoint paginated and branch-scoped automatically via the logged-in Branch Manager's `BranchScope`.

**Auth**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | /auth/login | Email + password → bearer token (portal = branch) |
| POST | /auth/logout | Revoke current token |
| GET | /auth/me | Profile + institute + assigned branch(es) + effective permissions |
| POST | /auth/change-password | Required when `must_change_password` is true |

**Dashboard**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /dashboard | Branch-scoped KPIs, lists, charts per granted `institute_dashboard.*` sections |

**Branch**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /branch | Own assigned branch(es) detail |

**Batches**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /batches | List, filter by standard/status |
| GET | /batches/{id} | Detail |
| POST | /batches | Create |
| PUT | /batches/{id} | Update |
| GET | /batches/{id}/students | Enrolled students |
| GET | /batches/{id}/attendance | Attendance tab |
| GET | /batches/{id}/exams | Exam tab |
| GET | /batches/{id}/homework | Homework tab |
| GET | /batches/{id}/notices | Notice tab |

**Students**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /students | List, filter by batch/standard/status |
| GET | /students/{id} | Detail |
| POST | /students | Create |
| PUT | /students/{id} | Update |
| POST | /students/{id}/status | Activate / deactivate |
| GET | /students/{id}/payments/{paymentId}/receipt | Fee receipt PDF |

**Teachers, Staff & Users directory (read-only)**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /teachers | Directory |
| GET | /teachers/{id} | Detail |
| GET | /staff | Directory |
| GET | /staff/{id} | Detail |
| GET | /users?role= | Merged Teacher/Branch Manager/Principal/Receptionist/Accountant list |

**Inquiries**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /inquiries | List |
| GET | /inquiries/{id} | Detail |
| POST | /inquiries | Create |
| PUT | /inquiries/{id} | Update |
| GET | /inquiries/{id}/receipt | Form-fee receipt PDF |
| GET | /inquiries/{id}/follow-ups | Follow-up log |
| POST | /inquiries/{id}/follow-ups | Add a follow-up |

**Attendance**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /attendance?batch_id=&date= | Student attendance for a batch/date |
| POST | /attendance | Bulk mark |
| PUT | /attendance/{id} | Edit one record |
| GET | /teacher-attendance?date= | Staff/teacher attendance for a date |
| POST | /teacher-attendance | Mark |
| PUT | /teacher-attendance/{id} | Edit |

**Fees (view-only — see §4 note)**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /fees?student_id= | Invoices + payment history |
| GET | /fees/payments/{id}/receipt | Receipt PDF |

**Exams**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /exams | List, filter by batch |
| GET | /exams/{id} | Detail |
| GET | /exams/{id}/download | Attachment |

**Homework**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /homework | List, filter by batch |
| GET | /homework/{id} | Detail |
| POST | /homework | Create |
| GET | /homework/{id}/download | Attachment |

**Notices**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /notices | List |
| GET | /notices/{id} | Detail |
| POST | /notices | Create |

**To-Do**

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | /todos | My tasks |
| POST | /todos | Create |

## 7. Non-Functional Requirements

| Concern | Approach |
| --- | --- |
| Pagination | Standard Laravel `paginate()` on every list endpoint — `page`, `per_page`, response carries `meta.total/current_page/last_page` |
| Error format | One shape everywhere: `{"success": false, "message": "...", "errors": {"field": [...]}}` for 422; `{"success": false, "message": "..."}` for 403/404/500 — same convention as the existing `Ajax` helper and `AdminAjax.js` error parsing on web |
| Validation | Reuse the same `FormRequest` / `$request->validate()` rules the web controllers already use (e.g. `BatchController::validateBatch()`), not a second copy |
| File uploads | `multipart/form-data`; photo/attachment fields stored on the existing `public` disk, same paths (`students`, `homeworks`, etc.) so web and mobile read the same files |
| Timezone | All dates/times in `Asia/Kolkata` (already fixed app-wide in `config/app.php`); API returns ISO-8601 with offset, mobile renders in device-local if different |
| Rate limiting | Laravel's `throttle:` middleware on `/auth/login` (brute-force) and a sane default (e.g. 120/min) on the rest |
| Soft deletes | Excluded by default on every list/detail query, matching web behaviour |
| Plan-gated modules | A module outside the institute's effective plan (`PlanModules`) returns `403` with a clear "not included in your plan" message, not a 404 |
| Push notifications | Not built yet — Phase 5. Needs a `device_tokens` table (user_id, fcm_token, platform) and a registration endpoint; flagged as a Phase 1 nice-to-have if timeline allows (e.g. new inquiry / to-do assigned) |
| Offline support | Not assumed for Phase 1 — online-only, standard loading/error/retry states. Revisit after Phase 1 ships if the field team needs offline attendance marking |
| API docs | OpenAPI/Swagger spec generated alongside the routes, so the mobile team isn't blocked on reading controller code |

## 8. Out of Scope for Phase 1

- Platform Admin and Institute Admin mobile access — confirmed web-only.
- Teacher, Parent and Principal mobile apps — later phases (§2), same API foundation.
- Result Announcement, GST Invoice, Income & Expense, Inventory/Assets, Holiday, Student Leave, Staff Leave — not in the Branch Manager's current permission template; add only if the product decision in §9 changes that.
- Subject/Standard management, Roles & Permissions, Settings, Reports, Billing — admin-only on web today, stays that way.
- Push notifications, offline mode — Phase 5 (§2, §7).
- Fee collection (`fees.add`) — Branch Manager cannot record a new payment on web today; mobile mirrors that unless §9 changes it.

## 9. Open Questions Needing a Decision

- [ ] Should Branch Manager be able to **collect fees** on mobile (`fees.add`), even though web doesn't grant it today? Common ask for a mobile app (collect payment on the spot).
- [ ] Should **Result Announcement** (view-only) be added to Phase 1, since Exam is already in scope and "check results on the go" is a likely ask?
- [ ] Push notification priority — pull into Phase 1 (new inquiry, to-do assigned, fee due) or keep as Phase 5?
- [ ] Native app (iOS + Android separately) or a single cross-platform codebase (Flutter / React Native)? Doesn't change the API, but affects token/refresh strategy and file-upload handling on the client.
- [ ] Minimum OS versions / offline tolerance for spotty branch wifi — affects whether Phase 1 needs request retry/queueing on the client.
- [ ] Should **Users (merged list)** on mobile expose edit/add for Branch Manager, or stay read-only as it is today?

## 10. Appendix — Full Module Catalog

Every module defined in `config/permission_matrix.php` (the system's single source of truth for CRM features). Phase 1 modules are in §4; the rest are reference for scoping Phase 2–4.

| Module | Used by (today, web) |
| --- | --- |
| Dashboard / Institute / Teacher / Parent Dashboard | All roles, role-specific sections |
| Classes (Institute Setting), Branch, Batch | Admin, Principal, Branch Manager |
| Teacher, Staff, Users (merged) | Admin, Principal, Branch Manager (view) |
| Subject, Standard | Admin only |
| Student | Admin, Principal, Branch Manager, Receptionist |
| Inquiry, Follow Up | Admin, Principal, Branch Manager, Receptionist |
| Fees, Fees Follow Up, GST Invoice | Admin, Accountant (Branch Manager: view-only) |
| Student Attendance, Teacher Attendance | Admin, Principal, Branch Manager |
| Exam, Result Announcement | Admin, Teacher (Branch Manager: Exam view-only, no Result Announcement) |
| Homework, Study Material | Admin, Teacher (Branch Manager: Homework only) |
| Notice, Holiday | Admin, Principal, Branch Manager (Holiday: admin only) |
| Student Leave, Staff Leave | Admin, Principal |
| Income & Expense (+ Category), Inventory (+ Assets Category) | Admin, Accountant |
| To Do | All roles (own tasks) |
| Roles, Permissions, Settings | Institute Admin only |
| Institutes, Plans, Project Inquiry, Demo Calendar/Availability, Billing | Platform Admin only — never mobile |
| Reports | Admin |

When scoping Phase 2 (Teacher) or Phase 3 (Parent), repeat the §4 exercise against that role's own template in `RoleTemplateService` rather than assuming parity with this document.
