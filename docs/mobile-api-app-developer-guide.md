# Class Flow Mobile API — App Developer Handbook

**Who this is for:** the person building the iOS / Android / Flutter / React Native app.  
**What this is:** one file that explains the *whole product*, then every screen, then every working API.  
**Date:** 20 Oct 2026 · **API version:** `v1` (live)

If you read only one file before writing a single line of app code, make it this one.

To **click every endpoint yourself** (order + paste-ready JSON): `docs/mobile-api-manual-runbook.md`.

Related files (optional extra reading, not required to start):

| File | What it is |
|------|------------|
| `docs/mobile-api-prd.md` | Product decisions / build plan (backend-oriented) |
| `docs/postman/Class-Flow-Mobile-API.postman_collection.json` | Clickable requests |
| `docs/postman/*.postman_environment.json` | Login emails per role |
| `routes/api.php` | Exact route list |

---

## 0. Read this like a story (5 minutes)

Imagine a coaching class.

- The **institute** is the whole coaching brand (one school / one company).
- A **branch** is one campus (e.g. “SG Highway centre”).
- A **standard** is a class level (e.g. “Class 10”).
- A **batch** is a timed group in a branch (e.g. “10th Maths 9–10 AM”).
- A **student** sits in one or more batches.
- A **teacher** teaches some batches / subjects.
- A **parent** looks at *their child only*.
- Staff like **accountant** / **receptionist** / **principal** / **branch manager** run the office.

The **web CRM** already does all of this on a laptop.  
The **mobile API** is the same work, from a phone.

Your app is **not** a new product. It is a phone window into the same data.

```
Phone app  --HTTPS-->  Laravel API  --MySQL-->  same database as the website
```

If the website says “this user cannot collect fees”, the API says the same thing (`403`).  
Do **not** invent extra rules in the app. Ask the API. Show what it allows.

---

## 1. How HTTP works here (baby version)

The phone sends a **request**. The server sends a **response**.

Every request needs:

1. **URL** — which page of the API (`/api/v1/students`)
2. **Method** — what you want to do
   - `GET` = read
   - `POST` = create / do an action (login, toggle status, mark attendance)
   - `PUT` = replace/update a record
   - `PATCH` = partial update (profile also accepts `POST`)
   - `DELETE` = delete
3. **Headers** — labels on the envelope
4. **Body** — the form data (not used on most GETs)

### 1.1 Base URL

All mobile routes sit under:

```
{APP_URL}/api/v1
```

Local Laragon example (this project):

```
http://localhost/iv_classes/public/api/v1
```

Production will look like:

```
https://your-domain.com/api/v1
```

Put the base URL in one config constant. Never hard-code it in 50 screens.

### 1.2 Headers you always send

```http
Accept: application/json
Authorization: Bearer {token}
```

For JSON bodies also send:

```http
Content-Type: application/json
```

For photos / homework files use `multipart/form-data` (the HTTP library sets the boundary). **Do not** send `Content-Type: application/json` on file uploads.

Login is the only call that does **not** send a Bearer token.

### 1.3 The envelope (every JSON response looks like this)

**Success**

```json
{
  "success": true,
  "message": "Logged in.",
  "data": { }
}
```

**Failure**

```json
{
  "success": false,
  "message": "Unauthorized.",
  "errors": null
}
```

**Validation failure (HTTP 422)**

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

App rule:

- If `success === true` → use `data`.
- If `success === false` → show `message`. If `errors` is an object, show those field errors under the inputs.

PDF / CSV / file-download endpoints are **not** JSON. They return a file. Save/share the bytes.

### 1.4 Lists are paginated

Most lists return:

```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [ { "id": 1 } ],
    "meta": {
      "current_page": 1,
      "last_page": 4,
      "per_page": 15,
      "total": 52
    }
  }
}
```

Query params:

| Param | Default | Max | Meaning |
|-------|---------|-----|---------|
| `page` | 1 | — | which page |
| `per_page` | 15 | 50 | rows per page |
| `q` | — | — | search text (when the endpoint supports it) |
| `status` | — | — | often `active` / `inactive` |

Infinite-scroll: request `page=2`, `page=3`, … stop when `current_page >= last_page`.

### 1.5 HTTP status codes (what the app should do)

| Code | Meaning | App action |
|------|---------|------------|
| 200 | OK | Show data |
| 201 | Created | Show success, go to detail or refresh list |
| 401 | Not logged in / token dead | Clear token, open Login |
| 403 | Logged in, but not allowed | Hide the button next time; toast the `message` |
| 404 | Not found **or** not in your scope | Treat as “this record is not available”, not a broken app |
| 422 | Form is wrong | Show field errors |
| 429 | Too many requests (login is 5/min) | Wait and retry |
| 500 | Server bug | Generic “try again” |

**Important:** a teacher asking for another teacher’s private page often gets **404**, not 403. That is on purpose (don’t leak that the row exists). Do not tell the user “API is down”.

---

## 2. The golden rule of this API

> **Permission-driven, not role-hardcoded.**

After login, `data.user.permissions` is a list of strings like `"student.view"`, `"fees.add"`.

```text
IF user.permissions contains "fees.add"
    show Collect Payment button
ELSE
    hide it
```

Do **not** write:

```text
IF role == accountant   // ❌ wrong
    show Collect Payment
```

Why? An institute admin can grant `fees.add` to a branch manager tomorrow. The API will allow it. If you hardcoded “only accountant”, the app would be wrong.

`mobile_role` is only a **hint** for:

- which dashboard layout to use (`institute` / `teacher` / `parent`)
- empty-state copy
- default demo accounts

The menu still comes from `permissions`.

Helper you should write once:

```ts
function can(user, permission: string): boolean {
  return user.permissions.includes(permission);
}

function canAny(user, perms: string[]): boolean {
  return perms.some((p) => user.permissions.includes(p));
}
```

---

## 3. Who can use the app

