# IVClasses — Development Tracker

> **Living memory** of the project. Update this file on **every** work session — small or big change, error, fix, decision.  
> Plan = `67c33d59a4db_prd.md` · Progress = **this file**. Do not scatter notes elsewhere.

---

## Current focus

| Field | Value |
|-------|--------|
| **Phase** | Phase 9 polish |
| **Status** | Phase 0–6 done · Phase 7 mostly done · Phase 8 not started · Phase 9 DomPDF/receipts/dashboard UX in progress |
| **Last updated** | 2026-10-19 |
| **Active module** | Institute first-login setup + theme PDFs + receipts + dashboard UX |
| **Blocked by** | Local subdomain setup on Laragon (`*.ivclasses.test`) — optional |
| **PRD file** | `67c33d59a4db_prd.md` (v1.5 + §16.8 SweetAlert standard) |
| **Agent rule** | `.cursor/rules/update-tracker.mdc` — auto-update this file after every add/edit/delete |

---

## Phase checklist

Mirror of `prd.md` phases. Tick as work completes.

### Phase 0 — Foundation
- [x] Marketing site + Book Demo (existing)
- [x] Platform demo CRM (existing)
- [x] PRD written + decisions locked (now v1.5)
- [x] Tracker file created
- [x] Fix `project_inquiries` schema drift (`demo_status`, conversion fields)
- [x] Base traits: SoftDeletes helpers, BelongsToInstitute, Audit (when Phase 1 starts)

### Phase 1 — Multi-tenant Core + Auth + RBAC
- [x] `institutes` + `subscriptions` + `plans`
- [x] Extend `users` (institute_id, user_type, soft delete, last login, global unique email)
- [x] Spatie Permission (teams) + extra columns
- [x] `config/permission_matrix.php` + `permissions:sync` → Developer auto-assign
- [x] Subdomain tenant resolver + central Platform Admin host
- [x] Platform Super Admin: institutes list, trial/plan, suspend
- [x] Register Institute + time-limited trial
- [x] Login / logout / login_logs
- [x] Separate portals: platform / institute / principal / branch / parent
- [x] Middleware: SuperAdmin, InstituteActive, Spatie permission
- [x] AJAX JSON scaffold + toasts pattern
- [x] `role_assignable`, `branch_user`
- [x] Wire Roles / Permissions Blade to real Spatie APIs
- [x] Role templates hierarchy (`RoleTemplateService`) + platform templates (`team_id` null)
- [ ] Document Laragon `*.ivclasses.test` subdomain + smoke on tenant host

### Phase 2 — Academic masters
- [x] Branch
- [x] Standard / Subject
- [x] Batch (+ pivots)
- [x] Teacher (user + profile + branch_user + login)
- [x] Staff (user + profile + role + branch_user + branch manager)
- [x] Classes branding settings
- [x] Module Help: platform CRUD + institute show API + layout wizard (`ModuleHelpRegistry`)
- [x] Teacher / Staff / Batch view tabs dynamic (attendance, login, work, teachers chips)

### Phase 3 — Admissions + Students + Parents
- [x] Inquiry CRUD + follow-up (institute-level)
- [x] Convert inquiry → Student + Parent login
- [x] Student list / add / view + multi-batch enrollment
- [x] Standalone follow-up module page wired to DB
- [x] Student edit with enrollment update
- [x] Admin form scope: institute login hides institute field; branch login hides institute + branch (auto-scoped)
- [x] Student view tabs dynamic (attendance, exam, fees, notice, GST, results)
- [x] Student list: deactivate toggle + admission receipt print
- [x] Inquiry list: receipt print

### Phase 4 — Fees + GST + Income/Expense + Inventory
- [x] Fee tables migration (`fee_invoices`, `fee_payments`, `fee_follow_ups`) + models
- [x] Fees list route wired to `FeeController`
- [x] Fee collection add/store + invoice auto-create from enrollment
- [x] Fee follow-up module (list + modal save)
- [x] Students lookup API for fee forms
- [x] GST invoices: generate from fee payment + printable view
- [x] Income & Expense: categories, add/list/delete, totals
- [x] Inventory & Assets: CRUD with categories, branch scope
- [x] Fees auto-post to Income & Expense on collection
- [x] Inquiry form fee auto-posts to Income & Expense (Form Fee category)
- [x] Master category DataTables + inventory status (Working/Repair/Damaged)
- [x] Fee payment receipt print + soft-delete payment (recalc invoice)
- [x] Fees follow-up Export PDF (modal: branch, multi-batch, date range)

