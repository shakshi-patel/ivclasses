# Class Flow Mobile API — Manual runbook (one-by-one)

**Date:** 20 Oct 2026  
**Use this file to click every API yourself** (Postman / Thunder Client / curl).  
App-theory file is `docs/mobile-api-app-developer-guide.md`. This file is **only run order + paste-ready JSON**.

Postman collection: `docs/postman/Class-Flow-Mobile-API.postman_collection.json`

---

## 0. Setup (2 minutes)

### 0.1 Base URL

```
http://localhost/iv_classes/public/api/v1
```

Production: `https://YOUR-DOMAIN/api/v1`

### 0.2 Headers (every call except Login)

```
Accept: application/json
Authorization: Bearer PASTE_TOKEN_HERE
```

JSON create/update also:

```
Content-Type: application/json
```

File/photo: use form-data, **not** JSON content-type.

CSV/PDF: `Accept: text/csv` or just Bearer; response is a **file**, not `{success:true}`.

### 0.3 Postman (fastest)

1. Import the collection JSON.  
2. Import **one** environment (top-right). Environment **None** = broken `{{base_url}}`.  
3. Run **Auth → Login (saves token)** first.  
4. Do **not** run **Change password** or **Logout** in the middle.  
5. Empty id (`{{teacher_id}}` blank) → collection **skips**. Fill the id from a list first.

| Environment file | Email | Role | Best for |
|------------------|-------|------|----------|
| `Class-Flow-Mobile-API.postman_environment.json` | `kanku01@gmail.com` | branch_manager | **Full run** (widest) |
| `Class-Flow-Mobile-API.principal.postman_environment.json` | `principal@demo-institute.test` | principal | Same as BM |
| `Class-Flow-Mobile-API.teacher.postman_environment.json` | `teacher@demo-institute.test` | teacher | Own batches; many 403s are OK |
| `Class-Flow-Mobile-API.parent.postman_environment.json` | `test1@gmail.com` | parent | Child screens |
| `Class-Flow-Mobile-API.receptionist.postman_environment.json` | `receptionist@demo-institute.test` | receptionist | Inquiry + student add |
| `Class-Flow-Mobile-API.accountant.postman_environment.json` | `accountant@demo-institute.test` | accountant | Fees / GST / I&E |

Password on **fresh seed** is `password`. If login 422, that live user changed password.

`admin@demo-institute.test` = **web only** (mobile 403).

### 0.4 ID notepad (fill as you go)

Copy numbers from JSON `data`. Never leave a URL like `/teachers//batches`.

```
TOKEN=
USER_ID=
BRANCH_ID=
STANDARD_ID=
BATCH_ID=
TEACHER_ID=
TEACHER_USER_ID=          (users.id of that teacher — batch subjects)
STAFF_ID=
STAFF_ROLE_ID=
STUDENT_ID=
CREATED_BATCH_ID=
CREATED_STUDENT_ID=
INQUIRY_ID=
CREATED_INQUIRY_ID=
EXAM_ID=
HOMEWORK_ID=
NOTICE_ID=
HOLIDAY_ID=
TODO_ID=
PAYMENT_ID=
INVOICE_ID=
GST_INVOICE_ID=
IE_CATEGORY_ID=
IE_ENTRY_ID=
SUBJECT_ID=
ATTENDANCE_STUDENT_ID=
ATTENDANCE_USER_ID=
```

BM Postman env already has examples: `branch_id=5`, `standard_id=9`, `batch_id=5`. **Your institute may differ** — always take ids from list responses.

### 0.5 What 200 / 201 / 403 / 404 / 422 mean

| Code | Meaning | What you do |
|------|---------|-------------|
| 200 / 201 | Worked | Copy `data.id` |
| 401 | No/expired token | Login again |
| 403 | No permission / plan module off | Expected for that role. Continue |
| 404 | Missing **or** not in your campus/batch | Check the id |
| 422 | Body wrong | Read `errors` |

---

## 1. Auth  ·  Postman: Auth

### 1.1 Login  ·  `POST /auth/login`  ·  **no Bearer**

```json
{
  "email": "kanku01@gmail.com",
  "password": "password"
}
```

Copy: `data.token`, `data.user.id` → `USER_ID`, `data.user.branches[0].id` → `BRANCH_ID`, `data.user.batches[0].id` → `BATCH_ID` (teacher), `data.user.children[0].id` → `STUDENT_ID` (parent), `data.user.teacher.id` → `TEACHER_ID`, `data.user.staff.id` → `STAFF_ID`.

Put token in header for everything below.

### 1.2 Me  ·  `GET /auth/me`

No body. Same user object. Refresh permissions.