| `mobile_role` | Who | Default job on the phone |
|---------------|-----|--------------------------|
| `branch_manager` | Runs one (or a few) campus(es) | Students, inquiries, attendance, batches, directory, fees *view* |
| `principal` | Same shape as BM | Same APIs as BM |
| `accountant` | Money | Collect fees, GST, income/expense |
| `receptionist` | Front desk | Inquiries, follow-ups, add student, view batches |
| `teacher` | Classroom | Own batches only. Default template is *small* (dashboard + batch view). Extra modules work if the institute grants them. |
| `parent` | Guardian | Own children: homework, exams, results, fees due, leave, notices |

**Cannot log in on mobile (HTTP 403):**

- Platform Admin (runs the whole SaaS)
- Institute Admin (runs Roles / Settings / Billing on web)

Message: *“Platform and Institute Admin accounts use the web portal only.”*

---

## 4. How data is filtered (you don’t pass institute_id)

Every user belongs to **one institute**. The API already knows it from the token.

You almost never send `institute_id`. If a form needs it, the server fills it.

Three walls:

| Wall | Who | What they see |
|------|-----|----------------|
| **Branch wall** | Branch manager, principal (and staff with assigned branches) | Only their campus(es) |
| **Institute-wide** | Accountant, receptionist (unless they are assigned to branches) | Whole institute |
| **Own-batch wall** | Teacher | Only batches where they are class teacher or subject teacher |
| **Own-child wall** | Parent | Only their children. Pass `student_id` to switch child |

If you request a batch/student outside the wall → **404**.

### 4.1 Parent child switcher

A parent can have two kids. After login, `user.children[]` has them.

Every parent screen that is “about a child” must send:

```
?student_id=41
```

If you forget, the API uses the first child. That looks like a bug (“wrong homework”). Always pass the selected child.

---

## 5. Auth screens (build these first)

### Screen A — Login

**When:** app opens and there is no token.

`POST /auth/login`  
No Bearer token.  
Body:

```json
{
  "email": "teacher@demo-institute.test",
  "password": "password"
}
```

Success `200`:

```json
{
  "success": true,
  "message": "Logged in.",
  "data": {
    "token": "1|abc...very-long...",
    "token_type": "Bearer",
    "mobile_role": "teacher",
    "must_change_password": false,
    "user": { }
  }
}
```

Save on device (secure storage, not plain SharedPreferences if you can avoid it):

| Key | From | Why |
|-----|------|-----|
| `token` | `data.token` | every later call |
| `mobile_role` | `data.mobile_role` | dashboard layout |
| `user` | `data.user` | name, avatar, permissions, children, batches |
| `must_change_password` | `data.must_change_password` | force password screen |

`user` important fields:

| Field | Use |
|-------|-----|
| `id` | this login’s **user** id (todos, attendance mark `user_id`) |
| `name`, `email`, `phone`, `avatar_url` | profile header |
| `permissions` | menu + buttons |
| `roles` | display only |
| `institute` | institute name in the header |
| `branches[]` | campus picker (`id`, `name`, `is_primary`) |
| `branch_ids` | assigned campus ids |
| `teacher` | `{ id, employee_code }` or `null` — this `id` is **teacher_id** for `/teachers/{id}/…` |
| `staff` | `{ id, employee_code }` or `null` — **staff_id** |
| `children[]` | parent only — each child is a **student** |
| `batches[]` | teacher only — assigned batches |

**IDs that look similar but are different**

| Name | Whose table | Example use |
|------|-------------|-------------|
| `user.id` | `users` | token owner; staff attendance `user_id` |
| `teacher.id` | `teachers` | `/teachers/6/batches` |
| `staff.id` | `staff` | `/staff/3/attendance` |
| `student.id` / child `id` | `students` | `/students/41` |
| `batch.id` | `batches` | `/batches/5/students` |

Never put `user.id` into `/teachers/{id}`.

Login errors:

| Situation | Code | What to show |
|-----------|------|----------------|
| Wrong email/password | 422 | “These credentials do not match our records.” |
| Admin account | 403 | Use the website |
| Inactive user | 403 | Account inactive / verify OTP |
| Inactive branch | 403 | Branch inactive |
| Institute suspended | 403 | Institute suspended |
| 5 failed logins | 422 throttle | Wait N seconds |

New login **kills the previous mobile token** for that user. One phone session at a time.

### Screen B — Force change password

If `must_change_password === true`, block the rest of the app.

`POST /auth/change-password`

```json
{
  "current_password": "old",
  "password": "NewPass123!",
  "password_confirmation": "NewPass123!"
}
```

Then call `GET /auth/me` and continue.

### Screen C — Home bootstrap

Right after login (and whenever the app resumes), call:

`GET /auth/me`

Same `user` object as login. Refresh permissions (admin may have changed them).

### Screen D — Edit profile

`POST /profile` **or** `PATCH /profile`  
(JSON for name/phone; `multipart` if sending `avatar`)

```json
{ "name": "Riya Shah", "phone": "9824000000" }
```

Avatar: field name `avatar`, image, max 2 MB.

### Screen E — Logout

`POST /auth/logout`  
Then delete the token locally even if the call fails.

---

## 6. Suggested app map (page-wise)

Build **one app**. After login, show/hide tabs from permissions.

### 6.1 Always

| App page | API | Permission |
|----------|-----|------------|
| Login | `POST /auth/login` | — |
| Change password | `POST /auth/change-password` | auth |
| Profile | `GET /auth/me`, `POST /profile` | auth |
| Home / Dashboard | `GET /dashboard` | `dashboard.view` |
| Dashboard chart | `GET /dashboard/data/{widget}` | `dashboard.view` + that widget |

### 6.2 Branch manager / Principal (office app)

Typical bottom tabs: **Home · People · Academics · CRM · More**

