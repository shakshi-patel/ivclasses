@extends('website.layouts.app')
@section('content')

<main>
    <section class="page-hero">
      <div class="atmosphere" aria-hidden="true"><div class="glow glow-1"></div><div class="glow glow-2"></div></div>
      <div class="container">
        <div class="page-hero-inner center">
          <p class="eyebrow reveal" data-reveal>Pricing</p>
          <h1 class="reveal" data-reveal data-delay="60">Simple plans for every institute size</h1>
          <p class="section-sub reveal" data-reveal data-delay="120">Pricing depends on branches &amp; students — book a demo for a custom quote that fits your setup.</p>
          <div class="page-hero-meta reveal" data-reveal data-delay="180">
            <span>Starter · Growth · Enterprise</span>
            <span>Custom quote</span>
            <span>Free demo</span>
          </div>
          <div class="hero-ctas reveal" data-reveal data-delay="240">
            <a href="{{ route('register-institute') }}" class="btn btn-primary">Start Free Trial</a>
            <a href="{{ route('book-demo') }}" class="btn btn-ghost">Book Free Demo</a>
          </div>
        </div>
      </div>
    </section>

    <section class="section section-alt">
      <div class="container">
        @include('website.partials.plan_cards', ['plans' => $plans, 'cardMode' => 'link'])

        <div class="pricing-note-bar reveal" data-reveal>
          <p><strong>No fake list prices</strong> — quote based on branches &amp; students</p>
          <p><strong>Free 30-min demo</strong> before you decide</p>
          <p><strong>Migration help</strong> during onboarding</p>
        </div>
      </div>
    </section>

    <section class="section faq-section">
      <div class="container">
        <div class="section-head center reveal" data-reveal>
          <p class="eyebrow">FAQ</p>
          <h2 class="section-title">Common questions</h2>
          <p class="section-sub">Quick answers before you book a demo.</p>
        </div>

        <div class="faq-list" id="faqList">
          <div class="faq-item reveal is-open" data-reveal>
            <button type="button" class="faq-q" aria-expanded="true">
              <span>What’s included in a demo?</span>
              <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-a">
              <p>A 30-minute walkthrough of Web CRM + Mobile App tailored to your institute size and workflow.</p>
            </div>
          </div>
          <div class="faq-item reveal" data-reveal data-delay="60">
            <button type="button" class="faq-q" aria-expanded="false">
              <span>Can we migrate existing data?</span>
              <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-a">
              <p>Yes — during onboarding we help import students, batches, and fee structures. Details confirmed in your demo.</p>
            </div>
          </div>
          <div class="faq-item reveal" data-reveal data-delay="120">
            <button type="button" class="faq-q" aria-expanded="false">
              <span>Do you support multi-branch?</span>
              <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-a">
              <p>Growth and Enterprise plans are built for multi-branch institutes with branch-wise context and reports.</p>
            </div>
          </div>
          <div class="faq-item reveal" data-reveal data-delay="180">
            <button type="button" class="faq-q" aria-expanded="false">
              <span>Are prices fixed INR plans?</span>
              <span class="faq-icon" aria-hidden="true"></span>
            </button>
            <div class="faq-a">
              <p>Pricing depends on branches &amp; students. We quote after understanding your setup — no fake list prices.</p>
            </div>
          </div>
        </div>

        <div class="addons-panel reveal" data-reveal>
          <p class="eyebrow">Add-ons (future)</p>
          <p class="addons-note">Optional extras we can discuss in your demo.</p>
          <div class="addons">
            <span class="addon-chip">Online fee gateway</span>
            <span class="addon-chip">SMS / WhatsApp</span>
            <span class="addon-chip">Push notifications</span>
          </div>
        </div>
      </div>
    </section>
  </main>
  @endsection