### 1.3 Profile update  ·  `POST /profile`  ·  Postman: Profile update

```json
{
  "name": "Kanku_managerr",
  "phone": "9824669969"
}
```

Photo: form-data field `avatar` (image, max 2MB).

### 1.4 Change password  ·  **SKIP while testing**

This overwrites the login password. Run last only if you mean it.

```json
{
  "current_password": "password",
  "password": "NewPass123!",
  "password_confirmation": "NewPass123!"
}
```

### 1.5 Logout  ·  `POST /auth/logout`  ·  **run last**

No body. After this, token is dead.

---

## 2. Dashboard  ·  Postman: Dashboard

### 2.1 Home  ·  `GET /dashboard`

Optional:

```
GET /dashboard?branch_id=BRANCH_ID
GET /dashboard?student_id=STUDENT_ID
```

`data.kind` = `institute` | `teacher` | `parent`.

### 2.2 Widget  ·  `GET /dashboard/data/fees_trend`

Only keys from `data.allowed_widgets`.

| Role | Try |
|------|-----|
| BM / accountant | `fees_trend` |
| Teacher | `attendance_trend` |
| Parent | `child_attendance` |

Wrong widget → 403.

---

## 3. Branch  ·  Postman: Branch & Batches → My branches

**There is no create/update/delete branch on mobile.** Branches come from web.

### 3.1 List my campuses  ·  `GET /branch`

Copy `data.items[0].id` or `data[0].id` → `BRANCH_ID`.

---

## 4. Standards & subjects  ·  Postman: Subjects & Standards

Need these ids **before** batch/student/exam create. Default BM often **403** (`subject.view` / `standard.view` missing). Then take `standard_id` from an existing batch instead.

### 4.1 List subjects  ·  `GET /subjects?per_page=15&status=active`

Copy `data.items[0].id` → `SUBJECT_ID`.

### 4.2 Create subject  ·  `POST /subjects`  ·  `subject.add`

```json
{
  "name": "QA Physics"
}
```

### 4.3 Update subject  ·  `PUT /subjects/SUBJECT_ID`  ·  `subject.edit`

```json
{
  "name": "QA Physics Updated"
}
```

### 4.4 Toggle subject  ·  `POST /subjects/SUBJECT_ID/status`

No body.

### 4.5 Delete subject  ·  `DELETE /subjects/SUBJECT_ID`

Soft delete. Same name create later **restores**.

### 4.6 List standards  ·  `GET /standards?per_page=15&status=active`

Copy → `STANDARD_ID`.

### 4.7 Create standard  ·  `POST /standards`

```json
{
  "name": "QA Class 10"
}
```

### 4.8 Update standard  ·  `PUT /standards/STANDARD_ID`

```json
{
  "name": "QA Class 10 Updated"
}
```

### 4.9 Toggle / delete  ·  `POST /standards/STANDARD_ID/status`  ·  `DELETE /standards/STANDARD_ID`

Delete **422** if batches/students/inquiries still use it.

---

## 5. Batches  ·  Postman: Branch & Batches

Run **list first**, then create a **QA row**, then update/toggle **that** id (`CREATED_BATCH_ID`). Do not toggle a live batch if you can avoid it.

### 5.1 List  ·  `GET /batches?per_page=15&status=active`

Optional: `&branch_id=BRANCH_ID&standard_id=STANDARD_ID`  
Copy `data.items[0].id` → `BATCH_ID`.

### 5.2 Detail  ·  `GET /batches/BATCH_ID`

### 5.3 Tabs (same batch)

```
GET /batches/BATCH_ID/students
GET /batches/BATCH_ID/attendance?date=2026-10-20
GET /batches/BATCH_ID/attendance-report?year=2026&month=10
GET /batches/BATCH_ID/exams
GET /batches/BATCH_ID/homework
GET /batches/BATCH_ID/notices
```

Exam/homework/attendance tabs need that **module** permission. Only `batch.view` → 403 on those tabs.

### 5.4 Create  ·  `POST /batches`  ·  Postman: Batch create (new QA row)  ·  `batch.add`

Replace numbers. `start_time` / `end_time` = `HH:mm`.

```json
{
  "name": "QA Batch 20261020",
  "branch_id": 5,
  "standard_id": 9,
  "status": "active",
  "academic_year": "2026-27",
  "capacity": 20,
  "start_time": "09:00",
  "end_time": "10:00",
  "subjects": [
    {
      "subject_id": 1,
      "teacher_user_id": 19,
      "fees_amount": 1500
    }
  ],
  "teacher_ids": [19]
}
```

`subjects` and `teacher_ids` optional. `teacher_user_id` / `teacher_ids` are **user ids**, not `teachers.id`.