| App page | Show if | Primary APIs |
|----------|---------|--------------|
| My branches | `branch.view` | `GET /branch` |
| Batches list / add / edit / tabs | `batch.view` (+ add/edit) | `/batches` |
| Teachers directory + tabs | `teacher.view` | `/teachers` |
| Staff directory + tabs | `staff.view` | `/staff` |
| Users directory | `users.view_teacher` etc. | `GET /users?tab=` |
| Students | `student.view` | `/students` |
| Inquiries | `inquiry.view` | `/inquiries` |
| Follow-ups | `follow_up.view` | `/follow-ups` |
| Student attendance | `attendance.view` | `/attendance/student` |
| Staff attendance | `teacher_attendance.view` | `/attendance/teacher` |
| Fees (view / receipt) | `fees.view` | `/fees` |
| Exams | `exam.view` | `/exams` |
| Homework | `homework.view` | `/homework` |
| Notices | `notice.view` | `/notices` |
| To-dos | `todo.view` | `/todos` |
| Holidays | `holiday.view` | `/holidays` |
| Leave (approve) | `student_leave.view` / `staff_leave.view` | `/leave-requests/...` |

Branch **create / edit / delete is web-only**. The phone can only **list** branches (`GET /branch`).

### 6.3 Teacher (class app)

Default teacher can open **Home** and **My batches**.  
If the institute later grants `attendance.add`, then show Mark Attendance — same endpoints, already scoped to own batches.

| App page | Show if | Notes |
|----------|---------|-------|
| Home | `dashboard.view` | `kind: "teacher"` |
| My batches | `batch.view` | list is already filtered |
| Mark attendance | `attendance.add` | `batch_id` from own batches |
| Homework | `homework.view` / `.add` | own batches only |
| Exams / results | `exam.*` / `result_announcement.*` | own batches |
| Study material | `study_material.*` | own batches |
| Notices | `notice.view` | |
| Apply leave | `staff_leave.add` | `POST /leave-requests/staff` |

A default demo teacher hitting `/teachers` gets **403**. That is correct. Do not show Teachers directory unless `teacher.view` is in permissions.

### 6.4 Parent (child app)

| App page | Show if | Notes |
|----------|---------|-------|
| Home + child switcher | `dashboard.view` | pass `student_id` |
| Attendance calendar | `attendance.view` or parent dashboard | `/attendance/student/reports/student/{id}` |
| Homework | `homework.view` | pass `student_id` / batch from enrollment |
| Exams | `exam.view` | |
| Results | `result_announcement.view` | |
| Study material | `study_material.view` | |
| Notices | `notice.view` | |
| Holidays | `holiday.view` | |
| Fees due | `fees.view` or parent dashboard | dashboard already includes `due_invoices` |
| Apply leave | `student_leave.add` | `POST /leave-requests/student` |

### 6.5 Accountant

Fees collect, fee follow-ups, GST, income & expense. Inventory only if `inventory.view` **and** the institute plan includes inventory (otherwise 403).

### 6.6 Receptionist

Inquiries + follow-ups + add student + view batches. Convert-to-admission needs `inquiry.convert` (not default).

---

## 7. Dashboard

`GET /dashboard`

Optional query:

- `branch_id` — institute/BM/principal campus filter
- `student_id` — parent child filter

Response `data.kind` is one of: `institute` | `teacher` | `parent`.

Render **three different home UIs**. Do not force one layout.

### 7.1 Institute / BM / Principal / Accountant / Receptionist

`kind: "institute"`

Useful fields: `greeting`, `name`, `persona` (`branch_manager` / `principal` / …), `place_name`, `selected_branch_id`, `branches[]`, `summary`, `kpis`, `quick_actions`, `attention`, `plan`, `allowed_widgets`.

Campus chips: if `branches.length > 1`, let the user pick `branch_id` and reload dashboard.

### 7.2 Teacher

`kind: "teacher"`

Contains `batches[]` (today’s pulse: `is_marked`, `is_live`, present/absent counts), `upcoming_exams`, `recent_homework`, `notices`, `quick_actions` with keys:

`mark_attendance` · `give_homework` · `schedule_exam` · `upload_material` · `apply_leave`

Only keys that the user is allowed appear. Use them as Home shortcuts.

### 7.3 Parent

`kind: "parent"`

Contains `children[]` (switcher), `selected_student_id`, `enrollments`, `upcoming_exams`, `homework`, `results`, `due_invoices`, `notices`, `holidays`, `leaves`.

### 7.4 Charts

`GET /dashboard/data/{widget}`

Only call widgets listed in `allowed_widgets`. Unknown / forbidden widget → 403.

| Kind | Widget keys |
|------|-------------|
| Institute | `fees_trend`, `attendance_trend`, `admissions`, `lead_sources`, `standards`, `branches`, `payment_methods`, `batch_attendance` |
| Teacher | `attendance_trend`, `batch_attendance` |
| Parent | `child_attendance`, `child_results` |

Parent chart calls should include `student_id`.

---

## 8. Module cookbook (working screens)

For each module: **what the page is**, **how to load it**, **how to save**, **what 403 means**.

Dates: `YYYY-MM-DD` (example `2026-10-20`).  
Times: `HH:mm` 24-hour (example `09:00`).  
Timezone: Asia/Kolkata.

---

### 8.1 Branches (read only)

**Page:** Settings / “My campus”

`GET /branch`  
Permission: `branch.view`

Returns the campuses this user may work in. Use `id` as `branch_id` on later filters.

There is **no** mobile API to create or rename a branch.

---

### 8.2 Batches

**List page:** Batches  
`GET /batches?per_page=15&status=active&branch_id=&standard_id=`  
Permission: `batch.view`

**Detail page:** `GET /batches/{id}`  
Tabs on the same page:

