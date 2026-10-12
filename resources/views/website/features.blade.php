@extends('website.layouts.app')
@section('content')

<main>
    <section class="page-hero">
      <div class="atmosphere" aria-hidden="true"><div class="glow glow-1"></div><div class="glow glow-2"></div></div>
      <div class="container">
        <div class="page-hero-inner center">
          <p class="eyebrow reveal" data-reveal>Features</p>
          <h1 class="reveal" data-reveal data-delay="60">Web CRM &amp; Mobile App</h1>
          <p class="section-sub reveal" data-reveal data-delay="120">One platform. Two experiences — every module for office control and on-the-go ops, listed with details below.</p>
          <div class="page-hero-meta reveal" data-reveal data-delay="180">
            <span>Admissions CRM</span>
            <span>Fees &amp; attendance</span>
            <span>Parent · Teacher · Staff</span>
          </div>
          <div class="feature-jump reveal" data-reveal data-delay="240">
            <a href="#web-crm" class="btn btn-primary">Web CRM</a>
            <a href="#mobile-app" class="btn btn-ghost">Mobile App</a>
          </div>
        </div>
      </div>
    </section>

    <!-- WEB CRM -->
    <section class="section" id="web-crm">
      <div class="container">
        <div class="section-head center reveal" data-reveal>
          <p class="eyebrow">Web CRM</p>
          <h2 class="section-title">Office control that runs your institute</h2>
          <p class="section-sub">Powerful admin CRM for branches, admissions, fees &amp; academics.</p>
        </div>

        <div class="feature-cards">
          <article class="feature-card reveal" data-reveal>
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></div>
            <h3>Dashboard &amp; Control</h3>
            <p class="card-intro">Live visibility across your institute.</p>
            <ul class="check-list">
              <li>Live institute dashboard (branches, teachers, students, inquiries, batches)</li>
              <li>Today’s follow-ups, fees follow-ups, homework, leaves, admissions</li>
              <li>Branch-wise working context</li>
              <li>Login history for staff/teachers</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="60">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg></div>
            <h3>Master Setup</h3>
            <p class="card-intro">Configure institute structure once.</p>
            <ul class="check-list">
              <li>Multi-branch management</li>
              <li>Teachers master (assign branch &amp; batches)</li>
              <li>Subjects &amp; Standards</li>
              <li>Batches (timing, subjects, students, teacher assign)</li>
              <li>Batch detail hub (attendance, exams, homework, notices, reports, PDF)</li>
              <li>Income/Expense categories &amp; assets master</li>
            </ul>
          </article>

          <article class="feature-card feature-card-core reveal" data-reveal data-delay="120">
            <div class="badge-slot"><span class="core-badge">Core module</span></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg></div>
            <h3>CRM &amp; Admissions</h3>
            <p class="card-intro">Inquiry → follow-up → confirmed student.</p>
            <ul class="check-list">
              <li>Inquiry / lead capture</li>
              <li>Follow-up tracker (next date, status, history)</li>
              <li>Convert inquiry → confirmed student</li>
              <li>Form-fee / inquiry receipt</li>
              <li>Today’s admission visibility</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal>
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <h3>Student Management</h3>
            <p class="card-intro">Complete student records in one place.</p>
            <ul class="check-list">
              <li>Full student profiles</li>
              <li>Multi-batch assign with subjects</li>
              <li>Activate / deactivate</li>
              <li>Tabs: Attendance, Exams, Fees, Homework, Notices, GST, Results</li>
              <li>Sibling parent reuse</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="60">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></div>
            <h3>Fees &amp; Recovery</h3>
            <p class="card-intro">Collect, track balances, follow up dues.</p>
            <ul class="check-list">
              <li>Fee collection (mode, method, reference)</li>
              <li>Balance tracking (total / received / remaining)</li>
              <li>Fees Follow-up CRM</li>
              <li>Export Excel/PDF</li>
              <li>GST Invoice</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="120">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
            <h3>Academics</h3>
            <p class="card-intro">Homework, exams, notices, results.</p>
            <ul class="check-list">
              <li>Study material</li>
              <li>Homework</li>
              <li>Exam scheduling</li>
              <li>Notices</li>
              <li>Result announcement + PDF</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal>
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
            <h3>Attendance</h3>
            <p class="card-intro">Students, teachers &amp; staff — with reports.</p>
            <ul class="check-list">
              <li>Student attendance by batch/date/roll</li>
              <li>Teacher &amp; staff attendance</li>
              <li>Punch login/logout</li>
              <li>Monthly reports</li>
              <li>Auto-absent automation</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="60">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11h-6"/></svg></div>
            <h3>Staff &amp; HR</h3>
            <p class="card-intro">Roles, leave, holidays, profiles.</p>
            <ul class="check-list">
              <li>Staff CRUD + roles</li>
              <li>Leave apply / approve (staff + student)</li>
              <li>Holiday calendar</li>
              <li>Staff profile + PDFs</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="120">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 11l3 3L22 4"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg></div>
            <h3>Internal Tasks</h3>
            <p class="card-intro">Assign work and track status.</p>
            <ul class="check-list">
              <li>Assign / approve / reject / carry forward</li>
              <li>Task history</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal>
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <h3>Finance &amp; Assets</h3>
            <p class="card-intro">Money movement beyond fees.</p>
            <ul class="check-list">
              <li>Income &amp; Expense</li>
              <li>Inventory &amp; Assets</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="60">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <h3>Security &amp; Settings</h3>
            <p class="card-intro">Permissions and institute branding.</p>
            <ul class="check-list">
              <li>Role-based permissions</li>
              <li>Institute settings (logo, contacts, signature, stamp, terms)</li>
              <li>Secure admin login</li>
            </ul>
          </article>
        </div>
      </div>
    </section>

    <!-- MOBILE APP -->
    <section class="section section-alt" id="mobile-app">
      <div class="container">
        <div class="section-head center reveal" data-reveal>
          <p class="eyebrow">Mobile App</p>
          <h2 class="section-title">Classroom &amp; parent ops in your pocket</h2>
          <p class="section-sub">Same live data as Web CRM — for Parent, Teacher &amp; Staff.</p>
        </div>

        <div class="feature-cards">
          <article class="feature-card reveal" data-reveal>
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <h3>Common for all users</h3>
            <p class="card-intro">Shared foundations across every role.</p>
            <ul class="check-list">
              <li>Secure login</li>
              <li>Role-based screens</li>
              <li>Force logout if inactive</li>
              <li>Institute branding via app settings</li>
              <li>Holidays &amp; notices</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="60">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <h3>Parent App</h3>
            <p class="card-intro">Track every child’s progress from phone.</p>
            <ul class="check-list">
              <li>Linked children</li>
              <li>Child attendance history</li>
              <li>Apply leave for student</li>
              <li>Homework, study materials, exams, results</li>
              <li>Change password</li>
              <li>Fees visibility (online self-pay: Coming soon)</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="120">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11h-6"/></svg></div>
            <h3>Teacher App</h3>
            <p class="card-intro">Attendance to homework to fees entry.</p>
            <ul class="check-list">
              <li>Dashboard scoped to batches</li>
              <li>Punch In / Out</li>
              <li>Mark student attendance (daily + monthly)</li>
              <li>Own attendance, leaves, batches, login history</li>
              <li>Homework (with file upload)</li>
              <li>Study materials, exams, results, notices</li>
              <li>Leave apply/status</li>
              <li>Fee collection entry</li>
              <li>To-do / assigned work</li>
              <li>Profile update</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal>
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 11l3 3L22 4"/><rect x="3" y="3" width="18" height="18" rx="2"/></svg></div>
            <h3>Staff App</h3>
            <p class="card-intro">Tasks and recovery on the go.</p>
            <ul class="check-list">
              <li>Attendance punch</li>
              <li>Assigned to-do tasks</li>
              <li>Income/expense &amp; inventory (permission-based)</li>
              <li>Fees follow-ups</li>
              <li>Profile &amp; attendance</li>
            </ul>
          </article>

          <article class="feature-card reveal" data-reveal data-delay="60">
            <div class="badge-slot"></div>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></div>
            <h3>Admin / Branch on App</h3>
            <p class="card-intro">Key controls without opening a laptop.</p>
            <ul class="check-list">
              <li>Dashboard counts</li>
              <li>Masters, students, admissions, inquiries</li>
              <li>Attendance reports, GST, batch operations</li>
            </ul>
          </article>
        </div>

        <div class="highlight-strip reveal" data-reveal style="margin-top:40px">
          <p>Teachers mark attendance on the go</p>
          <p>Parents track child progress from phone</p>
          <p>Staff manage tasks &amp; fee follow-ups</p>
          <p>Real-time sync with Web CRM</p>
        </div>
      </div>
    </section>
  </main>
@endsection