Copy `data.id` → `CREATED_BATCH_ID`.

### 5.5 Update  ·  `PUT /batches/CREATED_BATCH_ID`  ·  `batch.edit`

```json
{
  "name": "QA Batch Updated 20261020",
  "branch_id": 5,
  "standard_id": 9,
  "status": "active",
  "academic_year": "2026-27",
  "capacity": 25,
  "start_time": "09:00",
  "end_time": "10:30"
}
```

### 5.6 Toggle status  ·  `POST /batches/CREATED_BATCH_ID/status`  ·  `batch.status`

No body. URL **must** include the id. `/batches/status` = 404.

---

## 6. Teachers  ·  Postman: Teachers

Teacher list 403 on **teacher login** is normal (`teacher.view` missing). Use BM.

`TEACHER_ID` = `data.items[0].id` (**teachers.id**, not `user.id`).

### 6.1 List  ·  `GET /teachers?per_page=15&status=active`

Optional `&q=anita`

### 6.2 Detail + tabs

```
GET /teachers/TEACHER_ID
GET /teachers/TEACHER_ID/batches
GET /teachers/TEACHER_ID/login-history
GET /teachers/TEACHER_ID/attendance?year=2026&month=10
GET /teachers/TEACHER_ID/assigned-work
```

### 6.3 Create  ·  `POST /teachers`  ·  `teacher.add`

`batch_ids` = **batch** ids. `primary_branch_id` required. Email must be unique.

```json
{
  "name": "QA Teacher",
  "email": "qa.teacher.20261020@example.test",
  "password": "Password123",
  "password_confirmation": "Password123",
  "phone": "9876543210",
  "status": "active",
  "primary_branch_id": 5,
  "batch_ids": [5],
  "branch_ids": [5],
  "specialization_ids": [1],
  "joining_date": "2026-06-01",
  "expected_login_time": "09:00",
  "grace_minutes": 10,
  "address": "QA address"
}
```

Photo: form-data `avatar`. Copy `data.id` → `TEACHER_ID`. Copy `data.user.id` → `TEACHER_USER_ID`.

### 6.4 Update  ·  `PUT /teachers/TEACHER_ID`  ·  `teacher.edit`

Password optional.

```json
{
  "name": "QA Teacher Updated",
  "email": "qa.teacher.updated@example.test",
  "phone": "9876543210",
  "status": "active",
  "primary_branch_id": 5,
  "batch_ids": [5],
  "branch_ids": [5]
}
```

### 6.5 Toggle  ·  `POST /teachers/TEACHER_ID/status`

No body.

### 6.6 Delete  ·  `DELETE /teachers/TEACHER_ID`  ·  `teacher.delete`

Skip on live teachers. Only delete the QA row you created.

---

## 7. Staff  ·  Postman: Staff

Cannot create **branch_manager** here (web branch create).

### 7.1 List  ·  `GET /staff?per_page=15`

Copy `data.items[0].id` → `STAFF_ID`.

### 7.2 Roles (needed before create)  ·  `GET /staff/roles`

Copy an `id` (e.g. receptionist/accountant/principal) → `STAFF_ROLE_ID`.

### 7.3 Detail + tabs

```
GET /staff/STAFF_ID
GET /staff/STAFF_ID/attendance?year=2026&month=10
GET /staff/STAFF_ID/login-history
GET /staff/STAFF_ID/assigned-work
```

### 7.4 Create  ·  `POST /staff`  ·  `staff.add` / `users.add_accountant` etc.

```json
{
  "name": "QA Staff",
  "email": "qa.staff.20261020@example.test",
  "password": "Password123",
  "password_confirmation": "Password123",
  "status": "active",
  "primary_branch_id": 5,
  "role_id": 12
}
```

Replace `role_id` with `STAFF_ROLE_ID`. Copy `data.id`.

### 7.5 Update  ·  `PUT /staff/STAFF_ID`

```json
{
  "name": "QA Staff Updated",
  "email": "qa.staff.updated@example.test",
  "status": "active",
  "primary_branch_id": 5,
  "role_id": 12
}
```

### 7.6 Toggle / delete  ·  `POST /staff/STAFF_ID/status`  ·  `DELETE /staff/STAFF_ID`

---

## 8. Users directory  ·  Postman: Users

### 8.1 Tabs

```
GET /users?tab=teacher&per_page=15
GET /users?tab=principal&per_page=15
GET /users?tab=branch_manager&per_page=15
GET /users?tab=receptionist&per_page=15
GET /users?tab=accountant&per_page=15
```

`tab` is required. Permission: `users.view_{tab}`.

### 8.2 Permission view  ·  `GET /users/USER_ID/permissions`  ·  `roles.assign_permissions`

