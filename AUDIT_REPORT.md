# IV Classes — Full Application Audit Report

**Date:** 2026-09-09 (fixes completed 2026-09-10)  
**Scope:** `app/`, `resources/views/`, `routes/`, `config/`, `database/` (vendor excluded)  
**Goal:** Find Blade hardcode / `@php` business logic, real bugs, and future-risk patterns; recommend standard Laravel structure.

---

## Fix progress (2026-09-10) — ALL audit items Done

| ID | Status | What changed |
|----|--------|----------------|
| C1 | Done | `Institute::hasValidCoverage()` + `needsBilling()`; `activate` requires valid sub |
| C2 | Done | Billing bypass blocked outside local unless `BILLING_ALLOW_BYPASS`; default driver local→bypass else razorpay |
| C3 | Done | Deleted orphan hardcoded `classes.blade.php` (live route uses `branding`) |
| H1 | Done | Student/Staff/Teacher limits delegate to `PlanLimitService` (respects overrides) |
| H2 | Done | `assignPlan` cancels previous Active/Trial subscriptions in a transaction |
| H3 | Done | Manager/staff/teacher default `must_change_password = true` |
| H4 | Done | Branch delete blocked if batches/students; teacher detaches batches; student deactivates login/parent |
| H5 | Done | Admin blades: `url('/admin/...')` → `route()` / `AdminUrl::lookups()` + meta tag |
| H6 | Done | Login title no longer uses `{!! !!}` |
| H7 | Done | Website contact from `config/brand.php` + `route('privacy')` |
| M1 | Done | `AdminController` base; all admin controllers extend it |
| M2 | Done | Destroy dual-response via `respondDestroyed` / `respondDestroyFailed` |
| M3 | Done | Profile routes use `EnsureInstituteActive` |
| M4 | Done | `password.update` removed from force-password allowlist |
| M5 | Done | Force-password defaults for admin-created accounts |
| M6 | Done | Institutes + project inquiries paginated (25) |
| M7 | Done | Staff export uses `StaffService::roleNamesForStaff` (no N+1) |
| M8 | Done | Gate prepend documented; `tests/Feature/PlanModuleGateTest.php` |
| M9 | Done | `FeePayment` institute global scope via student |
| M10 | Done | Parent Gate short-circuit removed; Spatie + `config/permissions.php` |
| M11 | Done | Assign plan clears `modules_blocked_json` |
| M12 | Done | Shared utilities in `iv-tokens.css`; fees/follow-up cleaned |
| Enums | Done | `AttendanceStatus`, `ProjectInquiryStatus`, `DemoStatus`, `FeeInvoiceStatus` |
| Blade | Done | module_help / follow_up / fees / view_student cleaned |
| Codes | Done | `SequenceGenerator` locks admission / employee / inquiry / receipt / institute codes |
| Gateways | Done | Razorpay/Stripe fail-closed (no success without signature) |

**હજુ future (audit bug નહીં):** live Razorpay/Stripe Orders/Checkout API — keys મળે ત્યારે.

---

# ટેસ્ટ ગાઈડ (Gujarati) — Step by Step

> આ ભાગ કાંટા સરખું સમજાય એવું લખ્યું છે.  
> દરેક ટેસ્ટમાં: **શું ચેક કરવું → કેવી રીતે કરવું → Pass ક્યારે → Fail ક્યારે**

---

## A) ટેસ્ટ શરૂ કરતા પહેલાં તૈયારી

### તમારે શું જોઈએ
1. Laragon ચાલુ હોવું જોઈએ (Apache + MySQL)
2. Browser: Chrome / Edge
3. બે login:
   - **Platform admin** (super admin) — institutes / plans મેનેજ કરવા
   - **Institute admin** — student / fees / branch વગેરે CRM ચલાવવા
4. (જરૂર પડે તો) Parent login / Staff-Teacher login

### `.env` માં local માટે આ રાખો
```env
APP_ENV=local
BILLING_DRIVER=bypass
```
> Production જેવું test કરતી વખતે નીચે C2 માં અલગ સૂચના છે — પછી પાછું `local` કરી દેજો.

### પહેલું automated test (ફરજિયાત)
Terminal ખોલો → project folderમાં જાઓ:

```bash
cd d:\laragon\www\iv_classes
php artisan test --filter=PlanModuleGateTest
```