### Phase 5 — Attendance + Leave + Holidays
- [x] Student attendance migration + model + service
- [x] Student attendance list (batches) + mark page (present/absent/late)
- [x] Batch view: attendance tab + students tab + monthly attendance report wired to DB
- [x] Student view: attendance calendar per batch (dynamic)
- [x] Teacher / staff attendance: mark + list (present/absent/late)
- [x] Holidays: CRUD (institute/branch/batch scope)
- [x] Staff & student leave requests: list + approve/reject + apply (permission-based)
- [x] CSV export: teacher/staff/student attendance + batch attendance report

### Phase 6 — Academics content
- [x] Homework: migration + model + service + CRUD + batch view tab + parent read-only view
- [x] Study material: migration + model + service + CRUD + parent read-only view
- [x] To-do: CRUD + carry forward + assign user
- [x] Notice: CRUD + publish toggle + parent read-only view
- [x] Exam: CRUD + file upload
- [x] Result announcement: exam result entry + preview + parent view scope
- [x] Result announcement: Export PDF (list action + view modal footer)
- [x] Student results table + CSV / per-row PDF export
- [x] CSV export: teacher homework / staff todos / login history

### Phase 7 — Billing SaaS
- [x] Trial enforcement (redirect to billing when expired)
- [x] Razorpay + Stripe gateway abstraction (bypass mode default)
- [x] subscription_payments table + checkout + payment history
- [x] Plan limits service (branches / students / staff)
- [x] Plan modules gate (sidebar + can() via plan.modules_json)
- [x] Plans CRUD (platform admin)
- [x] Platform admin: collect payment for any institute (offline / gateway)
- [x] Register OTP + first branch + credentials email
- [x] Project Inquiry → Convert to Institute (temp passwords email + first-login force change)
- [ ] Renewals / dunning (cron — when gateways connected)

### Phase 8 — API + Mobile
- [ ] Sanctum
- [ ] Parent / Teacher `/api/v1`
- [ ] FCM hooks

### Phase 9 — Polish
- [x] In-module CSV exports (students, teachers, staff, inquiries, todos, roles, batch students, attendance, results)
- [x] Printable receipts / PDF via browser print (fees, inquiry, student, fees follow-up, result announcement)
- [x] DomPDF download exports (theme teal `#0f5c4d` / gold `#d9a441`): batch students+attendance, student attendance+results, teacher/staff attendance+login+work, fees follow-up, result announcement
- [x] Student / Fee / Inquiry receipt modal (Close / Print / Export) + premium PDF with logo, stamp, signature, fees-receipt terms
- [x] Institute Setting branding: Logo / Signature / Stamp Dropzones + Fees Receipt Terms repeater (`settings_json`)
- [x] Dashboard branch filter: Select2 (same as rest of admin) on institute hero
- [x] Dashboard How-it-works vs charts: content-first auto-open; resize charts on modal close; X dismiss ≠ done (Done on last step only)
- [x] Study material / homework download URL fix (`asset('storage/...')` + dedicated download routes)
- [ ] No separate Reports sidebar (intentionally removed — exports live on module pages)
- [ ] SEO pages, Excel imports, WhatsApp/SMS, activity log UI, automated tests
- [ ] Trash / Restore filters on list pages (soft-delete UX)
- [x] Onboarding wizard checklist (first login)

---

## Change log (newest first)