Usually 403 for BM. Fine.

### 8.3 Permission save  ·  `POST /users/USER_ID/permissions`

```json
{
  "grant_ids": [],
  "deny_ids": []
}
```

Ids are Spatie **permission ids** from the GET above. Do not guess.

---

## 9. Students  ·  Postman: Students

### 9.1 List  ·  `GET /students?per_page=15&status=active`

Optional `&branch_id=&standard_id=&batch_id=&q=`  
Copy `data.items[0].id` → `STUDENT_ID` and `ATTENDANCE_STUDENT_ID`.

### 9.2 Detail  ·  `GET /students/STUDENT_ID`

### 9.3 Create  ·  `POST /students`  ·  `student.add`

`father_phone` = 10–15 digits. `enrollments` min 1. Unique `father_email`.

```json
{
  "name": "QA Student 20261020",
  "dob": "2014-06-15",
  "gender": "Male",
  "category": "General",
  "admission_date": "2026-10-20",
  "branch_id": 5,
  "standard_id": 9,
  "school_name": "QA School",
  "address": "QA address",
  "form_fees": 100,
  "form_fees_payment_method": "Cash",
  "father_name": "QA Father",
  "mother_name": "QA Mother",
  "father_email": "qa.father.20261020@test.local",
  "father_phone": "9000011111",
  "father_phone_2": "9000011112",
  "father_occupation": "Service",
  "status": "active",
  "enrollments": [
    {
      "batch_id": 5,
      "total_fees": 1000,
      "discount": 0,
      "roll_no": "99"
    }
  ]
}
```

Photo: form-data `photo`. Copy `data.id` → `CREATED_STUDENT_ID`.

Gender: `Male` | `Female` | `Other`. Category: `General` | `OBC` | `SC` | `ST`. Payment: `Cash` | `Online` | `UPI` | `Cheque`.

### 9.4 Update  ·  `PUT /students/CREATED_STUDENT_ID`  ·  `student.edit`

Use the **same father_email** you created (or the one from detail).

```json
{
  "name": "QA Student Updated",
  "dob": "2014-06-15",
  "gender": "Male",
  "category": "General",
  "admission_date": "2026-10-20",
  "branch_id": 5,
  "standard_id": 9,
  "father_name": "QA Father",
  "father_email": "qa.father.20261020@test.local",
  "father_phone": "9000011111",
  "status": "active",
  "enrollments": [
    {
      "batch_id": 5,
      "total_fees": 1200,
      "discount": 0
    }
  ]
}
```

### 9.5 Toggle  ·  `POST /students/CREATED_STUDENT_ID/status`  ·  `student.status`

### 9.6 Receipt  ·  `GET /students/STUDENT_ID/receipt`  ·  `GET /students/STUDENT_ID/receipt-pdf`

PDF is a file download.

---

## 10. Inquiries + follow-ups  ·  Postman: Inquiries

### 10.1 List  ·  `GET /inquiries?per_page=15`

Copy `data.items[0].id` → `INQUIRY_ID`.

### 10.2 Detail  ·  `GET /inquiries/INQUIRY_ID`

### 10.3 Create  ·  `POST /inquiries`  ·  `inquiry.add`

`follow_up_date` **on/after** `inquiry_date`.

```json
{
  "student_name": "QA Inquiry 20261020",
  "father_name": "QA Father",
  "mother_name": "QA Mother",
  "father_email": "qa.inquiry.20261020@test.local",
  "father_phone": "9000022222",
  "gender": "Male",
  "category": "General",
  "inquiry_date": "2026-10-20",
  "follow_up_date": "2026-10-25",
  "follow_up_remark": "Wants evening batch",
  "branch_id": 5,
  "standard_id": 9,
  "source": "walk-in",
  "school_name": "QA School",
  "form_fees": 0,
  "form_fees_payment_method": "Cash"
}
```

Copy `data.id` → `CREATED_INQUIRY_ID`.

### 10.4 Update  ·  `PUT /inquiries/CREATED_INQUIRY_ID`

Cannot edit after convert (422).

```json
{
  "student_name": "QA Inquiry Updated",
  "father_name": "QA Father",
  "father_email": "qa.inquiry.updated@test.local",
  "father_phone": "9000022222",
  "gender": "Male",
  "category": "General",
  "inquiry_date": "2026-10-20",
  "follow_up_date": "2026-10-26",
  "follow_up_remark": "Will visit Saturday",
  "branch_id": 5,
  "standard_id": 9,
  "source": "walk-in"
}
```

### 10.5 Follow-ups list  ·  `GET /follow-ups?per_page=15&status=pending`

### 10.6 Add follow-up  ·  `POST /follow-ups/CREATED_INQUIRY_ID`  ·  `follow_up.add`

