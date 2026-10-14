<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default CRUD actions
    |--------------------------------------------------------------------------
    */
    'default_actions' => [
        'view' => 'View',
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles that ALWAYS receive every active permission on sync
    |--------------------------------------------------------------------------
    */
    'auto_assign_roles' => [
        'developer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules — enable as you wire each module
    |--------------------------------------------------------------------------
    |
    | Optional module keys:
    |  - plan_module:  gate this module under another module's plan entitlement
    |                  (it is not listed as a module of its own in plans / help).
    |  - inherit_from: newly created actions are granted to existing custom roles
    |                  that already hold this permission (see PermissionSyncService).
    */
    'modules' => [

        'dashboard' => [
            'name' => 'Dashboard',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Dashboard',
            ],
        ],

        // Sections of the institute dashboard (admin, branch manager, principal, accountant, receptionist, staff).
        'institute_dashboard' => [
            'name' => 'Institute Dashboard',
            'enabled' => true,
            'use_defaults' => false,
            'plan_module' => 'dashboard',
            'inherit_from' => 'dashboard.view',
            'actions' => [
                'quick_actions' => 'Header: Quick Actions',
                'pulse' => 'Header: Branches / Batches / Teachers',
                'kpi_students' => 'Card: Active Students',
                'kpi_fees' => 'Card: Fees Collected',
                'kpi_dues' => 'Card: Pending Dues',
                'kpi_attendance' => 'Card: Student Attendance',
                'kpi_staff_attendance' => 'Card: Staff Attendance',
                'kpi_inquiries' => 'Card: Inquiries This Month',
                'kpi_front_desk' => 'Card: New Leads Today',
                'kpi_finance' => 'Card: Net This Month',
                'attention' => 'Panel: Needs Attention',
                'chart_fees_trend' => 'Chart: Fee Collection',
                'chart_attendance_trend' => 'Chart: Attendance Trend',
                'chart_batch_attendance' => 'Chart: Batch-wise Attendance',
                'chart_admissions' => 'Chart: Admissions',
                'chart_lead_sources' => 'Chart: Lead Sources',
                'chart_standards' => 'Chart: Students by Standard',
                'chart_branches' => 'Chart: Branch Comparison',
                'chart_payment_methods' => 'Chart: Payment Methods',
                'list_payments' => 'List: Recent Payments',
                'list_dues' => 'List: Highest Dues',
                'list_fee_follow_ups' => 'List: Fee Follow-ups',
                'list_batches_today' => 'List: Batches Today',
                'list_staff_today' => 'List: Staff Today',
                'list_follow_ups' => "List: Today's Call List",
                'list_inquiries' => 'List: Recent Inquiries',
                'list_exams' => 'List: Upcoming Exams',
                'list_results' => 'List: Recent Results',
                'list_notices' => 'List: Notice Board',
                'list_holidays' => 'List: Holidays & Birthdays',
            ],
        ],

        'teacher_dashboard' => [
            'name' => 'Teacher Dashboard',
            'enabled' => true,
            'use_defaults' => false,
            'plan_module' => 'dashboard',
            'inherit_from' => 'dashboard.view',
            'actions' => [
                'quick_actions' => 'Header: Quick Actions',
                'pulse' => 'Header: Batches / Students / Subjects',
                'my_status' => 'Header: My Check-in Today',
                'kpi_students' => 'Card: My Students',
                'kpi_attendance' => 'Card: Class Attendance',
                'kpi_homework' => 'Card: Open Homework',
                'kpi_exams' => 'Card: Upcoming Exams',
                'kpi_my_attendance' => 'Card: My Attendance',
                'classes' => 'Panel: My Classes',
                'attention' => 'Panel: Needs Attention',
                'chart_attendance_trend' => 'Chart: Attendance Trend',
                'chart_batch_attendance' => 'Chart: Batch-wise Attendance',
                'list_exams' => 'List: Upcoming Exams',
                'list_homework' => 'List: Recent Homework',
                'list_tasks' => 'List: My Tasks',
                'list_notices' => 'List: Notice Board',
                'list_holidays' => 'List: Holidays & Birthdays',
            ],
        ],

        'parent_dashboard' => [
            'name' => 'Parent Dashboard',
            'enabled' => true,
            'use_defaults' => false,
            'plan_module' => 'dashboard',
            'inherit_from' => 'dashboard.view',
            'actions' => [
                'quick_actions' => 'Header: Quick Actions',
                'kpi_attendance' => 'Card: Attendance This Month',
                'kpi_fees' => 'Card: Fees Due',
                'kpi_exams' => 'Card: Upcoming Exams',
                'kpi_homework' => 'Card: Homework This Week',
                'kpi_results' => 'Card: Average Score',
                'chart_attendance' => 'Chart: Attendance',
                'attention' => 'Panel: Needs Attention',
                'classes' => 'Panel: Classes',
                'chart_results' => 'Chart: Performance',
                'list_homework' => 'List: Homework',
                'list_exams' => 'List: Upcoming Exams',
                'list_results' => 'List: Recent Results',
                'list_fees' => 'List: Fees & Payments',
                'list_notices' => 'List: Notice Board',
                'list_holidays' => 'List: Holidays & Leave',
            ],
        ],

        'classes' => [
            'name' => 'Institute Setting',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => false,
            'actions' => [],
        ],

        'branch' => [
            'name' => 'Branch',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'status' => 'Activate / Deactivate',
            ],
        ],

        'batch' => [
            'name' => 'Batch',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'status' => 'Activate / Deactivate',
                'assign' => 'Assign Batch',
                'tab_details' => 'View Tab: Batch Details',
                'tab_attendance' => 'View Tab: Attendance',
                'tab_students' => 'View Tab: Students',
                'tab_exam' => 'View Tab: Exam',
                'tab_homework' => 'View Tab: Homework',
                'tab_attendance_report' => 'View Tab: Attendance Report',
                'tab_notice' => 'View Tab: Notice',
            ],
        ],

        'teacher' => [
            'name' => 'Teacher',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'status' => 'Activate / Deactivate',
                'tab_attendance' => 'View Tab: Attendance',
                'tab_batches' => 'View Tab: Batches',
                'tab_assigned_work' => 'View Tab: Assigned Work',
                'tab_login_history' => 'View Tab: Login History',
            ],
        ],

        'staff' => [
            'name' => 'Staff',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'status' => 'Activate / Deactivate',
                'tab_attendance' => 'View Tab: Attendance',
                'tab_login_history' => 'View Tab: Login History',
                'tab_assigned_work' => 'View Tab: Assign Work List',
            ],
        ],

        // Governs ONLY the merged "Users" list page (Teacher / Branch Manager / Principal /
        // Receptionist / Accountant tabs). Does not replace `teacher.*` / `staff.*`, which still
        // guard the dedicated Teacher & Staff pages, profile tabs, attendance, reports, etc.
        'users' => [
            'name' => 'Users',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view_teacher' => 'View Teacher Tab',
                'add_teacher' => 'Add Teacher',
                'edit_teacher' => 'Edit Teacher',
                'delete_teacher' => 'Delete Teacher',
                'status_teacher' => 'Activate / Deactivate Teacher',

                'view_branch_manager' => 'View Branch Manager Tab',
                'add_branch_manager' => 'Add Branch Manager',
                'edit_branch_manager' => 'Edit Branch Manager',
                'delete_branch_manager' => 'Delete Branch Manager',
                'status_branch_manager' => 'Activate / Deactivate Branch Manager',

                'view_principal' => 'View Principal Tab',
                'add_principal' => 'Add Principal',
                'edit_principal' => 'Edit Principal',
                'delete_principal' => 'Delete Principal',
                'status_principal' => 'Activate / Deactivate Principal',

                'view_receptionist' => 'View Receptionist Tab',
                'add_receptionist' => 'Add Receptionist',
                'edit_receptionist' => 'Edit Receptionist',
                'delete_receptionist' => 'Delete Receptionist',
                'status_receptionist' => 'Activate / Deactivate Receptionist',

                'view_accountant' => 'View Accountant Tab',
                'add_accountant' => 'Add Accountant',
                'edit_accountant' => 'Edit Accountant',
                'delete_accountant' => 'Delete Accountant',
                'status_accountant' => 'Activate / Deactivate Accountant',
            ],
        ],

        'subject' => [
            'name' => 'Subject',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'status' => 'Activate / Deactivate',
            ],
        ],

        'standard' => [
            'name' => 'Standard',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'status' => 'Activate / Deactivate',
            ],
        ],

        'student' => [
            'name' => 'Student',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'add' => 'Add',
                'edit' => 'Edit',
                'status' => 'Activate / Deactivate',
                'receipt' => 'Receipt',
                'export' => 'Export Students',
            ],
        ],

        'inquiry' => [
            'name' => 'Inquiry',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'receipt' => 'Receipt',
                'export' => 'Export Inquiries',
                'convert' => 'Convert to Student',
            ],
        ],

        'follow_up' => [
            'name' => 'Follow Up',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Updates',
                'add' => 'Add Update',
                'export' => 'Export Follow Ups',
            ],
        ],

        'fees' => [
            'name' => 'Fees',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'add' => 'Add Fees',
                'receipt' => 'Receipt',
                'delete' => 'Delete',
            ],
        ],

        'fees_follow_up' => [
            'name' => 'Fees Follow Up',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Details',
                'add' => 'Collect Fees',
                'export' => 'Export',
            ],
        ],

        'gst_invoice' => [
            'name' => 'GST Invoice',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'generate' => 'Generate GST Invoice',
                'download' => 'Download GST Invoice',
            ],
        ],

        'attendance' => [
            'name' => 'Student Attendance',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'add' => 'Add Attendance',
                'edit' => 'Edit Attendance',
            ],
        ],

        'teacher_attendance' => [
            'name' => 'Teacher Attendance',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'add' => 'Add Attendance',
                'edit' => 'Edit Attendance',
            ],
        ],

        'exam' => [
            'name' => 'Exam',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Detail',
                'download' => 'Download',
                'add' => 'Add',
                'edit' => 'Edit',
                'delete' => 'Delete',
            ],
        ],

        'result_announcement' => [
            'name' => 'Result Announcement',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Details',
                'export' => 'Export PDF',
                'add' => 'Add',
                'edit' => 'Edit',
                'delete' => 'Delete',
            ],
        ],

        'homework' => [
            'name' => 'Homework',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Details',
                'download' => 'Download',
                'add' => 'Add',
                'edit' => 'Edit',
                'delete' => 'Delete',
            ],
        ],

        'study_material' => [
            'name' => 'Study Material',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Details',
                'download' => 'Download',
                'add' => 'Add',
                'edit' => 'Edit',
                'delete' => 'Delete',
            ],
        ],

        'notice' => [
            'name' => 'Notice',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Details',
                'add' => 'Add',
                'edit' => 'Edit',
                'status' => 'Activate / Deactivate',
                'delete' => 'Delete',
            ],
        ],

        'holiday' => [
            'name' => 'Holiday',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Details',
                'add' => 'Add',
                'edit' => 'Edit',
                'delete' => 'Delete',
            ],
        ],

        'student_leave' => [
            'name' => 'Student Leave',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Module',
                'show' => 'View Details',
                'add' => 'Apply Leave',
                'approve' => 'Approve Student Leave',
                'reject' => 'Reject Student Leave',
            ],
        ],

        'staff_leave' => [
            'name' => 'Staff Leave',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'approve' => 'Approve Staff Leave',
                'reject' => 'Reject Staff Leave',
            ],
        ],

        'income_expense' => [
            'name' => 'Income & Expense',
            'enabled' => true,
            'use_defaults' => false,
            'soft_delete' => true,
            'actions' => [
                'view' => 'View',
                'add' => 'Add',
                'delete' => 'Delete',
                'restore' => 'Restore',
                'show_total_income' => 'Show Total Income',
            ],
        ],

        'income_expense_category' => [
            'name' => 'Income & Expense Category',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'plan_module' => 'income_expense',
            'inherit_from' => 'income_expense.view',
            'actions' => [
                'status' => 'Activate / Deactivate',
            ],
        ],

        'inventory' => [
            'name' => 'Inventory',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [],
        ],

        'assets_category' => [
            'name' => 'Assets Category',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'plan_module' => 'inventory',
            'inherit_from' => 'inventory.view',
            'actions' => [
                'status' => 'Activate / Deactivate',
            ],
        ],

        'todo' => [
            'name' => 'To Do',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'approve' => 'Approve Task',
                'reject' => 'Reject Task',
                'carry_forward' => 'Carry Forward Task',
            ],
        ],

        'roles' => [
            'name' => 'Roles',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'assign_permissions' => 'Assign Role Permissions',
            ],
        ],

        'permissions' => [
            'name' => 'Permissions',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Permissions',
                'sync' => 'Sync Permission Matrix',
            ],
        ],

        'settings' => [
            'name' => 'Settings',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Settings',
                'edit' => 'Edit Settings',
            ],
        ],

        'institutes' => [
            'name' => 'Institutes',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'suspend' => 'Suspend Institute',
                'assign_plan' => 'Assign Plan / Trial',
            ],
        ],

        'plans' => [
            'name' => 'Plans',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [],
        ],

        'project_inquiry' => [
            'name' => 'Project Inquiry',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [
                'confirm_demo' => 'Confirm Demo',
                'decline_demo' => 'Decline Demo',
            ],
        ],

        'demo_calendar' => [
            'name' => 'Demo Calendar',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Demo Calendar',
            ],
        ],

        'demo_availability' => [
            'name' => 'Demo Availability',
            'enabled' => true,
            'use_defaults' => true,
            'soft_delete' => true,
            'actions' => [],
        ],

        'billing' => [
            'name' => 'Billing',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Billing',
                'checkout' => 'Subscribe / Upgrade Plan',
            ],
        ],

        'reports' => [
            'name' => 'Reports',
            'enabled' => true,
            'use_defaults' => false,
            'actions' => [
                'view' => 'View Reports',
            ],
        ],
    ],
];
