@extends('website.layouts.app')
@section('content')

<main class="legal-page">
    <section class="page-hero">
      <div class="atmosphere" aria-hidden="true"><div class="glow glow-1"></div></div>
      <div class="container">
        <div class="page-hero-inner center">
          <p class="eyebrow">Legal</p>
          <h1>Privacy Policy</h1>
          <p class="section-sub">How {{ config('brand.name') }} handles information collected on this marketing website and demo requests.</p>
        </div>
      </div>
    </section>

    <div class="container legal-wrap">
      <article class="legal-panel">
        <div class="legal-meta">
          <span><strong>Applies to:</strong> ivclasses.com marketing site</span>
          <span><strong>Last updated:</strong> August 2026</span>
        </div>

        <div class="legal-block">
          <h2><span class="legal-num">01</span> Information we collect</h2>
          <p>
            When you submit the Book a Demo form, we collect details you provide, such as:
          </p>
          <ul>
            <li>Name and institute / class name</li>
            <li>City, phone / WhatsApp, and email (if shared)</li>
            <li>Optional: number of branches, student volume, and interest area</li>
            <li>Optional free-text message about your institute</li>
          </ul>
        </div>

        <div class="legal-block">
          <h2><span class="legal-num">02</span> How we use it</h2>
          <p>
            We use this information to contact you about scheduling a product demo and to understand
            which plan or features may fit your institute.
          </p>
          <ul>
            <li>Respond to demo requests and follow-ups</li>
            <li>Prepare a walkthrough relevant to your size and workflow</li>
            <li>Share basic product or pricing information you asked for</li>
          </ul>
          <p>We do not sell your personal information to third parties.</p>
        </div>

        <div class="legal-block">
          <h2><span class="legal-num">03</span> Storage &amp; access</h2>
          <p>
            Demo requests may be stored in our internal systems (for example CRM, email inbox, or spreadsheet)
            so the {{ config('brand.name') }} team can follow up. Access is limited to people handling demos and onboarding.
          </p>
        </div>

        <div class="legal-block">
          <h2><span class="legal-num">04</span> Cookies &amp; analytics</h2>
          <p>
            This marketing site may use basic technical storage needed for the site to work.
            If we add analytics or tracking tools later, this page will be updated to describe them.
          </p>
        </div>

        <div class="legal-block">
          <h2><span class="legal-num">05</span> Your choices</h2>
          <p>
            You can ask us to update or remove a demo request contact record by emailing us.
            We will respond within a reasonable time.
          </p>
        </div>

        <div class="legal-block">
          <h2><span class="legal-num">06</span> Contact</h2>
          <p>
            For privacy questions, email
            <a href="mailto:hello@ivclasses.com">hello@ivclasses.com</a>.
          </p>
        </div>

        <p class="legal-notice">
          This page is a marketing-site privacy summary. Replace with final legal text reviewed for your business before public launch.
        </p>

        <div class="legal-actions">
          <a href="{{ route('home') }}" class="btn btn-ghost-navy">← Back to Home</a>
          <a href="{{ route('book-demo') }}" class="btn btn-primary">Book a Demo</a>
        </div>
      </article>
    </div>
  </main>
  @endsection