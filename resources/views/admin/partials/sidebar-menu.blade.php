<ul class="menu-inner py-1">
    @can('dashboard.view')
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div class="text-truncate" data-i18n="Dashboards">Dashboards</div>
            </a>
        </li>
    @endcan
    @canany(['batch.view', 'subject.view', 'standard.view', 'income_expense.view', 'inventory.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Master</span>
        </li>
        <li
            class="menu-item {{ request()->routeIs('admin.master.*') && !request()->routeIs('admin.master.roles', 'admin.master.permissions', 'admin.master.branch*', 'admin.master.teacher*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-layer"></i>
                <div class="text-truncate" data-i18n="Master">Master</div>
            </a>
            <ul class="menu-sub">
            @can('subject.view')
                    <li class="menu-item {{ request()->routeIs('admin.master.subject') ? 'active' : '' }}">
                        <a href="{{ route('admin.master.subject') }}" class="menu-link">
                            <div class="text-truncate">Subject</div>
                        </a>
                    </li>
                @endcan
                @can('standard.view')
                    <li class="menu-item {{ request()->routeIs('admin.master.standard') ? 'active' : '' }}">
                        <a href="{{ route('admin.master.standard') }}" class="menu-link">
                            <div class="text-truncate">Standard</div>
                        </a>
                    </li>
                @endcan
                @can('batch.view')
                    <li class="menu-item {{ request()->routeIs('admin.master.batch*') ? 'active' : '' }}">
                        <a href="{{ route('admin.master.batch') }}" class="menu-link">
                            <div class="text-truncate">Batches</div>
                        </a>
                    </li>
                @endcan
               
                @can('income_expense.view')
                    <li
                        class="menu-item {{ request()->routeIs('admin.master.income_expense') ? 'active' : '' }}">
                        <a href="{{ route('admin.master.income_expense') }}" class="menu-link">
                            <div class="text-truncate">Income &amp; Expense</div>
                        </a>
                    </li>
                @endcan
                @can('inventory.view')
                    <li
                        class="menu-item {{ request()->routeIs('admin.master.assets_category') ? 'active' : '' }}">
                        <a href="{{ route('admin.master.assets_category') }}" class="menu-link">
                            <div class="text-truncate">Assets Category</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcanany
    @canany(['branch.view', 'teacher.view', 'staff.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Member</span>
        </li>
        <li
            class="menu-item {{ request()->routeIs('admin.master.branch*', 'admin.users', 'admin.master.teacher*', 'admin.staff', 'admin.staff.add', 'admin.staff.show', 'admin.staff.edit') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-group"></i>
                <div class="text-truncate" data-i18n="Member">Member</div>
            </a>
            <ul class="menu-sub">
                @can('branch.view')
                    <li class="menu-item {{ request()->routeIs('admin.master.branch*') ? 'active' : '' }}">
                        <a href="{{ route('admin.master.branch') }}" class="menu-link">
                            <div class="text-truncate">Branch</div>
                        </a>
                    </li>
                @endcan
                @canany(['teacher.view', 'staff.view'])
                    <li class="menu-item {{ request()->routeIs('admin.users', 'admin.master.teacher*', 'admin.staff', 'admin.staff.add', 'admin.staff.show', 'admin.staff.edit') ? 'active' : '' }}">
                        <a href="{{ route('admin.users') }}" class="menu-link">
                            <div class="text-truncate">Users</div>
                        </a>
                    </li>
                @endcanany
            </ul>
        </li>
    @endcanany

    

    

    @canany(['inquiry.view', 'follow_up.view', 'student.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Student</span>
        </li>
        @can('inquiry.view')
            <li class="menu-item {{ request()->routeIs('admin.inquiry*') ? 'active' : '' }}">
                <a href="{{ route('admin.inquiry') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user-pin"></i>
                    <div class="text-truncate" data-i18n="Inquiry">Inquiry</div>
                </a>
            </li>
        @endcan
        @can('follow_up.view')
            <li class="menu-item {{ request()->routeIs('admin.follow_up') ? 'active' : '' }}">
                <a href="{{ route('admin.follow_up') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                    <div class="text-truncate" data-i18n="Follow Up">Follow Up</div>
                </a>
            </li>
        @endcan
        @can('student.view')
            <li
                class="menu-item {{ request()->routeIs('admin.student*') && !request()->routeIs('admin.fees*') && !request()->routeIs('admin.student_leave_request') ? 'active' : '' }}">
                <a href="{{ route('admin.student') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-user"></i>
                    <div class="text-truncate" data-i18n="Students">Students</div>
                </a>
            </li>
        @endcan
    @endcanany

    @canany(['fees.view', 'fees_follow_up.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Fee</span>
        </li>
        @can('fees.view')
            <li
                class="menu-item {{ request()->routeIs('admin.fees') || request()->routeIs('admin.fees.add') ? 'active' : '' }}">
                <a href="{{ route('admin.fees') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-rupee"></i>
                    <div class="text-truncate" data-i18n="Fees">Fees</div>
                </a>
            </li>
        @endcan
        @can('fees_follow_up.view')
            <li class="menu-item {{ request()->routeIs('admin.fees.follow_up') ? 'active' : '' }}">
                <a href="{{ route('admin.fees.follow_up') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-phone-call"></i>
                    <div class="text-truncate" data-i18n="Fees Follow Up">Fees Follow Up</div>
                </a>
            </li>
        @endcan
    @endcanany

    @canany(['attendance.view', 'teacher_attendance.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Attendance</span>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.attendance.*') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-calendar-check"></i>
                <div class="text-truncate" data-i18n="Attendance">Attendance</div>
            </a>
            <ul class="menu-sub">
                @can('attendance.view')
                    <li class="menu-item {{ request()->routeIs('admin.attendance.student') ? 'active' : '' }}">
                        <a href="{{ route('admin.attendance.student') }}" class="menu-link">
                            <div class="text-truncate">Student Attendance</div>
                        </a>
                    </li>
                @endcan
                @can('teacher_attendance.view')
                    <li class="menu-item {{ request()->routeIs('admin.attendance.teacher') ? 'active' : '' }}">
                        <a href="{{ route('admin.attendance.teacher') }}" class="menu-link">
                            <div class="text-truncate">Teacher Attendance</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcanany

    @canany(['exam.view', 'result_announcement.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Exam</span>
        </li>
        @can('exam.view')
            <li class="menu-item {{ request()->routeIs('admin.exam*') ? 'active' : '' }}">
                <a href="{{ route('admin.exam') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-notepad"></i>
                    <div class="text-truncate" data-i18n="Exam">Exam</div>
                </a>
            </li>
        @endcan
        @can('result_announcement.view')
            <li class="menu-item {{ request()->routeIs('admin.result_announcement*') ? 'active' : '' }}">
                <a href="{{ route('admin.result_announcement') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-trophy"></i>
                    <div class="text-truncate" data-i18n="Result Announcement">Result Announcement</div>
                </a>
            </li>
        @endcan
    @endcanany

    @canany(['student_leave.view', 'student_leave.add', 'staff_leave.view', 'staff_leave.add'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Leave</span>
        </li>
        @canany(['student_leave.view', 'student_leave.add'])
            <li class="menu-item {{ request()->routeIs('admin.student_leave_request') ? 'active' : '' }}">
                <a href="{{ route('admin.student_leave_request') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar-x"></i>
                    <div class="text-truncate" data-i18n="Student Leave Request">Student Leave Request</div>
                </a>
            </li>
        @endcanany
        @canany(['staff_leave.view', 'staff_leave.add'])
            <li class="menu-item {{ request()->routeIs('admin.staff_leave_request') ? 'active' : '' }}">
                <a href="{{ route('admin.staff_leave_request') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar-minus"></i>
                    <div class="text-truncate" data-i18n="Staff Leave Request">Staff Leave Request</div>
                </a>
            </li>
        @endcanany
    @endcanany

    @can('income_expense.view')
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Income &amp; Expense</span>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.income_expense') ? 'active' : '' }}">
            <a href="{{ route('admin.income_expense') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-wallet"></i>
                <div class="text-truncate" data-i18n="Income & Expense">Income &amp; Expense</div>
            </a>
        </li>
    @endcan

    @can('inventory.view')
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Inventory &amp; Assets</span>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.inventory_assets*') ? 'active' : '' }}">
            <a href="{{ route('admin.inventory_assets') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-box"></i>
                <div class="text-truncate" data-i18n="Inventory & Assets">Inventory &amp; Assets</div>
            </a>
        </li>
    @endcan

    @canany(['study_material.view', 'homework.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Student Study</span>
        </li>
        @can('study_material.view')
            <li class="menu-item {{ request()->routeIs('admin.study_material*') ? 'active' : '' }}">
                <a href="{{ route('admin.study_material') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-book-content"></i>
                    <div class="text-truncate" data-i18n="Study Material">Study Material</div>
                </a>
            </li>
        @endcan
        @can('homework.view')
            <li class="menu-item {{ request()->routeIs('admin.homework*') ? 'active' : '' }}">
                <a href="{{ route('admin.homework') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-edit"></i>
                    <div class="text-truncate" data-i18n="Homework">Homework</div>
                </a>
            </li>
        @endcan
    @endcanany

    @canany(['notice.view', 'holiday.view', 'todo.view', 'todo.add'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Notice &amp; Holiday &amp; To Do Task</span>
        </li>
        @can('notice.view')
            <li class="menu-item {{ request()->routeIs('admin.notice*') ? 'active' : '' }}">
                <a href="{{ route('admin.notice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bell"></i>
                    <div class="text-truncate" data-i18n="Notice">Notice</div>
                </a>
            </li>
        @endcan
        @can('holiday.view')
            <li class="menu-item {{ request()->routeIs('admin.holiday') ? 'active' : '' }}">
                <a href="{{ route('admin.holiday') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar-star"></i>
                    <div class="text-truncate" data-i18n="Holiday">Holiday</div>
                </a>
            </li>
        @endcan
        @canany(['todo.view', 'todo.add'])
            <li class="menu-item {{ request()->routeIs('admin.to_do_task*') ? 'active' : '' }}">
                <a href="{{ route('admin.to_do_task') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-task"></i>
                    <div class="text-truncate" data-i18n="To Do Task">To Do Task</div>
                </a>
            </li>
        @endcanany
    @endcanany

    @canany(['institutes.view', 'plans.view', 'project_inquiry.view', 'demo_calendar.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text" data-i18n="INQUIRY">INQUIRY</span>
        </li>
        @can('institutes.view')
            <li class="menu-item {{ request()->routeIs('admin.institutes.*') ? 'active' : '' }}">
                <a href="{{ route('admin.institutes.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-buildings"></i>
                    <div class="text-truncate">Institutes</div>
                </a>
            </li>
        @endcan
        @can('plans.view')
            <li class="menu-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                <a href="{{ route('admin.plans.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-purchase-tag-alt"></i>
                    <div class="text-truncate">Plans</div>
                </a>
            </li>
        @endcan
        @if (auth()->user()?->isPlatformUser())
            <li class="menu-item {{ request()->routeIs('admin.module_help.*') ? 'active' : '' }}">
                <a href="{{ route('admin.module_help.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-help-circle"></i>
                    <div class="text-truncate">Module Help</div>
                </a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.email_templates.*') ? 'active' : '' }}">
                <a href="{{ route('admin.email_templates.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-envelope"></i>
                    <div class="text-truncate">Email Templates</div>
                </a>
            </li>
        @endif
        @can('project_inquiry.view')
            <li class="menu-item {{ request()->routeIs('admin.project-inquiries.*') ? 'active' : '' }}">
                <a href="{{ route('admin.project-inquiries.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-message-detail"></i>
                    <div class="text-truncate" data-i18n="Project Inquiry">Project Inquiry</div>
                </a>
            </li>
        @endcan
        @can('demo_calendar.view')
            <li class="menu-item {{ request()->routeIs('admin.demo-calendar.*') ? 'active' : '' }}">
                <a href="{{ route('admin.demo-calendar.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar-event"></i>
                    <div class="text-truncate" data-i18n="Demo Calendar">Demo Calendar</div>
                </a>
            </li>
        @endcan
    @endcanany

    @can('billing.view')
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Billing</span>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.billing.*') ? 'active' : '' }}">
            <a href="{{ route('admin.billing.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-credit-card"></i>
                <div class="text-truncate" data-i18n="Billing">Billing</div>
            </a>
        </li>
    @endcan

    @canany(['classes.view', 'settings.view', 'demo_availability.view', 'gst_invoice.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">SETTINGS</span>
        </li>
        @can('classes.view')
            @if (! auth()->user()?->isPlatformUser())
                <li class="menu-item {{ request()->routeIs('admin.classes*') ? 'active' : '' }}">
                    <a href="{{ route('admin.classes') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-buildings"></i>
                        <div class="text-truncate" data-i18n="Institute Setting">Institute Setting</div>
                    </a>
                </li>
            @endif
        @endcan
        @can('settings.view')
            @if (auth()->user()?->isPlatformUser())
                <li
                    class="menu-item {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.google-calendar.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.settings.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-cog"></i>
                        <div class="text-truncate" data-i18n="Settings">Settings</div>
                    </a>
                </li>
            @endif
        @endcan
        @can('demo_availability.view')
            <li class="menu-item {{ request()->routeIs('admin.demo-availability.*') ? 'active' : '' }}">
                <a href="{{ route('admin.demo-availability.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-time-five"></i>
                    <div class="text-truncate" data-i18n="Demo Availability">Demo Availability</div>
                </a>
            </li>
        @endcan
        @can('gst_invoice.view')
            <li class="menu-item {{ request()->routeIs('admin.gst_invoice') ? 'active' : '' }}">
                <a href="{{ route('admin.gst_invoice') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-receipt"></i>
                    <div class="text-truncate" data-i18n="GST Invoice">GST Invoice</div>
                </a>
            </li>
        @endcan
    @endcanany
    @canany(['roles.view', 'permissions.view'])
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Role Permission</span>
        </li>
        <li
            class="menu-item {{ request()->routeIs('admin.master.roles', 'admin.master.permissions') ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-shield-quarter"></i>
                <div class="text-truncate" data-i18n="Role & Permissions">Role &amp; Permissions</div>
            </a>
            <ul class="menu-sub">
                @can('roles.view')
                    <li class="menu-item {{ request()->routeIs('admin.master.roles') ? 'active' : '' }}">
                        <a href="{{ route('admin.master.roles') }}" class="menu-link">
                            <div class="text-truncate">Roles</div>
                        </a>
                    </li>
                @endcan
                @can('permissions.view')
                    <li class="menu-item {{ request()->routeIs('admin.master.permissions') ? 'active' : '' }}">
                        <a href="{{ route('admin.master.permissions') }}" class="menu-link">
                            <div class="text-truncate">Permissions</div>
                        </a>
                    </li>
                @endcan
            </ul>
        </li>
    @endcanany
</ul>