| Date | Phase | Type | Summary | Files / notes |
|------|-------|------|---------|---------------|
| 2026-10-19 | 9 | fix | batches `deleted_at_key` uses CASE not IFNULL (MariaDB import #1901) | migration `add_unique_branch_name_constraint_to_batches` |
| 2026-10-19 | 9 | fix | B23: Hide billing bypass/dev .env hints from UI (bypass driver still works) | `billing/index`, `billing/platform` |
| 2026-10-19 | 9 | fix | P4: Project Inquiry Demo Time fills after date pick (tear down Select2 on rebuild) | `project-inquiries/create.blade.php` |
| 2026-10-19 | 9 | fix | B25: GST Generate (+) uses themed SweetAlert confirm + POST (no native localhost dialog) | `gst_invoice.blade.php` |
| 2026-10-19 | 9 | fix | B15: Re-creating a soft-deleted standard/subject restores it (no 500 unique conflict) | `StandardController`/`SubjectController` store, migration drop unique name |
| 2026-10-19 | 9 | fix | B12: Block deleting a standard that still has batches, students, or inquiries | `StandardController@destroy`, `Standard` relations |
| 2026-10-19 | 9 | fix | B7: Global search no longer 404s/crashes; loads permission-aware admin pages (Ctrl+/) | `AdminSearchCatalog`, layout, `main.js`, `config.js` assetsPath slash |
| 2026-10-19 | 9 | fix | B11: Escape Standard/Subject names in AJAX-built table rows (stored HTML/XSS via innerHTML) | `admin-ajax.js` `escapeHtml`, `standard.blade.php`, `subject.blade.php` |
| 2026-10-19 | 9 | fix | P2/P3: Plan yearly price must be ≥ monthly; Student Attendance Module Help intro no longer copies teacher text | `PlanController`, plans form, migration `fix_student_attendance_module_help_intro` |
| 2026-10-19 | 9 | fix | Assign plan trial days capped at 365 on the form (server already had max 365) | `institutes/index.blade.php`, plans form |
| 2026-10-19 | 9 | fix | Inventory asset purchase date cannot be in the future (form max today + server `before_or_equal:today`) | `InventoryAssetController`, `add_inventory_assets` |
| 2026-10-19 | 9 | fix | B22/B24: Friendly 405 page for POST-only URLs; Enterprise Unlimited no longer hidden by a stale branch override | `errors/405`, `bootstrap/app.php`, billing, `PlanLimit`/assign/activate |
| 2026-10-19 | 9 | fix | B13/B17/B21: Standard deactivate asks confirm; follow-up date must be on/after enquiry date; exam max marks min 1 | `standard.blade.php`, Inquiry/FollowUp controllers, `add_exam` |
| 2026-10-19 | 9 | fix | Master modal validation errors (Standard/Subject/etc.) now show under the correct field instead of under Status | `admin-ajax.js` `showFieldErrors`, standard/subject/assets/IE modals |
| 2026-10-01 | 9 | ui | Start Trial form drops the left pitch and uses the full width. The heading shows the chosen plan and its free-trial days | `register_institute.blade.php`, `style.css` |
| 2026-10-01 | 9 | feat | Homepage and Start Trial show the public institute plans as cards. A plan button opens the registration form with that plan; opening Start Trial directly shows the cards first | `plan_cards`, `home`, `register_institute`, `style.css` |
| 2026-10-01 | 9 | ui | Member menu: Teachers and Staff are one Users list. Tabs above the table filter Branch Manager, Teacher, Principal, Receptionist, and Accountant | `MemberUserController`, `users.blade.php`, sidebar |
| 2026-10-01 | 9 | fix | Standard setup page no longer freezes. Continue does not open the How it works overlay or a second highlight card | `onboarding.js`, `module-help.js` |
| 2026-10-01 | 9 | fix | Setup Continue no longer stacks the page How it works tour on the setup highlight. The screen stays clickable, and Add opens only that form | `ModuleHelpService`, `onboarding.js`, `layout.blade.php` |
| 2026-10-01 | 9 | fix | Converted institute admin can log in with the emailed temporary password. The main-branch manager link no longer makes the institute login say the credentials do not match | `AuthPortalGuard` |
| 2026-10-01 | 9 | feat | Demo convert uses the registration fields: plan and branch count on top, branch logins below; credentials email lists every branch | `ProjectInquiryController`, `InstituteRegistrationService`, project inquiry show |
| 2026-10-01 | 9 | ui | Module Help tour image shows full, in a framed stage above the text, like the old guide modal | `layout.blade.php` `.iv-tour-frame`, `module-help.js` |
| 2026-10-01 | 9 | ui | Module Help tour card: teal header, gold rule, step progress bar, theme Skip/Next buttons | `layout.blade.php` `.iv-shepherd`, `module-help.js` |
| 2026-09-30 | 9 | feat | Institute admin first-login setup: compulsory steps lock the dashboard until the record exists; optional steps are dismissible reminders; Go opens the page with a Shepherd highlight | migration `is_onboarding`/`completion_key`, `InstituteOnboardingService`, dashboard, `onboarding.js`, Module Help form |
| 2026-09-30 | 9 | feat | Module Help plays as a Shepherd.js page tour (vendor lib). Steps can target a CSS selector; empty target stays centered. Skip/X does not mark done | migration `target_selector`/`placement`, `ModuleHelp*`, `module-help.js`, form, layout |
| 2026-09-30 | 9 | ui | Sidebar menu-header leading line uses darker theme teal | `layout.blade.php` `.menu-header::before` |
| 2026-09-30 | 9 | ui | SweetAlert OK/Yes/Cancel buttons use theme teal (rest + hover), not template purple on hover | `layout.blade.php` `.swal2-popup` |
| 2026-09-30 | 7 | fix | Demo confirmed page "Open in admin" always goes to platform login (logs out existing session) instead of opening admin directly | `DemoAdminResponseController@openInAdmin`, `demo_admin_response.blade.php`, `routes/web.php` |
| 2026-09-30 | 2 | ui | Batch view Students tab: parent phone call icon is a round badge `tel:` link | `view_batch.blade.php`, `layout.blade.php` `.s-call` |
| 2026-09-30 | 9 | fix | Student inactive/create/update/delete now busts institute dashboard cache so Active students count updates immediately (was cached 120s) | `InstituteDashboardService::forgetForInstitute`, `StudentService` |
| 2026-09-29 | 9 | chore | Cursor always-apply rule: after any add/edit/delete, update TRACKER in same session (no need to ask) | `.cursor/rules/update-tracker.mdc`, `TRACKER.md` |
| 2026-09-29 | 9 | fix | How-it-works + dashboard charts: wait for `IvdDashboard.whenReady` before auto-open; resize ApexCharts on modal close; **X dismiss does not mark done** — auto-open until last-step Done; manual via How it works after done | `module-help.js`, `admin-dashboard.js` (`IvdDashboard.whenReady` / `resizeCharts`), layout cache-bust |
| 2026-09-29 | 9 | ui | Dashboard institute branch filter uses common Select2 (`form-select`) instead of native OS dropdown | `institute.blade.php`, `admin-dashboard.css` (`.ivd-branch`) |
| 2026-09-29 | 9 | ui | Premium receipt design (shared header/styles) for Student + Fee + Inquiry — modal preview + DomPDF parity; fee amount highlight card; stamp/signature/terms | `iv_receipt_brand_header`, `iv_receipt_styles`, `*/partials/receipt_card`, `print/pdf/{student,fee,inquiry}_receipt`, student/fees/inquiry list modals |
| 2026-09-29 | 2/9 | feat | Institute branding: Logo* / Signature* / Stamp* Dropzones + usage text + preview; Fees Receipt Terms title repeater; helpers `logoUrl`/`signatureUrl`/`stampUrl`/`feesReceiptTerms()` | `ClassesBrandingController`, branding blade, migration `signature_path`/`stamp_path`, Institute model |
| 2026-09-29 | 9 | feat | Theme DomPDF exports across admin: batch students/attendance; student attendance/results; teacher & staff attendance/login/work; fees follow-up landscape; result announcement; Export buttons theme green | `ExportController`, `FeeController`, `ResultAnnouncementController`, `print/pdf/*`, view_batch / view_student / view_teacher / view_staff, routes |
| 2026-09-29 | 9 | feat | Receipt flow: list action → AJAX modal (Close / Print / Export PDF) for student, fee payment, inquiry | routes `*.receipt` + `*.receipt_pdf`, controllers, list blades |
| 2026-09-23 | 9 | fix | After AJAX delete, Sr. No. renumbers immediately (no refresh) on Batch/Subject/Standard/Exam/Homework/Notice — `AdminAjax.renumberSr` inside `removeRow` | `admin-ajax.js`, list blades `data-role="sr"` |
| 2026-09-23 | 9 | feat/fix | SweetAlert via AdminAjax: Email Templates delete confirm+success + add/edit success; Exam/Result delete success popup; Holiday delete+save Swal; Leave approve/reject Swal; PRD §16.8 locked standard | `email_templates/*`, `EmailTemplateController`, `exam.blade`, `result_announcement.blade`, `holiday.blade`, staff/student leave blades, `prd.md`, `TRACKER.md` |
| 2026-09-23 | 7 | feat | Email Templates master: keyed DB templates, Quill editor, CRUD, soft delete, created_by, MailTemplateService wired to registration/demo mails | `EmailTemplate*`, `MailTemplateService`, routes, seeder |
| 2026-09-09 | 7 | feat | Project Inquiry convert → trial institute; email temp passwords; must_change_password + force-change page; Forgot password clears flag | `InstituteRegistrationService::convertFromProjectInquiry`, `ProjectInquiryController@convert`, `EnsurePasswordChanged`, `ForceChangePasswordController` |
| 2026-09-09 | 9/4 | fix | Fees follow-up Export PDF: platform institute→branch cascade (`url('/admin/lookups')` + no Select2) | `fees_follow_up.blade.php` |
| 2026-09-09 | 9/4/3/6 | feat | Module-page exports + receipts + list actions: teacher/staff/student/batch attendance CSV; login/homework/todos export; student results table; fee receipt+delete; inquiry/student receipt; fees follow-up PDF modal; result announcement PDF | `ExportController`, `FeeController`, `StudentController`, `InquiryController`, `ResultAnnouncementController`, print blades, routes |
| 2026-09-09 | 9 | fix | Upload downloads broken under `/iv_classes/public` (APP_URL localhost) — use `asset('storage/...')` + download routes | homework/study_material download routes |
| 2026-09-09 | 9 | decision | No separate Reports sidebar — CSV/PDF from each module page instead | removed sidebar Reports; hub routes kept for deep links |
| 2026-09-09 | 1 | feat | Role templates: platform templates (`team_id` null), hierarchy developer→institute_admin→others; sync propagates; hide own role from Roles UI | `RoleTemplateService`, Roles blade |
| 2026-09-09 | 2 | fix | Batch view teacher chips: real avatar + employee code (theme `.avatar` layout break) | `BatchController::show`, `view_batch` |
| 2026-09-09 | 2 | feat | Module Help institute wizard: show API, layout + JS, localStorage Done, How it works + first-visit | `ModuleHelpRegistry`, `ModuleHelpController`, layout |
| 2026-09-08 | 2 | feat | Module Help dynamic CRUD (guides, steps, roles, media) + form UX tweaks | migrations, `ModuleHelpService`, admin UI |
| 2026-09-08 | 2 | ui | Module Help platform admin list + add/edit form (UI only, static demo data) | `ModuleHelpController`, `admin/module_help/*` |
| 2026-09-08 | 2 | fix | Remove duplicate Laravel pagination; DataTables-only on admin lists | controllers `get()` + blades drop `links()` |
| 2026-09-08 | 2 | fix | Bootstrap 5 pagination (stop giant Tailwind SVG arrows) + study material seed for institute 5 | `AppServiceProvider`, `Institute5CrmDemoSeeder` |
| 2026-09-08 | 2 | feat | Institute 5 CRM demo seed (~2 months data across modules) | `Institute5CrmDemoSeeder` |
| 2026-09-07 | 2 | feat | Teacher specialization = multi-select of institute subjects | `add_teacher` + `TeacherController` specialization_ids |
| 2026-09-07 | 2 | feat | Staff view tabs dynamic: attendance calendar, login history, assign work (todos) | `StaffController::show`, `attendance.report.staff`, `TodoService::listForAssignee` |
| 2026-09-07 | 7 | feat | After OTP verify: email institute+branch logins via platform SMTP | `InstituteRegistrationCredentialsMail`, session credentials |
| 2026-09-11 | 9 | ui | List pages: top toolbar card + bottom table card; DataTables no DOM strip; IE summary totals from filtered query | list blades, layout DT init, IncomeExpenseController |
| 2026-09-10 | 9 | fix | List filters: category pages, attendance, leave, todo, result; fees follow-up `$instituteId`; cascade batch duplicate fix | `list-filters`, attendance/leave/todo controllers |
| 2026-09-10 | 9 | feat | Shared list filters (`x-admin.list-filters`): platform institute→branch cascade; institute admin no institute field; wired across CRM lists + fixed fees stub filters | `AdminController`, `components/admin/list-filters` |
| 2026-09-10 | 7 | fix | Audit remaining: M2 dual destroy, M8/M10 Gate+parent Spatie, M12 CSS utils, blade cleanup, SequenceGenerator, fail-closed gateways + test checklist | `AdminController`, `AUDIT_REPORT.md`, `PlanModuleGateTest` |
| 2026-09-10 | 7 | fix | Audit Medium batch: AdminController, enums, pagination, FeePayment scope, profile/password middleware, PlanModules helpers | `AdminController`, enums, blades, services |
| 2026-09-10 | 7 | fix | Audit H4–H7: soft-delete guards, AdminUrl/route sweep, login escape, brand config | `BranchController`, `TeacherService`, `StudentService`, `AdminUrl`, blades |
| 2026-09-10 | 7 | fix | Audit C1–C3 + H1–H3: billing coverage/activate, bypass fail-closed, remove classes hardcode, PlanLimitService for creates, assignPlan cancel old, force password defaults | `Institute`, `BillingGatewayManager`, services |
| 2026-09-09 | — | chore | Full app audit report: Blade @php/hardcode, billing/activate risks, plan override limit gap, soft-delete orphans | `AUDIT_REPORT.md` |
| 2026-09-09 | 7 | feat | Per-institute plan overrides: gift/block modules + limit overrides (branches/students/staff) | `PlanModules`, `PlanLimitService`, Institutes overrides modal |
| 2026-09-07 | 7 | feat | Register step-form saves first branch + branch manager login, then OTP | `InstituteRegistrationService` + `BranchService`, register blade |
| 2026-09-07 | 7 | feat | Institute register OTP verify screen (temp OTP on-page) | migration `registration_otp`, `RegisterInstituteController`, OTP blade |
| 2026-09-07 | 2 | feat | Batch view Exam + Notice tabs dynamic from DB | `BatchController::show`, `ExamService::listForBatch`, `NoticeService::listForBatch` |
| 2026-09-07 | 2 | feat | Teacher view tabs dynamic: attendance calendar, batches, assigned work, login history | `TeacherController::show`, `StaffAttendanceService::userMonthCalendar` |
| 2026-09-07 | 3 | feat | Student view tabs dynamic: Exam, Fees, Notice, GST, Result | `StudentController::show`, listForStudent helpers, `view_student` |
| 2026-09-07 | 3 | feat | Student roll no auto from batch (next after max) on add/convert | `StudentService`, lookups `next-roll-no`, student/convert blades |
| 2026-09-07 | 4 | feat | Inquiry form fee auto-posts to Income & Expense (Form Fee) via `inquiry_id` | migration, `InquiryService`, `IncomeExpenseService` |
| 2026-09-03 | 2 | feat | Branch manager `user_type` = `branch_manager` (not institute_staff) + backfill migration | `UserType`, `BranchService`, migration |
| 2026-09-03 | 7 | feat | Platform Billing: select institute + collect offline/gateway payment; institute self-serve billing unchanged | `BillingController`, `platform.blade.php`, `SubscriptionBillingService::collectOffline` |
| 2026-09-03 | 7 | fix | Plan module sidebar gate: prepend before Spatie Gate::before (after could not deny) | `AppServiceProvider` |
| 2026-09-03 | 7 | feat | Plan modules gate: CORE + plan.modules_json enforce via Gate + Roles UI filter | `PlanModules`, Assign plan |
| 2026-09-02 | 7 | feat | 3-portal auth: platform / institute / parent login + portal guard + login_logs.portal | `AuthPortal`, `AuthPortalGuard`, auth routes |
| 2026-09-02 | 6 | feat | Phase 6 complete: To-do, Notice, Exam, Result announcement wired to DB | migrations, services, controllers, blades |
| 2026-09-02 | 6 | feat | Study material CRUD wired (file upload, parent scoped view/download) + ParentBatchScope shared helper | `StudyMaterial*`, migration, blades, permissions |
| 2026-09-02 | 6 | feat | Parent homework read-only list scoped to child batches | `HomeworkService`, `ParentBatchScope`, homework blade |
| 2026-09-01 | 6 | feat | Phase 6 started: Homework CRUD wired (list/add/edit/delete, file upload, batch tab) | `Homework*`, migration, blades, permissions |
| 2026-09-01 | 5 | feat | Leave apply flow: staff self-submit + parent/student leave with permissions | `LeaveRequestService`, leave blades |
| 2026-09-01 | 5 | feat | Attendance reports: batch monthly grid + student calendar dynamic | report APIs, view_batch, view_student |
| 2026-09-01 | 4 | feat | Fees collect auto-creates Income entry (Education Fee) linked via `fee_payment_id` | migration, `FeeService`, `IncomeExpenseService` |
| 2026-09-01 | 4 | feat | Inventory asset status dropdown: Working / Repair / Damaged | `InventoryAsset`, migration |
| 2026-09-01 | 4 | feat | Master Income/Expense + Assets Category tables: DataTables | master blades, controllers `get()` |
| 2026-08-31 | 4 | feat | Master category CRUD: Income/Expense categories + Assets categories (add/edit/delete/toggle) wired to DB | `IeCategoryController`, `AssetCategoryController`, master blades |
| 2026-08-31 | 4 | feat | Fee collection: FeeService, add fees form wired, student summary API, follow-up list+save; fees permissions enabled | `FeeService`, `FeeController`, add_fees, fees_follow_up blades |
| 2026-08-31 | 4 | feat | Phase 4 started: fee migrations + models, fees list controller; Phase 3 marked complete | `FeeInvoice`, `FeePayment`, `FeeFollowUp`, `FeeController`, migration |
| 2026-08-31 | 3 | feat | AdminFormScope: hide institute field for institute users; hide institute+branch for branch-restricted users on all wired add forms | `AdminFormScope`, form partials, batch/branch/inquiry/student/staff/teacher/convert |
| 2026-08-31 | 2 | feat | BranchScope: Principal/Staff/Teacher see only assigned branches | `BranchScope.php`, controllers |
| 2026-08-31 | 2 | fix | Staff role column on platform admin list (resolve role via institute team_id, not platform team 0) | `StaffService::resolveRole`, staff blades |
| 2026-08-31 | 2 | feat | Phase 2 complete: Batch CRUD + batch_subject/batch_teacher pivots; Classes branding settings (institute profile, logo, GSTIN) | `BatchController`, `BatchService`, `ClassesBrandingController`, migrations, blades |
| 2026-08-27 | 2 | feat | Center SweetAlert + no-reload CRUD on list/modals; AJAX form save (Branch/Teacher/Staff) with Swal then redirect to list | `admin-ajax.js`, layout Swal assets, Standard/Subject/Branch/Teacher/Staff/Roles/Institutes/Permissions |
| 2026-08-27 | 2 | feat | Standard/Subject: platform Add modal institute required + list Institute column; institute users stay auto-scoped | `StandardController`, `SubjectController`, standard/subject blades |
| 2026-08-27 | 2 | feat | Platform Add forms: institute change reloads Branch/Role/Manager (scoped lookups API + shared cascade JS) | `InstituteLookupController`, `admin-institute-cascade.js`, Teacher/Staff/Branch forms |
| 2026-08-27 | 2 | feat | Standard + Subject masters: AJAX modal CRUD, institute scope, soft delete, matrix enabled | `StandardController`, `SubjectController`, `standards`/`subjects` tables |
| 2026-08-27 | 2 | feat | Staff module: login user + staff profile + Spatie role (principal/accountant/receptionist) + branch_user + optional branch manager | `StaffController`, `StaffService`, `staff` table |
| 2026-08-27 | 2 | feat | Teacher module: users login + teachers profile + branch_user + Spatie teacher role | `TeacherController`, `TeacherService`, `teachers` table |
| 2026-08-26 | 2 | fix | Institute login 403 on Branch edit — Spatie team_id bound after auth in EnsureInstituteActive | `EnsureInstituteActive`, `SyncPermissionsTeamFromAuth`, `User::isPlatformUser` |
| 2026-08-26 | 2 | feat | Branch CRUD live: migration, model (institute scope + soft delete), list/add/edit, plan max_branches check, matrix enabled | `BranchController`, `branches` table, branch blades |
| 2026-08-26 | 1 | feat | Roles/Permissions UI wired to Spatie (CRUD + assign perms + matrix sync); team sync from auth user | `RoleController`, `PermissionController`, `SyncPermissionsTeamFromAuth`, `Tenancy` |
| 2026-08-26 | 1 | design | Start Trial page restyled to match Book Demo website theme (pitch + form card) | `register_institute.blade.php`, `style.css` trial-alert |
| 2026-08-26 | 1 | feat | Phase 1 core: plans/institutes/subscriptions, Spatie teams + matrix sync, tenant middleware, register trial, Super Admin institutes AJAX, login_logs | migrations `090000–090009`, models, middleware, `PermissionSyncService`, seed credentials |
| 2026-08-26 | 1 | chore | CSRF meta + `admin-ajax.js` in layout; Institutes menu (platform users); Start Trial website links; `.env` tenancy vars | `layout.blade.php`, website nav, `.env.example` |
| 2026-08-26 | 1 | fix | Composer autoload missing Spatie after install → `composer dump-autoload`; then `db:seed` + `permissions:sync` OK | vendor composer maps |
| 2026-08-26 | 0 | chore | PRD v1.0→1.3: SaaS schema, matrix permissions, locked decisions, tracker rule | `prd.md`, `TRACKER.md` |
| 2026-08-26 | 0 | decision | Subdomain; time-limited trial; Razorpay+Stripe; global email; parent-first; Principal=Spatie role; Spatie+matrix; UI keep + deltas | `prd.md` §16 |

---

## Errors & fixes

| Date | Phase | Error | Cause | Fix | Status |
|------|-------|-------|-------|-----|--------|
| 2026-10-19 | 9 | Assign plan accepted trial days like 99999 | Input had `min=1` only; no client max | Form `max=365` + JS check; server already `max:365` | Resolved |
| 2026-10-19 | 9 | Asset saved with future purchase date (e.g. 2030) | Purchase date had no max/today rule | Form `max=today` + server `before_or_equal:today` | Resolved |
| 2026-10-19 | 9 | POST-only admin URL shows raw Method Not Allowed; Enterprise billing shows 2/3 not Unlimited | No 405 view; institute had `max_branches_override=3` over unlimited plan | Friendly `errors/405`; clear stale overrides on unlimited plan assign/activate; billing labels custom caps | Resolved |
| 2026-10-19 | 9 | Standard deactivate had no confirm; follow-up before enquiry date; exam max marks 0 allowed | Toggle skipped confirm; date rules missing; `total_marks` min was 0 | Confirm on deactivate; `after_or_equal` enquiry date; `total_marks` min 1 | Resolved |
| 2026-10-19 | 9 | Add Standard validation message shows under Status | All modal errors dumped into one bottom `#…FormError` div under the last field | Map Laravel error keys to each field with `AdminAjax.showFieldErrors` | Resolved |
| 2026-10-01 | 9 | After setup, the institute admin sees the parent dashboard | Student save used the admin email as the father's email and overwrote `user_type` to parent | Keep staff logins unchanged when the parent email matches them. Restored this account to institute admin | Resolved |
| 2026-10-01 | 9 | Standard step Continue still freezes the whole page | How to manage Standards opens with a full-screen Shepherd overlay on top of the setup card. The overlay captures every click | During setup, do not start either tour. Only the instruction bar is shown, and any overlay is removed | Resolved |
| 2026-10-01 | 9 | After setup Continue the page freezes, as if two modals are open | Setup highlight and that page’s How it works tour both start. The tour overlay sits above the Add form, so the screen cannot be clicked | Do not boot How it works while `onboarding_step` is set. Close the highlight when Add opens the form | Resolved |
| 2026-10-01 | 9 | After demo convert, emailed temporary password shows “These credentials do not match our records.” | Single-centre convert sets the institute admin as main-branch `manager_user_id`. Institute login then treats them as a branch manager and rejects a correct password with `auth.failed` | Institute portal allows Institute Admin even when they manage the main branch; only `user_type` Branch Manager is sent to the branch login | Resolved |
| 2026-09-30 | 9 | Dashboard Active students count stays same after marking student inactive | Institute dashboard summary cached 120s; status change did not invalidate it | Version-bump cache key on student create/update/delete/toggle | Resolved |
| 2026-09-29 | 9 | Dashboard charts look empty / wrong until refresh when How it works opens | Help modal auto-opened at 400ms while ApexCharts rendered under modal (wrong width); user thought data stale | Charts-first `whenReady` then help; `resizeCharts` on modal close; keep X ≠ done | Resolved |
| 2026-09-09 | 9 | Homework/study material download 404 | `Storage::url` + APP_URL=`http://localhost` under Laragon `/iv_classes/public` | `asset('storage/...')` + dedicated download routes | Resolved |
| 2026-08-26 | 2 | Institute Branch edit 403 | Spatie team_id still 0 when `can()` ran (sync middleware before auth) | Bind team after auth in `EnsureInstituteActive` | Resolved |

---

## Open blockers

- [ ] Local subdomain setup on Laragon (e.g. `*.ivclasses.test`) — document when started

---

## Remaining (priority)

1. **Phase 7:** Renewals / dunning cron when Razorpay/Stripe live  
2. **Phase 8:** Sanctum + Parent/Teacher API + FCM  
3. **Phase 9:** SEO pages, Excel import, WhatsApp/SMS, trash/restore UI, onboarding wizard, automated tests  
4. **Optional:** Document Laragon `*.ivclasses.test` smoke  
5. **Module Help content:** paste-ready guides for modules still missing copy (Branch/Teacher/Staff covered)

---

## Notes / memory (read every new session)

- Admin CRM modules Phases **1–6 are wired to DB** (not UI mock). Remaining = Phase 8 API + Phase 9 polish leftovers + Phase 7 renewals.
- **Exports live on module pages** — no separate Reports sidebar. CSV via `ExportController`; many PDFs now **Barryvdh DomPDF** download (theme teal/gold). Browser-print blades still exist for some receipts.
- **Receipts (student / fee / inquiry):** shared `iv-receipt` chrome + institute logo/stamp/signature + `fees_receipt_terms` from branding; modal preview then Export PDF.
- **Module Help:** Shepherd.js page tour (vendor lib). Step `target` CSS selector highlights a control; empty target is a centered tip. First-visit auto-open until last-step Done (Skip/X does not mark done). Dashboard waits for charts before auto-open.
- **Institute setup:** separate from How it works. One Module Help guide flagged “Institute first-login setup”. Each step is Compulsory or Optional at add time, with a completion key (branch/standard/subject/batch/teacher/student). Compulsory steps block only the institute admin until a live record exists (existing data auto-completes). Optional steps are dashboard reminders and can be dismissed. Resume is server-side. Other roles see a short notice while required steps are open.
- Permission source of truth = `config/permission_matrix.php` → sync into **Spatie** tables; Developer role auto-full access.
- Role templates: platform rows `team_id` null; `RoleTemplateService` hierarchy + sync propagate.
- Tenant isolation = `institute_id` + subdomain + Spatie `setPermissionsTeamId`.
- Soft delete + AJAX CRUD on all business modules when wired.
- After **any** code change: update this tracker in the **same session** (enforced by `.cursor/rules/update-tracker.mdc` — agent must not wait to be asked).
- **Seed logins:** `developer@ivclasses.com` / `password` (platform); `admin@demo-institute.test` / `password` (demo institute); `teacher@demo-institute.test` / `password` (teacher); `principal@demo-institute.test` / `password` (branch manager staff).
- PHP CLI: use `D:\laragon\bin\php\php-8.3.1-nts-Win32-vs16-x64\php.exe` (system PATH often has no `php`).
- DomPDF images: prefer `public_path('storage/...')` file paths in PDF blades.

---

## Session template (copy under Change log when working)

```
| YYYY-MM-DD | Phase N | feat/fix/fix/chore/error/decision | One-line what & why | key files |
```

Error template:

```
| YYYY-MM-DD | Phase N | short error | root cause | what we did | Open/Resolved |
```
