@extends('website.layouts.app')
@section('content')

<main>
    <section class="page-hero">
      <div class="atmosphere" aria-hidden="true"><div class="glow glow-1"></div><div class="glow glow-2"></div></div>
      <div class="container">
        <div class="page-hero-inner center">
          <p class="eyebrow reveal" data-reveal>About us</p>
          <h1 class="reveal" data-reveal data-delay="60">Built for coaching institutes that want clarity</h1>
          <p class="section-sub reveal" data-reveal data-delay="120">IVClasses is a complete institute management SaaS — admissions, fees, academics and parents on one live system.</p>
          <div class="page-hero-meta reveal" data-reveal data-delay="180">
            <span>Web CRM</span>
            <span>Mobile App</span>
            <span>Multi-branch ready</span>
          </div>
          <div class="hero-ctas reveal" data-reveal data-delay="240">
            <a href="{{ route('book-demo') }}" class="btn btn-primary">Book a Demo</a>
            <a href="{{ route('features') }}" class="btn btn-ghost">Explore Features</a>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container about-grid">
        <div class="about-story">
          <div class="section-head reveal" data-reveal>
            <p class="eyebrow">Our story</p>
            <h2 class="section-title">A complete institute management SaaS</h2>
          </div>
          <p class="reveal" data-reveal>
            IVClasses is built for coaching classes and tuition centers that outgrew Excel sheets and WhatsApp groups.
            CRM, academics, fees, attendance and staff ops live in one system — via Web CRM and Mobile App.
          </p>
          <p class="reveal" data-reveal>
            From the first inquiry to fee recovery and parent updates, every role gets a focused workspace so owners stay in control without daily firefighting.
          </p>
          <blockquote class="mission reveal" data-reveal>
            Help institutes manage admissions, fees and classroom operations without Excel sheets and scattered WhatsApp groups.
          </blockquote>
        </div>
        <div class="diff-panel reveal" data-reveal data-delay="100">
          <p class="eyebrow">What makes us different</p>
          <ul class="check-list">
            <li>Indian coaching workflow (inquiry → follow-up → admission → fees)</li>
            <li>Multi-branch ready from day one</li>
            <li>Role-based access (Admin, Branch, Teacher, Staff, Parent)</li>
            <li>Web for office + App for field &amp; parents</li>
            <li>One live source of truth — no double entry</li>
          </ul>
        </div>
      </div>
    </section>

    <section class="section section-alt">
      <div class="container">
        <div class="section-head center reveal" data-reveal>
          <p class="eyebrow">How we think</p>
          <h2 class="section-title">Principles behind IVClasses</h2>
          <p class="section-sub">Simple rules that keep the product useful for real institutes — not generic software filled with unused menus.</p>
        </div>
        <div class="about-pillars">
          <div class="about-pillar reveal" data-reveal>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
            <h3>Workflow first</h3>
            <p>Every module follows how Indian coaching institutes already work — inquiry, admission, batch, fees, attendance.</p>
          </div>
          <div class="about-pillar reveal" data-reveal data-delay="80">
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <h3>Role clarity</h3>
            <p>Owners, branch staff, teachers and parents only see what they need — clean permissions, less chaos.</p>
          </div>
          <div class="about-pillar reveal" data-reveal data-delay="160">
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M12 15h4"/></svg></div>
            <h3>Office + field</h3>
            <p>Web CRM runs the office. Mobile App covers classrooms and parents — same data, kept in sync.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="section-head center reveal" data-reveal>
          <p class="eyebrow">Audience</p>
          <h2 class="section-title">Who we serve</h2>
          <p class="section-sub">From a single tuition batch to multi-branch exam centers — IVClasses scales with your setup.</p>
        </div>
        <div class="trust-row">
          <div class="trust-item reveal" data-reveal>
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg></div>
            <div>
              <strong>Coaching / tuition</strong>
              <span>Single or multi-batch classes</span>
            </div>
          </div>
          <div class="trust-item reveal" data-reveal data-delay="80">
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M4 20h16M6 20V8h4v12M14 20V4h4v16"/></svg></div>
            <div>
              <strong>Multi-branch</strong>
              <span>Central control, local ops</span>
            </div>
          </div>
          <div class="trust-item reveal" data-reveal data-delay="160">
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
            <div>
              <strong>Exam centers</strong>
              <span>Competitive prep institutes</span>
            </div>
          </div>
          <div class="trust-item reveal" data-reveal data-delay="240">
            <div class="icon-wrap"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <div>
              <strong>Tuition networks</strong>
              <span>School-support groups</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section section-alt">
      <div class="container">
        <div class="about-cta-box reveal" data-reveal>
          <div>
            <h3>See IVClasses on your institute workflow</h3>
            <p>Book a free 30-minute demo — we walk through admissions, fees and app flow tailored to your size.</p>
          </div>
          <div class="about-cta-actions">
            <a href="{{ route('book-demo') }}" class="btn btn-primary">Book Free Demo</a>
            <a href="{{ route('pricing') }}" class="btn btn-ghost">View Pricing</a>
          </div>
        </div>
      </div>
    </section>
  </main>
@endsection