| Tab | Call | Extra permission |
|-----|------|------------------|
| Students | `GET /batches/{id}/students` | `batch.tab_students` or `batch.view` |
| Attendance | `GET /batches/{id}/attendance?date=2026-10-20` | `batch.tab_attendance` or `attendance.view` |
| Attendance report | `GET /batches/{id}/attendance-report?year=2026&month=10` | `batch.tab_attendance_report` or `attendance.view` |
| Exams | `GET /batches/{id}/exams` | `batch.tab_exam` **or** `exam.view` — `batch.view` alone is **not** enough |
| Homework | `GET /batches/{id}/homework` | `batch.tab_homework` or `homework.view` |
| Notices | `GET /batches/{id}/notices` | `batch.tab_notice` or `notice.view` |

**Create page** (`batch.add`):

```json
{
  "name": "10th Maths Morning",
  "branch_id": 5,
  "standard_id": 9,
  "status": "active",
  "academic_year": "2026-27",
  "capacity": 20,
  "start_time": "09:00",
  "end_time": "10:00",
  "subjects": [
    { "subject_id": 3, "teacher_user_id": 19, "fees_amount": 1500 }
  ],
  "teacher_ids": [19]
}
```

`POST /batches` → `201`, `data.id` is the new batch.

**Edit:** `PUT /batches/{id}` same body.  
**Toggle active:** `POST /batches/{id}/status` (needs `batch.status`).  
Do **not** call `/batches/status` with an empty id.

CSV:

- `GET /export/batches/{id}/students`
- `GET /export/batches/{id}/attendance-report?year=&month=`

---

### 8.3 Teachers directory

**List:** `GET /teachers?per_page=15&status=active&q=`  
Permission: `teacher.view` or `users.view_teacher`  
Each row `id` = **teacher profile id**.

**Detail:** `GET /teachers/{teacher_id}` (`teacher.show`)

**Tabs:**

| Tab | Call |
|-----|------|
| Batches | `GET /teachers/{id}/batches` |
| Login history | `GET /teachers/{id}/login-history` |
| Attendance month | `GET /teachers/{id}/attendance?year=2026&month=10` |
| Assigned work | `GET /teachers/{id}/assigned-work` |

**Create** (`teacher.add` or `users.add_teacher`):

```json
{
  "name": "Anita Patel",
  "email": "anita@example.com",
  "password": "Password123",
  "password_confirmation": "Password123",
  "phone": "9876543210",
  "status": "active",
  "primary_branch_id": 5,
  "batch_ids": [5],
  "branch_ids": [5],
  "specialization_ids": [3],
  "joining_date": "2026-06-01",
  "expected_login_time": "09:00",
  "grace_minutes": 10
}
```

Avatar: multipart field `avatar`.

**Update:** `PUT /teachers/{id}` (password optional).  
**Delete:** `DELETE /teachers/{id}`  
**Toggle:** `POST /teachers/{id}/status`

CSV: `GET /export/teachers/{id}/attendance`, `GET /export/teachers/{id}/homework`

---

### 8.4 Staff directory

Same idea as teachers.

1. `GET /staff/roles` → pick `role_id` (principal / accountant / receptionist / custom). You **cannot** create a branch manager from mobile (that happens when a branch is created on web).
2. `POST /staff` with `role_id`, `primary_branch_id`, name/email/password.
3. Tabs: `/staff/{id}`, `/attendance`, `/login-history`, `/assigned-work`
4. Toggle: `POST /staff/{id}/status`

Permissions: `staff.view` / `staff.add` or `users.add_accountant` (matching role).

---

### 8.5 Users (merged directory)

`GET /users?tab=teacher|branch_manager|principal|receptionist|accountant`

Permission: `users.view_{tab}`

Per-user permission override (rare on mobile, usually institute admin):

- `GET /users/{user_id}/permissions`
- `POST /users/{user_id}/permissions`  
  Permission: `roles.assign_permissions`

---

### 8.6 Students (admission)

**List:** `GET /students?branch_id=&standard_id=&batch_id=&status=active&q=`  
`student.view`  
Parents skip the permission check but only see their children.

**Detail:** `GET /students/{id}`

**Add student page** (`student.add`) — this is a long form, same as web admission:

```json
{
  "branch_id": 5,
  "standard_id": 9,
  "name": "Aarav Shah",
  "dob": "2014-06-15",
  "gender": "Male",
  "category": "General",
  "admission_date": "2026-10-20",
  "father_name": "Nirav Shah",
  "father_email": "nirav@example.com",
  "father_phone": "9000011111",
  "status": "active",
  "enrollments": [
    { "batch_id": 5, "total_fees": 10000, "discount": 0, "roll_no": "1" }
  ]
}
```

`enrollments` min 1. Photo: multipart `photo` (max 2 MB).  
Gender: `Male` / `Female` / `Other`. Category: `General` / `OBC` / `SC` / `ST`.  
Phone: 10–15 digits.

**Update:** `PUT /students/{id}`  
**Activate/deactivate:** `POST /students/{id}/status` (`student.status`)  
**Form-fee receipt JSON:** `GET /students/{id}/receipt`  
**PDF file:** `GET /students/{id}/receipt-pdf`

CSV: `GET /export/students`, `.../students/{id}/attendance`, `.../students/{id}/results`

---

### 8.7 Inquiries (front desk)

Walk-in lead **before** they become a student.

**List:** `GET /inquiries`  
**Detail:** `GET /inquiries/{id}` (includes `follow_ups`)  
**Create** (`inquiry.add`):

```json
{
  "student_name": "Diya Patel",
  "father_name": "Amit Patel",
  "father_email": "amit@example.com",
  "father_phone": "9000022222",
  "gender": "Female",
  "category": "General",
  "inquiry_date": "2026-10-20",
  "follow_up_date": "2026-10-25",
  "follow_up_remark": "Wants evening batch",
  "branch_id": 5,
  "standard_id": 9,
  "source": "walk-in"
}
```

`follow_up_date` must be on/after `inquiry_date`.

**Update:** `PUT` — cannot edit after converted.  
**Delete:** `DELETE` (`inquiry.delete`, often not granted).  
**Receipt:** `GET /inquiries/{id}/receipt` and `.../receipt-pdf`