```json
{
  "remark": "Called, callback Monday",
  "next_follow_up_at": "2026-10-28"
}
```

### 10.7 Receipt  ·  `GET /inquiries/INQUIRY_ID/receipt`  ·  `.../receipt-pdf`

### 10.8 Convert prefill  ·  `GET /inquiries/CREATED_INQUIRY_ID/convert`  ·  `inquiry.convert`

If `already_converted` true, stop. Copy a batch id from `data.batches`.

### 10.9 Convert save  ·  `POST /inquiries/CREATED_INQUIRY_ID/convert`

Not in the Postman folder as a POST body — API exists.

```json
{
  "branch_id": 5,
  "form_fees": 100,
  "form_fees_payment_method": "Cash",
  "parent_password": "Password123",
  "enrollments": [
    {
      "batch_id": 5,
      "total_fees": 1000,
      "discount": 0,
      "roll_no": "1"
    }
  ]
}
```

### 10.10 Delete  ·  `DELETE /inquiries/CREATED_INQUIRY_ID`  ·  `inquiry.delete`

Often 403. Converted inquiry may 422.

---

## 11. Attendance  ·  Postman: Attendance

Use a **real student in that batch**. Copy `student_id` from `/attendance/student` roster, not a random id.

Date example: `2026-10-20` (Postman env uses `2026-01-03`).

### 11.1 Student roster  ·  `GET /attendance/student?batch_id=BATCH_ID&date=2026-10-20`

Copy `data.students[0].id` or `.student_id` → `ATTENDANCE_STUDENT_ID`. Note `data.meta.is_marked`.

### 11.2 Mark students  ·  `POST /attendance/student/BATCH_ID`

Status: `present` | `absent` | `late`  
First save: `attendance.add`. Already marked: `attendance.edit`.

```json
{
  "attendance_date": "2026-10-20",
  "records": [
    { "student_id": 41, "status": "present" },
    { "student_id": 42, "status": "absent", "remark": "Sick" }
  ]
}
```

### 11.3 Batch month report  ·  `GET /attendance/student/reports/batch/BATCH_ID?year=2026&month=10`

### 11.4 One student calendar  ·  `GET /attendance/student/reports/student/STUDENT_ID?batch_id=BATCH_ID&year=2026&month=10`

### 11.5 Teacher/staff roster  ·  `GET /attendance/teacher?date=2026-10-20&branch_id=BRANCH_ID`

Copy `data.staff[0].user_id` → `ATTENDANCE_USER_ID`. Teacher row also has `profile_id` → can fill `TEACHER_ID`.

### 11.6 Mark staff  ·  `POST /attendance/teacher`

Uses **user_id**, not teacher_id. Status: `present` | `absent` | `late`.

```json
{
  "attendance_date": "2026-10-20",
  "branch_id": 5,
  "records": [
    { "user_id": 19, "status": "present" }
  ]
}
```

### 11.7 Month calendars

```
GET /attendance/teacher/reports/teacher/TEACHER_ID?year=2026&month=10
GET /attendance/teacher/reports/staff/STAFF_ID?year=2026&month=10
```

---

## 12. Homework  ·  Postman: Homework

### 12.1 List  ·  `GET /homework?per_page=15&batch_id=BATCH_ID`

Copy `data.items[0].id` → `HOMEWORK_ID`.

### 12.2 Detail / download  ·  `GET /homework/HOMEWORK_ID`  ·  `GET /homework/HOMEWORK_ID/download`

### 12.3 Create  ·  `POST /homework`  ·  `homework.add`

`type`: `text` | `file` | `file_text`. For `text` / `file_text`, `description` required.

```json
{
  "title": "QA Homework 20261020",
  "type": "text",
  "description": "Complete exercise 5.2",
  "batch_id": 5,
  "subject_id": 1,
  "homework_date": "2026-10-20",
  "due_date": "2026-10-22"
}
```

File type: multipart fields same + `attachment` file (max 10MB).

### 12.4 Update  ·  `PUT /homework/HOMEWORK_ID`  ·  `homework.edit`

Same JSON. Remove file: `"remove_attachment": true`.

### 12.5 Delete  ·  `DELETE /homework/HOMEWORK_ID`

---

## 13. Study materials  ·  Postman: Study Materials (list/detail/download)

Create is **multipart** (file required). Not a JSON-only Postman body.

### 13.1 List  ·  `GET /study-materials?per_page=15`

### 13.2 Create  ·  `POST /study-materials`  ·  form-data

| Field | Value |
|-------|--------|
| title | QA Material 20261020 |
| batch_id | 5 |
| subject_id | 1 |
| description | Chapter notes |
| file | (choose a pdf) |

