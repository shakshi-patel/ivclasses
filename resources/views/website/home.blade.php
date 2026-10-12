@extends('website.layouts.app')
@section('content')
    <main>
        {{-- HERO --}}
        <section class="hero-home">
            <div class="atmosphere" aria-hidden="true">
                <div class="glow glow-1"></div>
                <div class="glow glow-2"></div>
                <div class="glow glow-3"></div>
                <div class="grid-bg"></div>
            </div>
            <div class="container hero-home-grid">
                <div class="hero-copy">
                    <p class="hero-kicker reveal" data-reveal>
                        <span class="kicker-dot"></span>
                        Web CRM + Mobile App for coaching classes
                    </p>
                    <p class="brand-hero reveal" data-reveal data-delay="40">IVClasses</p>
                    <h1 class="reveal" data-reveal data-delay="100">
                        Run your institute like a modern business — not WhatsApp &amp; Excel.
                    </h1>
                    <p class="hero-sub reveal" data-reveal data-delay="160">
                        Admissions, fees, attendance, exams and parent updates — one live platform for
                        owners, teachers, staff and parents.
                    </p>
                    <div class="hero-ctas reveal" data-reveal data-delay="200">
                        <a href="{{ route('book-demo') }}" class="btn btn-primary">Book Free Demo</a>
                        <a href="#compare" class="btn btn-ghost">See how it works</a>
                    </div>
                    <div class="hero-trust-line reveal" data-reveal data-delay="260">
                        <span>Multi-branch ready</span>
                        <span class="dot" aria-hidden="true"></span>
                        <span>Role-based access</span>
                        <span class="dot" aria-hidden="true"></span>
                        <span>Live parent updates</span>
                    </div>
                </div>

                <div class="hero-visuals reveal" data-reveal data-delay="120" id="heroVisuals">
                    <div class="visual-ring" aria-hidden="true"></div>
                    <div class="hero-image-card" data-parallax="0.03">
                        <img src="{{ asset('website-assets/images/hero-ivclasses.png') }}"
                            alt="IVClasses Web CRM and mobile app for coaching institutes"
                            class="hero-main-image" />
                    </div>
                </div>
            </div>
        </section>

        {{-- WHO USES IT (image role cards) --}}
        <section class="section trust-section roles-section" id="who-uses">
            <div class="container">
                <div class="section-head center reveal" data-reveal>
                    <p class="eyebrow">Built for real institute teams</p>
                    <h2 class="section-title">Who uses IVClasses?</h2>
                    <p class="section-sub">
                        Every role gets the right access — so owners stay in control, teachers teach,
                        staff operate, and parents stay informed without calling the office.
                    </p>
                </div>

                <div class="roles-grid">
                    <article class="role-card reveal" data-reveal data-role="superadmin">
                        <div class="role-image-wrap">
                            <img src="{{ asset('website-assets/images/role-superadmin.png') }}"
                                alt="Superadmin managing institutes on IVClasses" />
                        </div>
                        <div class="role-card-body">
                            <div class="role-card-top"><span class="role-num">01</span></div>
                            <h3>Platform Admin</h3>
                            <p>Platform-level control — manage classes, plans, subscriptions, access and overall platform health from one central console.</p>
                            <div class="role-tags"><span>Platform</span><span>Classes</span><span>Access</span></div>
                        </div>
                    </article>
                    <article class="role-card reveal" data-reveal data-delay="50" data-role="admin">
                        <div class="role-image-wrap">
                            <img src="{{ asset('website-assets/images/role-classes-branch.png') }}"
                                alt="Classes and branch owners using Web CRM" />
                        </div>
                        <div class="role-card-body">
                            <div class="role-card-top"><span class="role-num">02</span></div>
                            <h3>Classes &amp; Branch</h3>
                            <p>Class owners and managers — manage students, admissions, fees, staff, inventory and branch operations from Web CRM.</p>
                            <div class="role-tags"><span>Web CRM</span><span>Reports</span><span>Multi-branch</span></div>
                        </div>
                    </article>
                    <article class="role-card reveal" data-reveal data-delay="100" data-role="teacher">
                        <div class="role-image-wrap">
                            <img src="{{ asset('website-assets/images/role-teachers.png') }}"
                                alt="Teachers marking attendance and homework" />
                        </div>
                        <div class="role-card-body">
                            <div class="role-card-top"><span class="role-num">03</span></div>
                            <h3>Teachers</h3>
                            <p>Teachers get access to their assigned batches — manage attendance, homework, exams, results and daily academic activities from app or web.</p>
                            <div class="role-tags"><span>Attendance</span><span>Homework</span><span>Exams</span></div>
                        </div>
                    </article>
                    <article class="role-card reveal" data-reveal data-delay="150" data-role="staff">
                        <div class="role-image-wrap">
                            <img src="{{ asset('website-assets/images/role-staff.png') }}"
                                alt="Staff handling follow-ups and office tasks" />
                        </div>
                        <div class="role-card-body">
                            <div class="role-card-top"><span class="role-num">04</span></div>
                            <h3>Staff</h3>
                            <p>Staff work according to their roles — manage follow-ups, fees, tasks, attendance, leave, income, expenses and daily operations.</p>
                            <div class="role-tags"><span>Follow-up</span><span>Tasks</span><span>Finance</span></div>
                        </div>
                    </article>
                    <article class="role-card reveal" data-reveal data-delay="200" data-role="parent">
                        <div class="role-image-wrap">
                            <img src="{{ asset('website-assets/images/role-parents.png') }}"
                                alt="Parents tracking child progress on mobile" />
                        </div>
                        <div class="role-card-body">
                            <div class="role-card-top"><span class="role-num">05</span></div>
                            <h3>Parents</h3>
                            <p>Parents stay connected with their child’s progress — track attendance, homework, fees, results, notices and leave requests from the mobile app.</p>
                            <div class="role-tags"><span>Mobile App</span><span>Results</span><span>Leave</span></div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- BEFORE / AFTER --}}
        <section class="section section-alt" id="compare">
            <div class="container">
                <div class="section-head center reveal" data-reveal>
                    <p class="eyebrow">Why institutes switch</p>
                    <h2 class="section-title">Before chaos. After control.</h2>
                    <p class="section-sub">See the difference when your class runs on one live system instead of scattered chats and sheets.</p>
                </div>

                <div class="compare-flow">
                    <article class="compare-row compare-row-before reveal" data-reveal>
                        <div class="compare-row-media">
                            <img src="{{ asset('website-assets/images/before-ivclasses.png') }}"
                                alt="Before IVClasses — Excel WhatsApp and paper chaos" />
                        </div>
                        <div class="compare-row-copy">
                            <span class="compare-badge">Before IVClasses</span>
                            <h3>Manual work slows your institute</h3>
                            <p>Manage everything across WhatsApp, Excel and notebooks.</p>
                            <ul class="check-list muted-list">
                                <li>Missed inquiry follow-ups</li>
                                <li>Scattered fee records</li>
                                <li>Manual attendance</li>
                                <li>Homework & exam updates</li>
                                <li>No branch-wise overview</li>
                            </ul>
                        </div>
                    </article>

                    <article class="compare-row compare-row-after reveal" data-reveal data-delay="80">
                        <div class="compare-row-copy">
                            <span class="compare-badge compare-badge-good">With IVClasses</span>
                            <h3>Run your institute from one platform</h3>
                            <p>One platform for your Classes, Branches, Teachers, Staff and Parents.</p>
                            <ul class="check-list">
                                <li>Student & Staff Attendance</li>
                                <li>Follow-ups, Fees & To-Do Tasks</li>
                                <li>Homework, Exams & Results</li>
                                <li>Income, Expenses & Fees</li>
                                <li>Inventory & Assets</li>
                                <li>Notices, Holidays & Leave</li>
                                <li>MWeb CRM + Mobile App</li>
                            </ul>
                        </div>
                        <div class="compare-row-media">
                            <img src="{{ asset('website-assets/images/after-ivclasses.png') }}"
                                alt="After IVClasses — organized live CRM dashboard" />
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- PRODUCT MODULES: 4 MAIN + SMALL BETWEEN --}}
        <section class="section" id="modules">
            <div class="container">
                <div class="section-head center reveal" data-reveal>
                    <p class="eyebrow">Product modules</p>
                    <h2 class="section-title">What makes institutes choose IVClasses</h2>
                    <p class="section-sub">
                        Four core systems that run the institute — with supporting modules that keep daily ops complete.
                    </p>
                </div>

                {{-- 1. ATTENDANCE --}}
                <div class="feature-showcase">
                    <div class="feature-story reveal" data-reveal>
                        <div class="feature-media">
                            <img src="{{ asset('website-assets/images/module-attendance.png') }}"
                                alt="Student and staff attendance dashboard in IVClasses" />
                        </div>
                        <div class="feature-copy">
                            <div class="feature-badge"><span class="feature-icon">✓</span> Main module</div>
                            <h3>Student &amp; staff attendance — live every day</h3>
                            <p>
                            Track student and staff attendance easily from web or app, with clear daily records.
                            </p>
                            <ul class="feature-points">
                                <li>Batch-wise student attendance</li>
                                <li>Staff attendance & punch</li>
                                <li>Leave tracking</li>
                                <li>Parent attendance updates</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="module-mini-grid reveal" data-reveal>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-book-alt"></i></span>
                        <h4>Homework</h4>
                        <p>Teachers publish homework; parents see it on the app the same day.</p>
                    </article>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-checkbox"></i></span>
                        <h4>To-do Tasks</h4>
                        <p>Assign and track office and field tasks so nothing stays pending.</p>
                    </article>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-calendar"></i></span>
                        <h4>Leave Requests</h4>
                        <p>Manage student and staff leave requests with simple approval tracking.</p>
                    </article>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-phone-call"></i></span>
                        <h4>Follow-up</h4>
                        <p>Track inquiries with status, reminders and complete follow-up history.</p>
                    </article>
                </div>

                {{-- 2. EXAM & RESULTS --}}
                <div class="feature-showcase">
                    <div class="feature-story reverse reveal" data-reveal>
                        <div class="feature-media">
                            <img src="{{ asset('website-assets/images/module-exams-results.png') }}"
                                alt="Exam schedule and result announcement in IVClasses" />
                        </div>
                        <div class="feature-copy">
                            <div class="feature-badge"><span class="feature-icon">🏆</span> Main module</div>
                            <h3>Exams &amp; result announcement</h3>
                            <p>
                            Schedule exams, manage marks and share results with students and parents easily.
                            </p>
                            <ul class="feature-points">
                                <li>Batch-wise exam scheduling</li>
                                <li>Marks entry & review</li>
                                <li>Result updates for parents & students</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="module-mini-grid reveal" data-reveal>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-megaphone"></i></span>
                        <h4>Notice &amp; Holiday</h4>
                        <p>Publish notices and manage holiday calendars for the right audience.</p>
                    </article>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-wallet"></i></span>
                        <h4>Fees Follow-up</h4>
                        <p>Track pending fees with reminders and follow-up status.</p>
                    </article>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-building"></i></span>
                        <h4>Classes &amp; Branches</h4>
                        <p>Manage multiple classes and branches with central control.</p>
                    </article>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-users"></i></span>
                        <h4>Teachers · Staff · Parents</h4>
                        <p>Role-based access so everyone sees only what they need.</p>
                    </article>
                </div>

                {{-- 3. INCOME & EXPENSE --}}
                <div class="feature-showcase">
                    <div class="feature-story reveal" data-reveal>
                        <div class="feature-media">
                            <img src="{{ asset('website-assets/images/module-income-expense.png') }}"
                                alt="Income and expense finance dashboard in IVClasses" />
                        </div>
                        <div class="feature-copy">
                            <div class="feature-badge"><span class="feature-icon">₹</span> Main module</div>
                            <h3>Income &amp; expense Management</h3>
                            <p>
                            Track institute income and expenses with a clear financial overview.
                            </p>
                            <ul class="feature-points">
                                <li>Income & expense entries</li>
                                <li>Daily & monthly tracking</li>
                                <li>Finance overview</li>
                                <li>Connected with fees recovery</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="module-mini-grid module-mini-grid-3 reveal" data-reveal>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-dollar"></i></span>
                        <h4>Fees Follow-up</h4>
                        <p>Track installments, pending balances and recovery records in one place.</p>
                    </article>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-list-check"></i></span>
                        <h4>To-do Tasks</h4>
                        <p>Assign daily recovery tasks to counselors and accounts teams.</p>
                    </article>
                    <article class="module-mini">
                        <span class="module-mini-icon" aria-hidden="true"><i class="fi fi-rr-clock"></i></span>
                        <h4>Staff Attendance</h4>
                        <p>Track staff presence and punch records without paper registers.</p>
                    </article>
                </div>

                {{-- 4. INVENTORY & ASSETS --}}
                <div class="feature-showcase">
                    <div class="feature-story reverse reveal" data-reveal>
                        <div class="feature-media">
                            <img src="{{ asset('website-assets/images/module-inventory-assets.png') }}"
                                alt="Inventory and assets tracking in IVClasses" />
                        </div>
                        <div class="feature-copy">
                            <div class="feature-badge"><span class="feature-icon">📦</span> Main module</div>
                            <h3>Inventory &amp; assets across branches</h3>
                            <p>
                            Track stock and assets across branches with clear visibility.
                            </p>
                            <ul class="feature-points">
                                <li>Inventory stock tracking</li>
                                <li>Assets register</li>
                                <li>Branch-wise visibility</li>
                                <li>Low-stock tracking</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- PLATFORM --}}
        <section class="section section-alt">
            <div class="container">
                <div class="section-head center reveal" data-reveal>
                    <p class="eyebrow">Platform</p>
                    <h2 class="section-title">Web for the office. App for the field.</h2>
                    <p class="section-sub">One SaaS product, two experiences — everyone stays on the same institute data.</p>
                </div>
                <div class="platform-grid">
                    <div class="platform-panel web reveal" data-reveal>
                        <span class="platform-tag">Complete  institute  control</span>
                        <h2>Web CRM</h2>
                        <p>Manage branches, admissions, fees, finance, inventory, exams and daily operations from one admin console.</p>
                        <ul class="chip-list">
                            <li>Students & Staff</li>
                            <li>Attendance & Academics</li>
                            <li>Finance & Fees</li>
                            <li>Inventory & Assets</li>
                        </ul>
                        <a href="{{ route('features') }}#web-crm" class="btn btn-primary">See Web Features</a>
                    </div>

                    <div class="platform-panel app reveal" data-reveal data-delay="100">
                        <span class="platform-tag">Stay  connected  on  the  go</span>
                        <h2>Mobile App</h2>
                        <p>Teachers, staff and parents can manage daily activities and stay updated from anywhere.</p>
                        <ul class="chip-list">
                            <li>Attendance & Homework</li>
                            <li>Tasks & Follow-ups</li>
                            <li>Fees & Results</li>
                            <li>Leave & Notices</li>
                        </ul>
                        <a href="{{ route('features') }}#mobile-app" class="btn btn-primary">See App Features</a>
                    </div>
                </div>
            </div>
        </section>

        {{-- HOW IT WORKS --}}
        <section class="section">
            <div class="container">
                <div class="section-head center reveal" data-reveal>
                    <p class="eyebrow">How it works</p>
                    <h2 class="section-title">Live in three steps</h2>
                    <p class="section-sub">Most institutes start day-to-day ops once branches, batches and roles are ready.</p>
                </div>
                <div class="steps" id="steps">
                    <div class="step-line" aria-hidden="true"><span class="step-line-fill"></span></div>
                    <div class="step reveal" data-reveal>
                        <span class="step-num">01</span>
                        <h3>Setup</h3>
                        <p>Create branches, classes, teachers and staff. Turn on the modules you need and set role permissions once.</p>
                    </div>
                    <div class="step reveal" data-reveal data-delay="100">
                        <span class="step-num">02</span>
                        <h3>Convert</h3>
                        <p>Capture inquiries, run follow-ups, convert to admission and start fee plans without switching tools.</p>
                    </div>
                    <div class="step reveal" data-reveal data-delay="200">
                        <span class="step-num">03</span>
                        <h3>Operate</h3>
                        <p>Attendance, homework, exams, notices, leave, fees recovery and parent updates stay connected on web + app.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="section section-alt">
            <div class="container">
                <div class="section-head center reveal" data-reveal>
                    <p class="eyebrow">Plans</p>
                    <h2 class="section-title">Start a trial on a published plan</h2>
                    <p class="section-sub">
                        These are the plans added for institutes. Choose one and the registration form opens with that plan already selected.
                    </p>
                </div>
                @include('website.partials.plan_cards', ['plans' => $plans ?? collect(), 'cardMode' => 'link'])
                <div class="center-cta reveal" data-reveal>
                    <a href="{{ route('book-demo') }}" class="btn btn-ghost center-cta-ghost">Prefer a walkthrough? Book a demo</a>
                </div>
            </div>
        </section>
    </main>
@endsection