| Result | અર્થ |
|--------|------|
| `passed` / 2 tests | Gate setup OK — આગળ manual test કરો |
| `FAILED` | મને કહો — code/Gate તરફ જોઈશ |

---

## B) Critical ટેસ્ટ (સૌથી મહત્વના — પહેલા કરો)

### ટેસ્ટ C1 — Expired institute Activate થઈ શકે નહીં (બિલિંગ વગર)

**શું ચેક:** Trial/subscription પૂરી થયા પછી Activate દબાવતા CRM મફતમાં ન ખુલે.

**કેવી રીતે:**
1. Platform admin થી login કરો
2. **Institutes** list ખોલો
3. એક institute પસંદ કરો જેનો trial/sub expire થઈ ગયો હોય  
   - ન હોય તો: DB માં તે institute ની subscription નું `ends_at` ભૂતકાળની તારીખ કરો, અથવા trial expire કરો
4. તે institute પર **Activate** try કરો (valid Active paid/coverage વગર)
5. પછી **Assign Plan** / **Extend Trial** કરીને valid coverage આપો
6. ફરી Activate કરો → institute admin થી CRM login કરી જુઓ

| Pass | Fail |
|------|------|
| Coverage વગર Activate ન થાય / billing જરૂરી રહે | Expire પછી પણ Activate થઈને પૂરું CRM ખુલી જાય |
| Coverage આપ્યા પછી Activate + CRM OK | Coverage આપ્યા પછી પણ CRM ન ખુલે |

---

### ટેસ્ટ C2 — Production માં Bypass થી મફત payment ન થાય

**શું ચેક:** ખોટા `.env` થી production જેવા env માં payment auto-complete ન થાય.

**કેવી રીતે (સાવધાની — test પછી પાછું local કરજો):**
1. `.env` માં થોડા સમય માટે:
   ```env
   APP_ENV=production
   BILLING_DRIVER=bypass
   BILLING_ALLOW_BYPASS=false
   ```
2. `php artisan config:clear`
3. Billing checkout try કરો (institute billing / platform collect)
4. **પછી તરત** પાછું:
   ```env
   APP_ENV=local
   BILLING_DRIVER=bypass
   ```
5. `php artisan config:clear`
6. Local પર ફરી checkout → bypass થી complete થવું જોઈએ

| Pass | Fail |
|------|------|
| production-like + bypass → payment complete ન થાય / error | production-like માં પણ મફત complete થઈ જાય |
| local + bypass → OK | local માં પણ bypass કામ ન કરે |

---

### ટેસ્ટ C3 — Classes page પર fake data / PII ન હોય

**શું ચેક:** Classes પેજે જૂની hardcoded table (fake email/phone) ન હોય.

**કેવી રીતે:**
1. Platform/Institute admin → sidebar **Classes**
2. Page જુઓ

| Pass | Fail |
|------|------|
| Branding / logo form જેવું real page દેખાય | Fake institutes table, fake emails/phones દેખાય |

---

## C) High ટેસ્ટ (bugs / business logic)

### ટેસ્ટ H1 — Plan limit override ખરેખર કામ કરે

**શું ચેક:** Institutes → Overrides માં max students ઓછું કર્યું હોય તો create block થાય.

**કેવી રીતે:**
1. Platform → Institutes → એક institute → **Overrides** (અથવા plan modules/limits modal)
2. `max students` override = ઓછી સંખ્યા (ઉદા. હાલ 5 students હોય તો override = 5 અથવા વર્તમાન count)
3. Save
4. તે institute admin થી login → **Student Add**
5. Limit ઓળંગીને નવો student save કરવાનો પ્રયાસ

| Pass | Fail |
|------|------|
| Error/block: limit પૂરી | Override હોવા છતાં વધારે student બની જાય |

*(જરૂર હોય તો staff/teacher limit પણ એ જ રીતે)*

---

### ટેસ્ટ H2 — Assign Plan જૂની subscription cancel કરે

**શું ચેક:** નવી plan આપતા જૂની Active/Trial end થાય.

**કેવી રીતે:**
1. Platform → Institutes → **Assign Plan** → Plan A
2. ફરી **Assign Plan** → Plan B
3. Institute view / DB `subscriptions` table જુઓ (phpMyAdmin)

| Pass | Fail |
|------|------|
| ફક્ત નવી plan current Active/Trial; જૂની cancelled/ended | બે Active/Trial એકસાથે દેખાય |