**Convert to student (admission):**

1. `GET /inquiries/{id}/convert` → prefill + `batches` list. If `already_converted`, stop.
2. `POST /inquiries/{id}/convert` with student enrollments (same shape as add student). Needs `inquiry.convert`.

---

### 8.8 Follow-ups (inquiry module)

**List page:** `GET /follow-ups` (`follow_up.view`)  
**Add on an inquiry:** `POST /follow-ups/{inquiryId}`

```json
{
  "follow_up_date": "2026-10-28",
  "remark": "Called, will visit Saturday"
}
```

CSV: `GET /export/follow-ups`

---

### 8.9 Student attendance

**Mark page (the daily roster):**

1. User picks a batch + date.
2. `GET /attendance/student?batch_id=5&date=2026-10-20`  
   Permission: `attendance.view` or `.add` or `.edit`  
   `data.students` = roster with current status. `data.meta.is_marked` = already saved today?
3. User taps Present / Absent / Late on each row.
4. `POST /attendance/student/{batchId}`

```json
{
  "attendance_date": "2026-10-20",
  "records": [
    { "student_id": 41, "status": "present" },
    { "student_id": 42, "status": "absent", "remark": "Sick" }
  ]
}
```

Status values: `present` | `absent` | `late`  
First save needs `attendance.add`. Editing an already-marked day needs `attendance.edit`.

**Month report for a batch:**  
`GET /attendance/student/reports/batch/{batchId}?year=2026&month=10`

**One student calendar (parent loves this):**  
`GET /attendance/student/reports/student/{studentId}?batch_id=5&year=2026&month=10`

Teacher: `batch_id` must be one of their assigned batches.

---

### 8.10 Teacher / staff attendance

**Roster:** `GET /attendance/teacher?date=2026-10-20&branch_id=5`  
`data.staff[]` has `user_id`, `profile_type` (`teacher`/`staff`), `profile_id`.

**Save:** `POST /attendance/teacher`

```json
{
  "attendance_date": "2026-10-20",
  "branch_id": 5,
  "records": [
    { "user_id": 19, "status": "present" }
  ]
}
```

Here `user_id` is the **login user**, not `teacher_id`. Status: `present` | `absent` | `late`.

**Month calendars:**

- `GET /attendance/teacher/reports/teacher/{teacher_id}?year=&month=`
- `GET /attendance/teacher/reports/staff/{staff_id}?year=&month=`

Permission: `teacher_attendance.view` / `.add` / `.edit`

---

### 8.11 Fees

**Payment list:** `GET /fees` with filters (`branch_id`, `batch_id`, `method`, dates). `fees.view`

**One student’s bills:** `GET /fees/student-summary?student_id=41` (`fees.show`)

**Collect payment page** (`fees.add` — accountant by default, not BM):

```json
{
  "student_id": 41,
  "batch_id": 5,
  "payment_date": "2026-10-20",
  "amount": 500,
  "payment_method": "Cash",
  "note": "October fee"
}
```

`POST /fees` → `201`. Then you can open receipt:

- JSON: `GET /fees/{payment_id}/receipt`
- PDF: `GET /fees/{payment_id}/receipt-pdf`

**Delete payment:** `DELETE /fees/{payment_id}` (`fees.delete` — often not granted)

**Fee follow-ups (dues):**

- List: `GET /fees/follow-ups` (`fees_follow_up.view`)
- Log a call: `POST /fees/follow-ups/{invoiceId}`
- PDF: `GET /fees/follow-ups/export-pdf`

---

### 8.12 GST invoices (accountant)

- List: `GET /gst-invoices` (`gst_invoice.view`)
- Create from a payment: `POST /gst-invoices/generate/{payment_id}` (`gst_invoice.add`)
- Detail / PDF payload: `GET /gst-invoices/{id}`

---

### 8.13 Income & expense (accountant)

**Categories** (need these before add entry):

- `GET /income-expense-categories?type=income|expense`
- `POST /income-expense-categories` `{ "name", "type": "income"|"expense" }`
- `PUT /income-expense-categories/{id}`
- `POST /income-expense-categories/{id}/status`

**Entries:**

- `GET /income-expense?type=expense`
- `POST /income-expense`

```json
{
  "category_id": 2,
  "title": "Electricity",
  "amount": 1200,
  "entry_date": "2026-10-20",
  "payment_method": "UPI",
  "branch_id": 5,
  "note": "October bill"
}
```

- `DELETE /income-expense/{id}` (`income_expense.delete`)

---

### 8.14 Inventory & asset categories

Only if `inventory.*` / `assets_category.*` **and** the institute plan includes inventory. Demo accountant often gets **403** until both are true.

**Categories:** `/asset-categories` CRUD + `/status`  
**Assets:** `/inventory-assets`

```json
{
  "category_id": 1,
  "name": "Projector",
  "quantity": 1,
  "purchase_date": "2026-10-01",
  "purchase_amount": 25000,
  "status": "in_stock",
  "branch_id": 5
}
```

`purchase_date` cannot be in the future.

---

### 8.15 Exams

**List:** `GET /exams?batch_id=` (`exam.view`)  
**Detail:** `GET /exams/{id}`  
**Download paper:** `GET /exams/{id}/download` (file, `exam.download`)

**Create** (`exam.add`), JSON or multipart if attaching a file:

```json
{
  "batch_id": 5,
  "subject_id": 3,
  "title": "Chapter 4 test",
  "exam_date": "2026-11-02",
  "start_time": "10:00",
  "end_time": "11:00",
  "total_marks": 50,
  "description": "Bring calculator"
}
```

`total_marks` minimum **1**. Attachment max 10 MB: pdf/doc/docx/jpg/png/zip.

**Update / delete:** `PUT` / `DELETE`  
Remove file: `remove_attachment: true`