### 13.3 Detail / download / update / delete

```
GET /study-materials/ID
GET /study-materials/ID/download
PUT /study-materials/ID     (multipart; file optional)
DELETE /study-materials/ID
```

---

## 14. Exams + results  ·  Postman: Exams, Result Announcements

Postman has **list/detail/download/result-sheet**. Create/update JSON below is still live API.

### 14.1 List  ·  `GET /exams?per_page=15&batch_id=BATCH_ID`

Copy → `EXAM_ID`.

### 14.2 Detail / download / result-sheet

```
GET /exams/EXAM_ID
GET /exams/EXAM_ID/download
GET /exams/EXAM_ID/result-sheet
```

Result-sheet needs `result_announcement.add` or `.edit`. Copy student ids + `total_marks`.

### 14.3 Create exam  ·  `POST /exams`  ·  `exam.add`

`total_marks` min **1**.

```json
{
  "batch_id": 5,
  "subject_id": 1,
  "title": "QA Chapter test",
  "exam_date": "2026-11-02",
  "start_time": "10:00",
  "end_time": "11:00",
  "total_marks": 50,
  "description": "Bring calculator"
}
```

Attachment: multipart `attachment`.

### 14.4 Update / delete  ·  `PUT /exams/EXAM_ID`  ·  `DELETE /exams/EXAM_ID`

Same JSON. `"remove_attachment": true` to drop file.

### 14.5 Results list / detail / preview / pdf

```
GET /result-announcements?per_page=15
GET /result-announcements/ID
GET /result-announcements/ID/preview
GET /result-announcements/ID/export-pdf
```

### 14.6 Save / publish marks  ·  `POST /result-announcements`

Marks cannot exceed that exam’s `total_marks`. If result already exists, need `result_announcement.edit`.

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

`attendance_status`: `present` | `absent`.

### 14.7 Delete result  ·  `DELETE /result-announcements/ID`

---

## 15. Notices  ·  Postman: Notices

### 15.1 List / detail  ·  `GET /notices?per_page=15`  ·  `GET /notices/NOTICE_ID`

### 15.2 Create  ·  `POST /notices`  ·  `notice.add`

`audience`: `all` | `parents` | `students` | `teachers`

```json
{
  "title": "QA Notice 20261020",
  "body": "Centre closed for QA test",
  "notice_date": "2026-10-20",
  "audience": "all",
  "branch_ids": [5],
  "batch_ids": [5],
  "status": "active"
}
```

Image: multipart `image` (jpg/png/webp, max 5MB).

### 15.3 Update  ·  `PUT /notices/NOTICE_ID`

Same JSON. `"remove_image": true` optional.

### 15.4 Toggle / delete  ·  `POST /notices/NOTICE_ID/status`  ·  `DELETE /notices/NOTICE_ID`

---

## 16. Holidays  ·  Postman: Holidays

### 16.1 List  ·  `GET /holidays?per_page=15`

Optional `&branch_id=BRANCH_ID`

### 16.2 Create  ·  `POST /holidays`

```json
{
  "title": "QA Holiday",
  "start_date": "2026-12-24",
  "end_date": "2026-12-26",
  "reason": "Postman QA",
  "branch_ids": [5],
  "batch_ids": []
}
```

Copy → `CREATED_HOLIDAY_ID` / `HOLIDAY_ID`.

### 16.3 Update  ·  `PUT /holidays/HOLIDAY_ID`

```json
{
  "title": "QA Holiday Updated",
  "start_date": "2026-12-24",
  "end_date": "2026-12-25",
  "reason": "Postman QA updated",
  "branch_ids": [5],
  "batch_ids": []
}
```

### 16.4 Delete  ·  `DELETE /holidays/HOLIDAY_ID`

---

## 17. To-dos  ·  Postman: To-Dos

`assigned_to_user_ids` = **user ids** (from login `USER_ID`), not teacher/staff profile ids.

### 17.1 List  ·  `GET /todos?per_page=15`

### 17.2 Create  ·  `POST /todos`  ·  `todo.add`

```json
{
  "assigned_to_user_ids": [18],
  "due_date": "2026-10-25",
  "tasks": [
    { "title": "Call pending inquiries" }
  ]
}
```

Copy `data.id` → `TODO_ID`.

### 17.3 Update  ·  `PUT /todos/TODO_ID`  ·  `todo.edit`

Same shape.

### 17.4 Approve / reject / delete / carry-forward

```
POST /todos/TODO_ID/approve
POST /todos/TODO_ID/reject
DELETE /todos/TODO_ID
POST /todos/carry-forward
```

Reject may take `{ "remark": "Not needed" }` depending on service — if 422, send empty body first.