---

### ટેસ્ટ H3 + M4 + M5 — Force change password

**શું ચેક:** Admin ના બનાવેલા login પહેલા password બદલવા મજબૂર કરે; વચ્ચે બીજા password route ન ચાલે.

**કેવી રીતે:**
1. Institute admin → Branch manager / Staff / Teacher બનાવો (password set કરો)
2. Logout
3. તે નવા user થી login
4. Force-change password screen આવે એ check કરો
5. Password બદલ્યા વગર CRM URL ખોલવાનો પ્રયાસ (dashboard)
6. Password બદલો → પછી CRM ખુલે

| Pass | Fail |
|------|------|
| Login પછી તરત force-change; CRM ત્યાં સુધી ન ખુલે | સીધું dashboard ખુલી જાય |
| Password બદલ્યા પછી normal CRM | Force screen skip થઈ જાય |

---

### ટેસ્ટ H4 — Soft delete સુરક્ષા

**શું ચેક:** અવલંબી data હોય તો delete અટકે / સાફ થાય.

#### H4-A Branch
1. એવી branch લો જેમાં batch અથવા student હોય
2. Branch list → Delete

| Pass | Fail |
|------|------|
| “Cannot delete…” જેવો message | Branch delete થઈ જાય, batches/students અનાથ રહે |

#### H4-B Teacher
1. Batch સાથે linked teacher delete કરો
2. તે batch ખોલીને teacher list જુઓ

| Pass | Fail |
|------|------|
| Teacher હટી ગયો / batch_teacher detach | Teacher soft-delete પણ batch પર નામ રહે |

#### H4-C Student
1. Login વાળો student delete કરો
2. તે user થી login try કરો (જો parent/student login હોય)

| Pass | Fail |
|------|------|
| Login બંધ / inactive | Delete પછી પણ login ચાલે |

---

### ટેસ્ટ H5 — Named routes / cascade dropdown

**શું ચેક:** Add forms માં Institute → Branch → Batch cascade તૂટે નહીં.

**કેવી રીતે:**
1. Add Student / Add Batch / Add Fees ખોલો
2. Institute પસંદ કરો (platform હોય તો) → Branch list load થાય
3. Browser F12 → Network: error 404 ન હોવા જોઈએ

| Pass | Fail |
|------|------|
| Dropdowns load; lookups OK | Empty dropdown / 404 on lookups |

---

### ટેસ્ટ H6 — Login title safe

**કેવી રીતે:**
1. Institute login page ખોલો
2. Title / heading સાચું દેખાય (HTML code raw ન દેખાય જેમ `<br>` text તરીકે અથવા script)

| Pass | Fail |
|------|------|
| Title neat દેખાય | Raw HTML / broken markup |

---

### ટેસ્ટ H7 — Website contact + Privacy

**કેવી રીતે:**
1. Marketing website home ખોલો
2. Footer માં email / phone જુઓ — `config/brand.php` સાથે મળે
3. Privacy link click → `/privacy` (અથવા named privacy page) ખુલે, `privacy.html` 404 નહીં

| Pass | Fail |
|------|------|
| Contact + privacy OK | Wrong number/email અથવા dead privacy link |

---

## D) Medium / polish ટેસ્ટ

### ટેસ્ટ M2 — Delete (Ajax) કામ કરે

**કેવી રીતે:**
1. Subject / Notice / Holiday જેવી list ખોલો
2. Trash icon → confirm → delete

| Pass | Fail |
|------|------|
| Success toast + row હટે | Error / page freeze / row રહે |

---

### ટેસ્ટ M3 — Suspended institute block

**કેવી રીતે:**
1. Platform → Institute **Suspend**
2. તે institute admin થી login / CRM page ખોલો

| Pass | Fail |
|------|------|
| Block / logout / inactive message | Suspend પછી પણ પૂરું CRM ચાલે |

---

### ટેસ્ટ M6 — Pagination

**કેવી રીતે:**
1. Platform → Institutes (ઘણા records હોય તો)
2. Project Inquiries list

| Pass | Fail |
|------|------|
| Page 1, 2… ~25 per page | બધા records એક જ page પર endless list |

---

### ટેસ્ટ M8 / M10 — Gate + Parent permissions

**M8:** ઉપરનું `php artisan test --filter=PlanModuleGateTest` — already Pass હોવું જોઈએ.