**Marks sheet (before publishing):**  
`GET /exams/{id}/result-sheet`  
Needs `result_announcement.add` or `.edit`. Returns students + present/absent for data entry.

---

### 8.16 Result announcements

**List / detail:** `/result-announcements`  
**Save marks:** `POST /result-announcements`

```json
{
  "exam_id": 12,
  "result_date": "2026-11-05",
  "publish": true,
  "results": [
    {
      "student_id": 41,
      "marks_obtained": 42,
      "is_attempted": true,
      "attendance_status": "present",
      "remark": "Good"
    }
  ]
}
```

Marks cannot exceed that exam’s `total_marks`. If a result already exists, the user needs `result_announcement.edit`.

**Preview:** `GET /result-announcements/{id}/preview`  
**PDF:** `GET /result-announcements/{id}/export-pdf`  
**Delete:** `DELETE`

---

### 8.17 Homework

**List:** `GET /homework?batch_id=`  
**Detail / download:** `GET /homework/{id}`, `GET /homework/{id}/download`

**Create** (`homework.add`):

```json
{
  "title": "Exercise 5.2",
  "type": "text",
  "description": "Complete Q1–Q10",
  "batch_id": 5,
  "subject_id": 3,
  "homework_date": "2026-10-20",
  "due_date": "2026-10-22"
}
```

`type`: `text` | `file` | `file_text`  
If type includes a file, send multipart field `attachment`.

---

### 8.18 Study materials

Same pattern as homework, but a **file is required** on create.

`POST /study-materials` multipart: `title`, `batch_id`, `subject_id`, `description`, `file`.

Download: `GET /study-materials/{id}/download`

---

### 8.19 Notices

**List / detail / create / update / delete / toggle**

```json
{
  "title": "Diwali holiday",
  "body": "Centre closed 20–24 Oct.",
  "notice_date": "2026-10-18",
  "audience": "all",
  "branch_ids": [5],
  "batch_ids": [5]
}
```

`audience`: `all` | `parents` | `students` | `teachers`  
Optional image field `image` (jpg/png/webp, max 5 MB).  
Toggle: `POST /notices/{id}/status`

---

### 8.20 Holidays

`GET /holidays`  
`POST /holidays`

```json
{
  "title": "Diwali",
  "start_date": "2026-10-20",
  "end_date": "2026-10-24",
  "reason": "Festival",
  "branch_ids": [5],
  "batch_ids": []
}
```

`PUT` / `DELETE` with `holiday.edit` / `holiday.delete`. Default parent can **list** only.

---

### 8.21 To-dos

**My tasks:** `GET /todos` (`todo.view`)  
**Create:** `POST /todos`

```json
{
  "assigned_to_user_ids": [18],
  "due_date": "2026-10-25",
  "tasks": [{ "title": "Call pending inquiries" }]
}
```

`assigned_to_user_ids` are **user ids** (staff/teachers), never parents/students.

**Update:** `PUT /todos/{id}`  
**Delete:** `DELETE`  
**Approve / reject:** `POST /todos/{id}/approve`, `POST /todos/{id}/reject`  
**Carry forward overdue:** `POST /todos/carry-forward` (`todo.carry_forward`)  
CSV: `GET /export/todos`

---

### 8.22 Leave

**Staff apply (teacher applying for self):**  
`POST /leave-requests/staff` (`staff_leave.add`)

```json
{
  "from_date": "2026-11-10",
  "to_date": "2026-11-12",
  "reason": "Family function"
}
```

**Staff list (approver):** `GET /leave-requests/staff`

**Parent apply for a child:**  
`POST /leave-requests/student` (`student_leave.add`)

```json
{
  "student_id": 41,
  "from_date": "2026-11-10",
  "to_date": "2026-11-10",
  "reason": "Fever"
}
```

**Student leave list:** `GET /leave-requests/student`

**Approve / reject:**

- `POST /leave-requests/{id}/approve`
- `POST /leave-requests/{id}/reject` optional `{ "review_note": "..." }`

Permissions: `staff_leave.approve` / `student_leave.approve` (and matching reject). Default BM/teacher/parent templates may not include approve.

---

### 8.23 Subjects & standards (academic masters)

Not in default BM/teacher templates. If `subject.view` / `standard.view` is granted:

| Action | Subject | Standard |
|--------|---------|----------|
| List | `GET /subjects` | `GET /standards` |
| Create | `POST /subjects` `{ "name": "Physics" }` | `POST /standards` `{ "name": "Class 11" }` |
| Update | `PUT /subjects/{id}` | `PUT /standards/{id}` |
| Delete | `DELETE` (soft; standard blocked if still in use) | same |
| Toggle | `POST /subjects/{id}/status` | `POST /standards/{id}/status` |

Creating a name that was deleted **restores** the old row instead of inserting a duplicate.

You need these ids when building Batch / Student / Exam forms (`standard_id`, `subject_id`). If the logged-in role cannot list them, get ids from batch detail (`batch.standard`, `batch.subjects`) instead.

---

## 9. Files, photos, PDFs

| What | How |
|------|-----|
| JSON APIs | `Content-Type: application/json` |
| Photos (`avatar`, `photo`, `image`) | `multipart/form-data`, field names above |
| Homework/exam attachment | multipart field `attachment` |
| Study material | multipart field `file` (required on create) |
| Receipt / GST / result PDF | `GET` the `*-pdf` URL with the **same Bearer header**; response is a file, not `{success:true}` |
| CSV exports | same as PDF — download the body, filename from `Content-Disposition` |
| Image URLs in JSON | `photo_url` / `avatar_url` are full `http…/storage/…` links. Put them in an `<Image>`. If 403/404, the file was deleted on disk. |

Max sizes (server): avatar/photo 2 MB, notice image 5 MB, exam file 10 MB.

---

## 10. Recommended client architecture