---

## 18. Leave  ·  Postman: Leave (lists only)

Apply/approve exist on API. Default BM/teacher may 403 apply/approve.

### 18.1 Lists

```
GET /leave-requests/staff?per_page=15
GET /leave-requests/student?per_page=15
```

### 18.2 Staff apply  ·  `POST /leave-requests/staff`  ·  `staff_leave.add`

```json
{
  "from_date": "2026-11-10",
  "to_date": "2026-11-12",
  "reason": "Family function"
}
```

### 18.3 Student/parent apply  ·  `POST /leave-requests/student`  ·  `student_leave.add`

```json
{
  "student_id": 41,
  "from_date": "2026-11-10",
  "to_date": "2026-11-10",
  "reason": "Fever"
}
```

### 18.4 Approve / reject

```
POST /leave-requests/LEAVE_ID/approve
POST /leave-requests/LEAVE_ID/reject
```

Reject body:

```json
{
  "review_note": "Too many leaves this month"
}
```

---

## 19. Fees  ·  Postman: Fees  ·  login as **accountant** for collect

BM can **view** fees; collect needs `fees.add` (accountant default).

### 19.1 Payments list  ·  `GET /fees?per_page=15`

Copy `data.items[0].id` → `PAYMENT_ID`. Copy student id if present.

### 19.2 Student summary  ·  `GET /fees/student-summary?student_id=STUDENT_ID`

### 19.3 Collect  ·  `POST /fees`  ·  `fees.add`

`amount` min `0.01`. Use a student who has due on that batch.

```json
{
  "student_id": 41,
  "batch_id": 5,
  "payment_date": "2026-10-20",
  "amount": 1,
  "payment_method": "Cash",
  "transaction_ref": "QA-001",
  "note": "Postman QA collect"
}
```

Copy `data.id` → `PAYMENT_ID`.

### 19.4 Receipt  ·  `GET /fees/PAYMENT_ID/receipt`  ·  `GET /fees/PAYMENT_ID/receipt-pdf`

### 19.5 Delete payment  ·  `DELETE /fees/PAYMENT_ID`  ·  `fees.delete`

Default accountant **403**. Expected.

### 19.6 Fee follow-ups list  ·  `GET /fees/follow-ups?per_page=15`

Copy due invoice `data.items[0].id` → `INVOICE_ID`.

### 19.7 Log follow-up  ·  `POST /fees/follow-ups/INVOICE_ID`

```json
{
  "remark": "Called parent, will pay Friday",
  "next_follow_up_at": null
}
```

### 19.8 Export PDF  ·  `GET /fees/follow-ups/export-pdf`  ·  `fees_follow_up.export`

Default accountant **403**.

---

## 20. GST  ·  Postman: GST invoices  ·  accountant

### 20.1 List  ·  `GET /gst-invoices?per_page=15`

### 20.2 Generate from payment  ·  `POST /gst-invoices/generate/PAYMENT_ID`  ·  `gst_invoice.add`

No body.

### 20.3 Show  ·  `GET /gst-invoices/GST_INVOICE_ID`

---

## 21. Income & expense  ·  Postman: Income & Expense  ·  accountant

### 21.1 Categories list  ·  `GET /income-expense-categories?per_page=15`

Optional `?type=expense` or `?type=income`. Copy `data.items[0].id` → `IE_CATEGORY_ID`.

### 21.2 Create category  ·  `POST /income-expense-categories`

```json
{
  "name": "QA Expense 20261020",
  "type": "expense",
  "status": "active"
}
```

`type`: `income` | `expense`.

### 21.3 Update category  ·  `PUT /income-expense-categories/IE_CATEGORY_ID`

```json
{
  "name": "QA Expense updated",
  "type": "expense",
  "status": "active"
}
```

### 21.4 Toggle category  ·  `POST /income-expense-categories/IE_CATEGORY_ID/status`

### 21.5 Entries list  ·  `GET /income-expense?per_page=15`

### 21.6 Create entry  ·  `POST /income-expense`  ·  `income_expense.add`

```json
{
  "category_id": 2,
  "title": "QA electricity",
  "amount": 1,
  "entry_date": "2026-10-20",
  "payment_method": "Cash",
  "branch_id": 5,
  "note": "Postman QA"
}
```

Copy `data.id` → `IE_ENTRY_ID`.

### 21.7 Delete entry  ·  `DELETE /income-expense/IE_ENTRY_ID`  ·  `income_expense.delete`

Default accountant **403**.

---

## 22. Inventory  ·  Postman: Inventory (lists)

Plan without inventory → 403 even if you grant the permission. Default accountant has **no** `inventory.*`.