**M10 Parent:**
1. Roles → **parent** role → permissions માંથી એક permission કાઢો (ઉદા. homework.view)
2. Parent થી login
3. તે module sidebar/page ન દેખાય / 403
4. Permission પાછી આપો → ફરી દેખાય

| Pass | Fail |
|------|------|
| Role edit મુજબ access બદલાય | Permission કાઢ્યા છતાં page ખુલે (જૂનું Gate short-circuit) |

---

### ટેસ્ટ M9 — Fees institute scope (tenant leak નહીં)

**કેવી રીતે:**
1. Institute A admin → Fees list નોંધો
2. Institute B admin થી login → Fees
3. A ના payments B માં ન દેખાય

| Pass | Fail |
|------|------|
| ફક્ત પોતાના institute ના fees | બીજા institute ના payments દેખાય |

---

### ટેસ્ટ M11 — Assign plan પછી blocked modules clear

**કેવી રીતે:**
1. Institute overrides માં કોઈ module **block** કરો, save
2. Assign નવી plan
3. Overrides / modules ફરી જુઓ — blocked list clear થયું હોવું જોઈએ (design મુજબ)

| Pass | Fail |
|------|------|
| Assign પછી blocked clear | જૂના blocked રહીને plan સાથે conflict |

---

### ટેસ્ટ Codes — Admission / employee number unique

**કેવી રીતે:**
1. એક પછી એક 2 students add કરો
2. Admission no જુઓ: `ADM-YYYY-0001`, `ADM-YYYY-0002` (અલગ)

| Pass | Fail |
|------|------|
| અલગ numbers | Duplicate admission no |

---

### ટેસ્ટ Pages — Module Help / Follow-up / Fees / View Student

**કેવી રીતે:**
1. **Module Help** — Add/Edit guide → save → list preview
2. **Follow-up** — View Updates → history + submit remark
3. **Fees** — list status pill (Paid / Partial / Unpaid) + receipt
4. **View Student** — tabs: fees totals, results — page error વગર

| Pass | Fail |
|------|------|
| બધા pages ખુલે, modal કામ કરે | 500 error / blank history / wrong amounts |

---

### ટેસ્ટ Gateways — Razorpay fail-closed

**કેવી રીતે (optional):**
1. `.env`: `BILLING_DRIVER=razorpay` (+ keys હોય તો)
2. `php artisan config:clear`
3. Checkout try
4. પાછું `bypass` + local કરી દેજો

| Pass | Fail |
|------|------|
| “not enabled” / fail message; paid ન થાય | Stub છતાં payment success થઈ જાય |

---

## E) ટેસ્ટ પૂરું થયા પછી

1. `.env` ફરી safe:
   ```env
   APP_ENV=local
   BILLING_DRIVER=bypass
   ```
2. `php artisan config:clear`
3. નીચે નોંધ કરો (મને મોકલી શકો):

| Test ID | Pass / Fail | નોંધ |
|---------|-------------|------|
| C1 | | |
| C2 | | |
| C3 | | |
| H1 | | |
| H2 | | |
| H3/M4/M5 | | |
| H4 | | |
| H5 | | |
| H6 | | |
| H7 | | |
| M2 | | |
| M3 | | |
| M6 | | |
| M8/M10 | | |
| M9 | | |
| M11 | | |
| Codes | | |
| Pages | | |
| Gateways | | |

---

## Executive summary (post-fix)

| Area | Verdict |
|------|---------|
| Tenancy + Plan Gate | Solid; plan Gate prepended; parents via Spatie |
| Blade standards | Main offenders cleaned |
| Billing / activate | Hardened |
| Plan overrides | Via `PlanLimitService` |
| Force password | Default on for admin-created logins |

---

## Standard code checklist (PR review)

- [x] No new business logic inside `@php` in Blade (audit targets)
- [x] No hardcoded PII / fake CRM rows in views
- [x] Named `route()` for admin URLs
- [x] Status via Enums where added
- [x] Plan limits only via `PlanLimitService`
- [x] Soft-delete dependents blocked/cleaned
- [x] `must_change_password = true` by default
- [x] Assign plan ends previous subscription
- [x] Production billing ≠ bypass (unless allowed)

---

*Audit remediation complete 2026-09-10. Production go-live પહેલાં ઉપરની Gujarati checklist ફરી ચલાવો.*