```
AuthRepository
  login / logout / me / changePassword / updateProfile

Permission
  can("student.view")

ApiClient
  baseUrl + Bearer + Accept
  on 401 → logout
  on 422 → map errors to form
  timeout + retry once on GET

Modules
  BatchesApi, StudentsApi, AttendanceApi, …
```

**Never** concatenate URLs like `/teachers/${teacherId}/batches` when `teacherId` is empty. You will call `/teachers/batches` and get a confusing 404.

**Store after login:**

```
token, user, selectedBranchId, selectedStudentId (parent), selectedBatchId
```

---

## 11. Default permission cheat-sheet

This is the **starting template**, not a ceiling. Always prefer `user.permissions`.

| Screen | BM / Principal | Accountant | Receptionist | Teacher (default) | Parent |
|--------|----------------|------------|--------------|-------------------|--------|
| Dashboard | yes | yes | yes | yes | yes |
| Branch list | view | — | — | — | — |
| Batches | view add edit | — | view | view (own) | — |
| Teachers / staff | view | — | — | — | — |
| Students | view add edit status | view | view add | — | own child |
| Inquiries | view add edit | — | view add edit | — | — |
| Student attendance | view add edit | — | — | only if granted | view child |
| Staff attendance | view add edit | — | — | — | — |
| Fees collect | view only | **add** | — | — | view due |
| GST / I&E | — | yes | — | — | — |
| Inventory | — | only if granted | — | — | — |
| Exam / homework | view (+ homework add) | — | — | only if granted | view |
| Results | — | — | — | only if granted | view |
| Notices | view add | — | — | only if granted | view |
| Todos | view add | — | — | — | — |
| Student leave apply | — | — | — | — | **add** |
| Staff leave apply | — | — | — | only if granted | — |

Teacher extra modules (attendance, homework, exams…) **work on mobile the moment an institute grants the permission.** You do not need a new API. Show the screen when `can(...)` becomes true.

---

## 12. What you must **not** build

| Thing | Why |
|-------|-----|
| Platform admin / institute admin login | 403 by design |
| Create / edit / delete **branch** | Web only |
| Roles & permissions screen, billing, branding, email templates | Web only |
| Push notifications / FCM / in-app inbox | Not built yet (Phase 7 deferred) |
| Offline-first sync | Not assumed. Online + retry is enough |
| A second permission system inside the app | Use `user.permissions` |

---

## 13. Common mistakes (please don’t)

1. **Hardcoding menus by role.** Use permissions.
2. **Using `user.id` as `teacher_id`.** Nested `user.teacher.id` is the teacher profile.
3. **Staff attendance with `teacher_id`.** Marking uses `user_id`.
4. **Parent screens without `student_id`.** Wrong child’s data appears.
5. **Calling `/teachers/batches` with no id.** Looks like a missing page. It isn’t.
6. **Treating 404 as “API broken”.** Often means “not in your campus / not your batch”.
7. **Treating teacher 403 on `/teachers` as a bug.** Default teacher cannot open the directory.
8. **Sending `institute_id` from the phone and hoping to switch institutes.** The token is locked to one institute.
9. **JSON content-type on file upload.** Photo silently missing.
10. **Forgetting `Accept: application/json`.** Laravel may return an HTML login page, which your JSON parser will hate.
11. **Keeping an old token after logout.** Login issues a new one and deletes the old `mobile` token.
12. **Batch academic tabs with only `batch.view`.** Exam/homework/attendance tabs need the module permission.

---

## 14. Try it (Postman)

1. Import `docs/postman/Class-Flow-Mobile-API.postman_collection.json`.
2. Import **one** environment (do not run with Environment = none).
3. Run **Auth → Login (saves token)** first.
4. Disable **Change password** and **Logout** while exploring.

Environments (password is `password` on **fresh seed**; live local users may have changed it):

| File | Typical email | Role |
|------|---------------|------|
| `Class-Flow-Mobile-API.postman_environment.json` | branch manager for a live institute | `branch_manager` |
| `Class-Flow-Mobile-API.teacher.postman_environment.json` | `teacher@demo-institute.test` | `teacher` |
| `Class-Flow-Mobile-API.parent.postman_environment.json` | parent demo | `parent` |
| `Class-Flow-Mobile-API.receptionist.postman_environment.json` | `receptionist@demo-institute.test` | `receptionist` |
| `Class-Flow-Mobile-API.accountant.postman_environment.json` | `accountant@demo-institute.test` | `accountant` |
| `Class-Flow-Mobile-API.principal.postman_environment.json` | `principal@demo-institute.test` | `principal` |

Demo seed also includes `admin@demo-institute.test` — that user is **web only**.

Empty path ids are skipped by the collection (so you don’t see fake 404s). In the real app, you must pass real ids.

---

## 15. First week build order (if you are new)

1. Login + secure token + 401 → login screen.  
2. `GET /auth/me` + permission helper.  
3. Profile + logout.  
4. Dashboard for all three `kind`s (institute / teacher / parent).  
5. Parent: child switcher + homework + leave.  
6. Teacher: my batches + (if permitted) mark attendance.  
7. BM: students list/detail + inquiries + attendance.  
8. Accountant: collect fee + receipt PDF.  
9. Files (photos, PDFs).  
10. Hide every remaining button behind `can()`.

Do not start with inventory, GST, or user-permission overrides. They are real, but they are week-3 screens.

---

## 16. Full endpoint index

Auth prefix for all rows: `{base}/api/v1`. All except login need Bearer.

### Auth & profile

| Method | Path |
|--------|------|
| POST | `/auth/login` |
| POST | `/auth/logout` |
| GET | `/auth/me` |
| POST | `/auth/change-password` |
| POST or PATCH | `/profile` |

### Home

| Method | Path |
|--------|------|
| GET | `/dashboard` |
| GET | `/dashboard/data/{widget}` |

### Campus & batches

