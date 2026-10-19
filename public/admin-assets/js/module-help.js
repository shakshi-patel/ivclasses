/**
 * Institute Module Help wizard — first-visit auto-open + How it works.
 * Boot JSON: #moduleHelpBoot { guide_id, module_key, user_id, show_url }
 * Done ids: localStorage iv_module_help_done_{userId} → [guideId, ...]
 * CTA resume: sessionStorage iv_module_help_resume → { guideId, stepIndex }
 */
(function () {
  // Setup already explains this page. A second tour paints a full-screen
  // overlay and the page can no longer be clicked.
  if (new URLSearchParams(window.location.search || '').get('onboarding_step')) {
    window.ModuleHelpWizard = {
      hasResume: function () { return false; },
      resumeIfNeeded: function () {}
    };
    return;
  }

  var RESUME_KEY = 'iv_module_help_resume';
  var LIST_PAUSE_MS = 1800;

  function parseBoot() {
    var el = document.getElementById('moduleHelpBoot');
    if (!el) return null;
    try {
      return JSON.parse(el.textContent || '{}');
    } catch (e) {
      return null;
    }
  }

  function peekResume() {
    try {
      var raw = sessionStorage.getItem(RESUME_KEY);
      if (!raw) return null;
      var data = JSON.parse(raw);
      if (!data || typeof data.stepIndex !== 'number') return null;
      return data;
    } catch (e) {
      return null;
    }
  }

  function hasResume() {
    return !!peekResume();
  }

  function hasAddResume() {
    // Kept name for AdminAjax callers; any pending resume counts.
    return !!peekResume();
  }

  function stashResume(payload) {
    try {
      sessionStorage.setItem(RESUME_KEY, JSON.stringify(payload));
    } catch (e) {}
  }

  function consumeResume() {
    var data = peekResume();
    try {
      sessionStorage.removeItem(RESUME_KEY);
    } catch (e) {}
    return data;
  }

  var boot = parseBoot();
  if (!boot || !boot.guide_id || !boot.show_url) {
    window.ModuleHelpWizard = {
      hasResume: hasAddResume,
      resumeIfNeeded: function () {}
    };
    return;
  }

  var storageKey = 'iv_module_help_done_' + String(boot.user_id || '0');
  var modalEl = document.getElementById('moduleHelpWizardModal');
  var modal = modalEl && typeof bootstrap !== 'undefined' ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
  if (!window.Shepherd && !modal) {
    window.ModuleHelpWizard = {
      hasResume: hasAddResume,
      resumeIfNeeded: function () {}
    };
    return;
  }
  var steps = [];
  var stepIndex = 0;
  var currentGuide = null;
  var loading = false;
  var skipNextHiddenClear = false;
  var activeTour = null;

  var els = {
    moduleLabel: document.getElementById('mhwModuleLabel'),
    title: document.getElementById('moduleHelpWizardLabel'),
    intro: document.getElementById('mhwIntro'),
    stepCurrent: document.getElementById('mhwStepCurrent'),
    stepTotal: document.getElementById('mhwStepTotal'),
    mediaType: document.getElementById('mhwMediaType'),
    progress: document.getElementById('mhwProgressBar'),
    media: document.getElementById('mhwMedia'),
    placeholder: document.getElementById('mhwMediaPlaceholder'),
    mediaIcon: document.getElementById('mhwMediaIcon'),
    mediaLabel: document.getElementById('mhwMediaLabel'),
    fileWrap: document.getElementById('mhwMediaFileWrap'),
    image: document.getElementById('mhwMediaImage'),
    video: document.getElementById('mhwMediaVideo'),
    embedWrap: document.getElementById('mhwMediaEmbedWrap'),
    embed: document.getElementById('mhwMediaEmbed'),
    caption: document.getElementById('mhwCaption'),
    stepTitle: document.getElementById('mhwStepTitle'),
    stepDesc: document.getElementById('mhwStepDesc'),
    ctaWrap: document.getElementById('mhwCtaWrap'),
    cta: document.getElementById('mhwCta'),
    ctaLabel: document.getElementById('mhwCtaLabel'),
    prev: document.getElementById('mhwPrevBtn'),
    next: document.getElementById('mhwNextBtn')
  };

  function headline(str) {
    return String(str || '')
      .replace(/_/g, ' ')
      .replace(/\b\w/g, function (c) {
        return c.toUpperCase();
      });
  }

  function readDoneIds() {
    try {
      var raw = localStorage.getItem(storageKey);
      var arr = raw ? JSON.parse(raw) : [];
      return Array.isArray(arr) ? arr.map(Number) : [];
    } catch (e) {
      return [];
    }
  }

  function markDone(guideId) {
    var ids = readDoneIds();
    var id = Number(guideId);
    if (!ids.includes(id)) {
      ids.push(id);
      try {
        localStorage.setItem(storageKey, JSON.stringify(ids));
      } catch (e) {}
    }
  }

  function isDone(guideId) {
    return readDoneIds().includes(Number(guideId));
  }

  function clearMedia() {
    els.image.classList.add('d-none');
    els.image.removeAttribute('src');
    els.video.classList.add('d-none');
    els.video.removeAttribute('src');
    if (els.video.load) els.video.load();
    els.embed.src = '';
    els.placeholder.classList.add('d-none');
    els.fileWrap.classList.add('d-none');
    els.embedWrap.classList.add('d-none');
    els.media.classList.add('d-none');
  }

  function renderStep() {
    var total = steps.length || 1;
    var step = steps[stepIndex] || {};
    var type = step.media_type || 'none';

    els.stepCurrent.textContent = String(stepIndex + 1);
    els.stepTotal.textContent = String(total);
    els.progress.style.width = Math.round(((stepIndex + 1) / total) * 100) + '%';
    els.mediaType.textContent = headline(type);
    els.stepTitle.textContent = step.title || '—';
    els.stepDesc.textContent = step.description || '';
    els.caption.textContent = step.media_caption || '';
    els.caption.classList.toggle('d-none', !step.media_caption);

    clearMedia();

    if (type === 'embed' && step.embed_url) {
      els.media.classList.remove('d-none');
      els.embedWrap.classList.remove('d-none');
      els.embed.src = step.embed_url;
    } else if (type === 'image' && step.media_url) {
      els.media.classList.remove('d-none');
      els.fileWrap.classList.remove('d-none');
      els.image.classList.remove('d-none');
      els.image.src = step.media_url;
    } else if (type === 'video' && step.media_url) {
      els.media.classList.remove('d-none');
      els.fileWrap.classList.remove('d-none');
      els.video.classList.remove('d-none');
      els.video.src = step.media_url;
    } else if (type !== 'none') {
      els.media.classList.remove('d-none');
      els.placeholder.classList.remove('d-none');
      els.mediaIcon.className = 'bx ' + (type === 'video' ? 'bx-video' : 'bx-image-alt');
      els.mediaLabel.textContent = 'Media not available';
    }

    els.prev.disabled = stepIndex <= 0;
    var isLast = stepIndex >= total - 1;
    els.next.innerHTML = isLast
      ? 'Done <i class="bx bx-check ms-1"></i>'
      : 'Next <i class="bx bx-right-arrow-alt ms-1"></i>';

    var ctaLabel = step.cta_label || '';
    var ctaUrl = step.cta_url || '';
    var hasCta = !!(ctaLabel && ctaUrl && els.ctaWrap && els.cta);
    if (els.ctaWrap) {
      els.ctaWrap.classList.toggle('d-none', !hasCta);
    }
    if (hasCta) {
      var href = resolveAppHref(ctaUrl);
      els.cta.setAttribute('href', href);
      var external = isExternalHref(href);
      els.cta.setAttribute('target', external ? '_blank' : '_self');
      if (external) {
        els.cta.setAttribute('rel', 'noopener noreferrer');
      } else {
        els.cta.removeAttribute('rel');
      }
      if (els.ctaLabel) {
        els.ctaLabel.textContent = ctaLabel;
      } else {
        els.cta.textContent = ctaLabel;
      }
    }
  }

  function appBasePath() {
    var meta = document.querySelector('meta[name="app-base-path"]');
    return meta ? String(meta.getAttribute('content') || '').replace(/\/$/, '') : '';
  }

  function resolveAppHref(raw) {
    var url = String(raw || '').trim();
    if (!url) return '#';
    if (/^(https?:)?\/\//i.test(url)) {
      try {
        var abs = new URL(url, window.location.origin);
        if (abs.origin === window.location.origin && abs.pathname.indexOf('/admin') === 0) {
          return appBasePath() + abs.pathname + abs.search + abs.hash;
        }
      } catch (e) {}
      return url;
    }
    var path = url.charAt(0) === '/' ? url : '/' + url;
    var base = appBasePath();
    if (base && path.indexOf(base + '/') !== 0 && path !== base) {
      if (path.indexOf('/admin') === 0) {
        return base + path;
      }
    }
    return path;
  }

  function isExternalHref(href) {
    try {
      var u = new URL(href, window.location.origin);
      return u.origin !== window.location.origin;
    } catch (e) {
      return /^(https?:)?\/\//i.test(String(href || ''));
    }
  }

  function sameListPath(href) {
    try {
      var target = new URL(href, window.location.origin);
      return target.pathname === window.location.pathname;
    } catch (e) {
      return false;
    }
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function tourStepHtml(step, index, total) {
    var pct = total > 0 ? Math.round(((index + 1) / total) * 100) : 0;
    var html = '<div class="iv-tour-step"><div class="iv-tour-progress"><span>Step ' + (index + 1) + ' of ' + total + '</span><div class="iv-tour-progress__track"><i style="width:' + pct + '%"></i></div></div>';
    var type = step.media_type || 'none';
    var media = '';
    if (type === 'image' && step.media_url) {
      media = '<img class="iv-tour-media" src="' + escapeHtml(step.media_url) + '" alt="">';
    } else if (type === 'video' && step.media_url) {
      media = '<video class="iv-tour-media" src="' + escapeHtml(step.media_url) + '" controls></video>';
    } else if (type === 'embed' && /^https?:\/\//i.test(String(step.embed_url || ''))) {
      media = '<div class="iv-tour-embed"><iframe src="' + escapeHtml(step.embed_url) + '" title="Step media" allowfullscreen></iframe></div>';
    }
    if (media) {
      html += '<div class="iv-tour-frame">' + media + '</div>';
    }
    if (step.media_caption) {
      html += '<div class="iv-tour-caption">' + escapeHtml(step.media_caption) + '</div>';
    }
    if (step.description) {
      html += '<p>' + escapeHtml(step.description) + '</p>';
    }
    if (step.cta_label && step.cta_url) {
      var href = resolveAppHref(step.cta_url);
      var external = isExternalHref(href);
      html += '<a class="btn btn-sm btn-primary iv-tour-cta" href="' + escapeHtml(href) + '" data-step-index="' + index + '"' +
        (external ? ' target="_blank" rel="noopener noreferrer"' : '') + '>' + escapeHtml(step.cta_label) + '</a>';
    }
    html += '</div>';
    return html;
  }

  function attachFor(step) {
    var selector = String(step.target || '').trim();
    if (!selector || /[<>]/.test(selector)) return null;
    var placement = ['top', 'bottom', 'left', 'right'].indexOf(step.placement) >= 0 ? step.placement : 'bottom';
    try {
      if (!document.querySelector(selector)) return null;
    } catch (e) {
      return null;
    }
    return { element: selector, on: placement };
  }

  function openShepherdTour() {
    if (activeTour) {
      activeTour.cancel();
      activeTour = null;
    }

    var backClass = 'btn btn-sm iv-tour-btn iv-tour-btn--ghost';
    var nextClass = 'btn btn-sm iv-tour-btn iv-tour-btn--next';
    var tour = new Shepherd.Tour({
      useModalOverlay: true,
      defaultStepOptions: {
        cancelIcon: { enabled: true },
        scrollTo: { behavior: 'smooth', block: 'center' },
        classes: 'iv-shepherd',
        modalOverlayOpeningPadding: 8,
        modalOverlayOpeningRadius: 8
      }
    });

    var doneClicked = false;

    steps.forEach(function (step, index) {
      var isFirst = index === 0;
      var isLast = index === steps.length - 1;
      var buttons = [
        {
          text: 'Skip',
          classes: backClass,
          // Skip never finishes the tour — on the last step it just closes it, same as
          // the X icon, so an unfinished tour (skipped all the way through or not) still
          // reopens on the next visit. Only the explicit Done click below marks it seen.
          action: isLast ? tour.cancel : tour.next
        }
      ];
      if (!isFirst) {
        buttons.push({ text: 'Back', classes: backClass, action: tour.back });
      }
      buttons.push({
        text: isLast ? 'Done' : 'Next',
        classes: nextClass,
        action: isLast ? function () {
          doneClicked = true;
          tour.complete();
        } : tour.next
      });

      var options = {
        id: 'iv-help-step-' + index,
        title: step.title || 'How it works',
        text: tourStepHtml(step, index, steps.length),
        buttons: buttons
      };
      var attach = attachFor(step);
      if (attach) options.attachTo = attach;
      tour.addStep(options);
    });

    tour.on('show', function (e) {
      var id = e && e.step && e.step.id ? String(e.step.id) : '';
      var match = id.match(/(\d+)$/);
      if (match) stepIndex = Number(match[1]);
    });
    tour.on('complete', function () {
      activeTour = null;
      if (doneClicked) {
        markDone((currentGuide && currentGuide.id) || boot.guide_id);
      }
      if (window.IvdDashboard && typeof window.IvdDashboard.resizeCharts === 'function') {
        setTimeout(window.IvdDashboard.resizeCharts, 50);
      }
    });
    tour.on('cancel', function () {
      activeTour = null;
      // Closing (the X icon) just hides the tour for now — only finishing it via Done
      // persists the "seen" flag, so an unfinished/closed tour reopens on the next visit.
      if (window.IvdDashboard && typeof window.IvdDashboard.resizeCharts === 'function') {
        setTimeout(window.IvdDashboard.resizeCharts, 50);
      }
    });

    activeTour = tour;
    tour.start();
    if (stepIndex > 0) {
      tour.show('iv-help-step-' + stepIndex);
    }
  }

  function openGuide(guide, atStep) {
    currentGuide = guide;
    steps = Array.isArray(guide.steps) ? guide.steps : [];
    stepIndex = 0;
    if (typeof atStep === 'number' && !isNaN(atStep)) {
      stepIndex = Math.max(0, Math.min(atStep, Math.max(steps.length - 1, 0)));
    }

    if (window.Shepherd && steps.length) {
      openShepherdTour();
      return;
    }

    if (!modal || !els.title) return;

    els.moduleLabel.textContent = (guide.module_label || guide.module_key || 'Module') + ' module';
    els.title.textContent = guide.title || 'How it works';
    els.intro.textContent = guide.intro || '';
    els.intro.classList.toggle('d-none', !guide.intro);

    renderStep();
    modal.show();
  }

  async function fetchAndOpen(atStep) {
    if (loading) return;
    loading = true;
    try {
      var json;
      if (window.AdminAjax && typeof AdminAjax.get === 'function') {
        json = await AdminAjax.get(boot.show_url);
      } else {
        var res = await fetch(boot.show_url, {
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        json = await res.json();
        if (!res.ok || json.success === false) {
          throw new Error(json.message || 'Failed to load guide');
        }
      }
      var guide = json.data || json;
      if (!guide || !guide.id) return;
      openGuide(guide, atStep);
    } catch (e) {
      if (window.AdminAjax && AdminAjax.error) {
        AdminAjax.error(e.message || 'Could not load guide');
      }
    } finally {
      loading = false;
    }
  }

  function resumeIfNeeded() {
    var data = peekResume();
    if (!data) return;
    if (data.guideId && Number(data.guideId) !== Number(boot.guide_id)) {
      return;
    }
    consumeResume();
    fetchAndOpen(data.stepIndex);
  }

  function createHowItWorksButton(className) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'moduleHelpHowBtn';
    btn.className = className;
    btn.innerHTML = '<i class="bx bx-help-circle me-1"></i> How it works';
    btn.addEventListener('click', function () {
      fetchAndOpen();
    });
    return btn;
  }

  function mountHowItWorksButton() {
    if (document.getElementById('moduleHelpHowBtn')) return;

    // Pages without a standard title row (e.g. dashboard) declare where the button goes.
    var slot = document.querySelector('[data-module-help-slot]');
    if (slot) {
      slot.appendChild(
        createHowItWorksButton(slot.getAttribute('data-module-help-btn-class') || 'btn btn-outline-secondary module-help-how-btn')
      );
      slot.classList.remove('d-none');
      return;
    }

    var title = document.querySelector('.admin-page-title');
    if (!title) return;

    var headerRow = title.closest('.d-flex');
    if (!headerRow) return;

    var btn = createHowItWorksButton('btn btn-outline-secondary module-help-how-btn');

    var actions = headerRow.querySelector('[data-module-help-actions]');
    if (!actions) {
      actions = document.createElement('div');
      actions.className = 'd-flex flex-wrap gap-2 align-items-center';
      actions.setAttribute('data-module-help-actions', '1');

      var children = Array.prototype.slice.call(headerRow.children);
      children.slice(1).forEach(function (child) {
        actions.appendChild(child);
      });
      headerRow.appendChild(actions);
    }

    actions.insertBefore(btn, actions.firstChild);
  }

  function hrefOpenMode(href) {
    try {
      var u = new URL(href, window.location.origin);
      var param = (window.AdminAjax && AdminAjax.OPEN_QUERY_PARAM) || 'open';
      if (u.searchParams.get(param) === ((window.AdminAjax && AdminAjax.OPEN_QUERY_EDIT) || 'edit')) {
        return 'edit';
      }
    } catch (e) {
      if (/(?:\?|&)open=edit(?:&|$)/i.test(String(href || ''))) return 'edit';
    }
    try {
      var target = new URL(href, window.location.origin);
      if (target.pathname === window.location.pathname) return 'list';
    } catch (e2) {}
    return 'page';
  }

  function scheduleListResume() {
    setTimeout(function () {
      resumeIfNeeded();
    }, LIST_PAUSE_MS);
  }

  function onCtaClick(e) {
    if (!els.cta || (els.ctaWrap && els.ctaWrap.classList.contains('d-none'))) {
      return;
    }
    var href = els.cta.getAttribute('href');
    if (!href || href === '#') return;
    if (isExternalHref(href)) return;

    var mode = hrefOpenMode(href);
    stashResume({
      guideId: currentGuide && currentGuide.id ? currentGuide.id : boot.guide_id,
      stepIndex: stepIndex,
      mode: mode
    });

    // Same list page: pause then resume, or jump to first Edit page link.
    if (sameListPath(href)) {
      e.preventDefault();
      skipNextHiddenClear = true;
      modal.hide();

      if (mode === 'edit') {
        if (window.AdminAjax && typeof AdminAjax.followFirstEditLink === 'function') {
          AdminAjax.followFirstEditLink();
        }
      } else {
        scheduleListResume();
      }
      return;
    }

    // Different page (e.g. /inquiry/add): allow navigation; help resumes when user returns to list.
  }

  els.prev &&
    els.prev.addEventListener('click', function () {
      if (stepIndex > 0) {
        stepIndex -= 1;
        renderStep();
      }
    });

  els.next &&
    els.next.addEventListener('click', function () {
      if (stepIndex < steps.length - 1) {
        stepIndex += 1;
        renderStep();
      } else {
        if (currentGuide && currentGuide.id) {
          markDone(currentGuide.id);
        } else {
          markDone(boot.guide_id);
        }
        modal.hide();
      }
    });

  document.addEventListener('click', function (e) {
    var link = e.target && e.target.closest ? e.target.closest('.iv-tour-cta') : null;
    if (!link || !activeTour) return;
    var href = link.getAttribute('href');
    if (!href || href === '#' || isExternalHref(href)) return;

    var mode = hrefOpenMode(href);
    stashResume({
      guideId: currentGuide && currentGuide.id ? currentGuide.id : boot.guide_id,
      stepIndex: Number(link.getAttribute('data-step-index') || stepIndex),
      mode: mode
    });

    if (sameListPath(href)) {
      e.preventDefault();
      activeTour.cancel();
      if (mode === 'edit' && window.AdminAjax && typeof AdminAjax.followFirstEditLink === 'function') {
        AdminAjax.followFirstEditLink();
      } else {
        scheduleListResume();
      }
    } else {
      activeTour.cancel();
    }
  });

  if (els.cta) {
    els.cta.addEventListener('click', onCtaClick);
  }

  if (modalEl) {
    modalEl.addEventListener('hidden.bs.modal', function () {
      if (skipNextHiddenClear) {
        skipNextHiddenClear = false;
        return;
      }
      clearMedia();
    });
  }

  window.ModuleHelpWizard = {
    hasResume: hasAddResume,
    resumeIfNeeded: resumeIfNeeded
  };

  document.addEventListener('DOMContentLoaded', function () {
    mountHowItWorksButton();

    if (new URLSearchParams(window.location.search || '').get('onboarding_step')) {
      return;
    }

    var openEdit =
      typeof URLSearchParams !== 'undefined' &&
      new URLSearchParams(window.location.search || '').get(
        (window.AdminAjax && AdminAjax.OPEN_QUERY_PARAM) || 'open'
      ) === ((window.AdminAjax && AdminAjax.OPEN_QUERY_EDIT) || 'edit');

    var resume = peekResume();

    // About to leave for an Edit page — do not auto-open help over the list.
    if (openEdit) {
      return;
    }

    // Returned from Add/Edit page or list CTA pause.
    if (resume) {
      scheduleListResume();
      return;
    }

    if (!isDone(boot.guide_id)) {
      var openLater = function () {
        setTimeout(fetchAndOpen, 350);
      };
      if (document.querySelector('[data-ivd-chart]') && window.IvdDashboard && typeof window.IvdDashboard.whenReady === 'function') {
        window.IvdDashboard.whenReady(openLater, 2500);
      } else {
        setTimeout(fetchAndOpen, 400);
      }
    }
  });
})();