Status values: `working` | `repair` | `damaged`. Purchase date **not future**.

### 22.1 Lists

```
GET /inventory-assets?per_page=15
GET /asset-categories?per_page=15
```

### 22.2 Create category  ·  `POST /asset-categories`  ·  `assets_category.add`

```json
{
  "name": "QA Electronics"
}
```

### 22.3 Update / toggle / delete category

```
PUT /asset-categories/ID     { "name": "QA Electronics Updated" }
POST /asset-categories/ID/status
DELETE /asset-categories/ID
```

### 22.4 Create asset  ·  `POST /inventory-assets`  ·  `inventory.add`

```json
{
  "category_id": 1,
  "name": "QA Projector",
  "quantity": 1,
  "purchase_date": "2026-10-01",
  "purchase_amount": 25000,
  "status": "working",
  "branch_id": 5,
  "note": "QA"
}
```

### 22.5 Update / delete asset  ·  `PUT /inventory-assets/ID`  ·  `DELETE /inventory-assets/ID`

Same JSON as create.

---

## 23. Exports  ·  Postman: Exports  ·  CSV files

All `GET`, header `Accept: text/csv` + Bearer.

```
GET /export/students
GET /export/students/STUDENT_ID/attendance?batch_id=BATCH_ID&year=2026&month=10
GET /export/students/STUDENT_ID/results
GET /export/inquiries
GET /export/follow-ups
GET /export/todos
GET /export/batches/BATCH_ID/students
GET /export/batches/BATCH_ID/attendance-report?year=2026&month=10
GET /export/teachers/TEACHER_ID/attendance?year=2026&month=10
GET /export/teachers/TEACHER_ID/homework
GET /export/staff/STAFF_ID/attendance?year=2026&month=10
```

Empty `STUDENT_ID` / `TEACHER_ID` → skip. Need matching export/view permission.

---

## 24. Suggested full-day order (BM token)

Do in this order so each step feeds the next id.

1. Login → token, `BRANCH_ID`, `USER_ID`  
2. `GET /branch`  
3. `GET /batches` → `BATCH_ID`, `STANDARD_ID` from item  
4. `GET /teachers` → `TEACHER_ID`  
5. Create QA batch → `CREATED_BATCH_ID` → update → toggle  
6. Create QA teacher (use `BATCH_ID`) → tabs → toggle (optional)  
7. `GET /staff/roles` → create QA staff (optional)  
8. Create QA student on `BATCH_ID` → `CREATED_STUDENT_ID`  
9. Create QA inquiry → follow-up  
10. Student attendance roster + mark on `BATCH_ID`  
11. Staff attendance roster + mark  
12. Homework create  
13. Notice create  
14. Holiday create  
15. Todo create assigned to `USER_ID`  
16. Exports that have ids  
17. **Switch environment to accountant** → login again → collect ₹1 fee → GST generate → I&E category + entry  
18. Logout  

**Do not** run Change password in this loop.

---

## 25. Role-wise: what should 403

If you get 200 everywhere on teacher login, something is wrong.

| Call | Teacher default | Parent | Receptionist | Accountant | BM |
|------|-----------------|--------|--------------|------------|-----|
| `GET /teachers` | 403 | 403 | 403 | 403 | 200 |
| `GET /batches` | 200 (own) | 403 or empty | 200 view | 403 | 200 |
| `POST /fees` | 403 | 403 | 403 | **201** | 403 |
| `POST /inquiries` | 403 | 403 | **201** | 403 | **201** |
| `GET /students` | 403 unless granted | own children | 200 | 200 view | 200 |
| `GET /inventory-assets` | 403 | 403 | 403 | 403 unless granted **and** plan | 403 unless granted |
| `POST /leave-requests/student` | 403 | **201** if `student_leave.add` | 403 | 403 | 403 unless granted |

---

## 26. Curl cheat (optional)

After login, PowerShell:

```powershell
$base = "http://localhost/iv_classes/public/api/v1"
$token = "PASTE"

# GET
curl.exe -s "$base/batches?per_page=15&status=active" -H "Accept: application/json" -H "Authorization: Bearer $token"

# POST JSON
curl.exe -s -X POST "$base/batches" -H "Accept: application/json" -H "Authorization: Bearer $token" -H "Content-Type: application/json" --data "{\"name\":\"QA Batch\",\"branch_id\":5,\"standard_id\":9,\"status\":\"active\",\"academic_year\":\"2026-27\",\"capacity\":20,\"start_time\":\"09:00\",\"end_time\":\"10:00\"}"
```

Replace ids. Never send `"branch_id": ""`.

---

Tick as you finish. One module per sitting is enough. If a create 422s, the `errors` object names the field — fix that field, don’t skip the whole module.