| Method | Path |
|--------|------|
| GET | `/branch` |
| GET POST | `/batches` |
| GET PUT | `/batches/{id}` |
| POST | `/batches/{id}/status` |
| GET | `/batches/{id}/students` |
| GET | `/batches/{id}/attendance` |
| GET | `/batches/{id}/attendance-report` |
| GET | `/batches/{id}/exams` |
| GET | `/batches/{id}/homework` |
| GET | `/batches/{id}/notices` |

### People

| Method | Path |
|--------|------|
| GET POST | `/teachers` |
| GET PUT DELETE | `/teachers/{id}` |
| POST | `/teachers/{id}/status` |
| GET | `/teachers/{id}/batches` |
| GET | `/teachers/{id}/login-history` |
| GET | `/teachers/{id}/attendance` |
| GET | `/teachers/{id}/assigned-work` |
| GET | `/staff` `/staff/roles` |
| POST | `/staff` |
| GET PUT DELETE | `/staff/{id}` |
| POST | `/staff/{id}/status` |
| GET | `/staff/{id}/attendance` `/login-history` `/assigned-work` |
| GET | `/users?tab=` |
| GET POST | `/users/{id}/permissions` |

### Students & CRM

| Method | Path |
|--------|------|
| GET POST | `/students` |
| GET PUT | `/students/{id}` |
| POST | `/students/{id}/status` |
| GET | `/students/{id}/receipt` `/receipt-pdf` |
| GET POST | `/inquiries` |
| GET PUT DELETE | `/inquiries/{id}` |
| GET | `/inquiries/{id}/receipt` `/receipt-pdf` |
| GET POST | `/inquiries/{id}/convert` |
| GET | `/follow-ups` |
| POST | `/follow-ups/{inquiryId}` |

### Attendance

| Method | Path |
|--------|------|
| GET | `/attendance/student` |
| POST | `/attendance/student/{batchId}` |
| GET | `/attendance/student/reports/batch/{batchId}` |
| GET | `/attendance/student/reports/student/{studentId}` |
| GET POST | `/attendance/teacher` |
| GET | `/attendance/teacher/reports/teacher/{teacherId}` |
| GET | `/attendance/teacher/reports/staff/{staffId}` |

### Academics

| Method | Path |
|--------|------|
| GET POST | `/exams` |
| GET PUT DELETE | `/exams/{id}` |
| GET | `/exams/{id}/download` `/result-sheet` |
| GET POST | `/result-announcements` |
| GET DELETE | `/result-announcements/{id}` |
| GET | `/result-announcements/{id}/preview` `/export-pdf` |
| GET POST | `/homework` |
| GET PUT DELETE | `/homework/{id}` |
| GET | `/homework/{id}/download` |
| GET POST | `/study-materials` |
| GET PUT DELETE | `/study-materials/{id}` |
| GET | `/study-materials/{id}/download` |
| GET POST | `/subjects` `/standards` |
| PUT DELETE | `/subjects/{id}` `/standards/{id}` |
| POST | `/subjects/{id}/status` `/standards/{id}/status` |

### Communication & workflow

| Method | Path |
|--------|------|
| GET POST | `/notices` |
| GET PUT DELETE | `/notices/{id}` |
| POST | `/notices/{id}/status` |
| GET POST | `/holidays` |
| PUT DELETE | `/holidays/{id}` |
| GET POST | `/todos` |
| POST | `/todos/carry-forward` |
| PUT DELETE | `/todos/{id}` |
| POST | `/todos/{id}/approve` `/reject` |
| GET POST | `/leave-requests/staff` `/leave-requests/student` |
| POST | `/leave-requests/{id}/approve` `/reject` |

### Money

| Method | Path |
|--------|------|
| GET POST | `/fees` |
| GET | `/fees/student-summary` |
| GET | `/fees/{paymentId}/receipt` `/receipt-pdf` |
| DELETE | `/fees/{paymentId}` |
| GET | `/fees/follow-ups` `/fees/follow-ups/export-pdf` |
| POST | `/fees/follow-ups/{invoiceId}` |
| GET | `/gst-invoices` |
| POST | `/gst-invoices/generate/{paymentId}` |
| GET | `/gst-invoices/{id}` |
| GET POST | `/income-expense` |
| DELETE | `/income-expense/{id}` |
| GET POST | `/income-expense-categories` |
| PUT | `/income-expense-categories/{id}` |
| POST | `/income-expense-categories/{id}/status` |
| GET POST | `/inventory-assets` |
| PUT DELETE | `/inventory-assets/{id}` |
| GET POST | `/asset-categories` |
| PUT DELETE | `/asset-categories/{id}` |
| POST | `/asset-categories/{id}/status` |

### Exports (files)

| Method | Path |
|--------|------|
| GET | `/export/students` |
| GET | `/export/students/{id}/attendance` `/results` |
| GET | `/export/inquiries` `/export/follow-ups` `/export/todos` |
| GET | `/export/batches/{id}/students` `/attendance-report` |
| GET | `/export/teachers/{id}/attendance` `/homework` |
| GET | `/export/staff/{id}/attendance` |

---

## 17. Rate limits & extras

| Rule | Value |
|------|--------|
| Login | 5 attempts / minute / email+IP |
| Other APIs | 120 / minute |
| Pagination | `per_page` 1–50 |
| Soft-deleted rows | hidden, like the website |
| Plan-blocked module (e.g. inventory not in plan) | 403, not 404 |
| Token name | `mobile` — re-login replaces it |

---

## 18. You are done when…

A new developer can:

1. Log in as teacher, parent, and branch manager.  
2. Draw three different homes from `dashboard.kind`.  
3. Hide every button the API would 403.  
4. Mark attendance, add an inquiry, collect a fee, apply child leave — each with the matching permission.  
5. Never send an empty `{id}` in a URL.  
6. Open a receipt PDF with the Bearer token.

If those six work, the rest of the modules are the same pattern: **list → detail → create body → permission → 403/422 handling**.

Welcome to Class Flow. The website and the phone share one brain. Keep them that way.
