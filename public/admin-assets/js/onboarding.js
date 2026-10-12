/**
 * First-login setup highlight. Opened from the dashboard Continue / Set up link
 * via ?onboarding_step={id}. Does not mark the step done — saving the record does.
 */
(function () {
  var params = new URLSearchParams(window.location.search || '');
  var stepId = params.get('onboarding_step');
  var boot = window.IV_ONBOARDING || {};
  var FLAG = 'iv_onboarding_dashboard';

  if (!stepId) {
    if (/\/dashboard\/?$/.test(window.location.pathname || '')) {
      try { sessionStorage.removeItem(FLAG); } catch (e) {}
    }
    return;
  }

  if (boot.dashboardUrl) {
    try { sessionStorage.setItem(FLAG, boot.dashboardUrl); } catch (e) {}
  }

  if (!boot.stepBase) return;

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  var clearing = false;
  function removeOverlays() {
    if (clearing) return;
    clearing = true;
    try {
      if (window.Shepherd && Shepherd.activeTour) {
        try { Shepherd.activeTour.cancel(); } catch (err) {}
      }
      document.querySelectorAll('.shepherd-modal-overlay-container').forEach(function (el) {
        el.remove();
      });
      document.body.classList.remove('shepherd-modal-is-visible');
      document.body.style.overflow = '';
    } finally {
      clearing = false;
    }
  }

  function showBar(dashboardUrl, text) {
    if (document.querySelector('.iv-onboarding-bar')) return;
    var bar = document.createElement('div');
    bar.className = 'iv-onboarding-bar';
    bar.innerHTML =
      '<span>' + escapeHtml(text) + '</span>' +
      '<a href="' + escapeHtml(dashboardUrl || boot.dashboardUrl || '/') + '">Back to dashboard</a>';
    var host = document.querySelector('.content-wrapper') || document.body;
    host.insertBefore(bar, host.firstChild);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var ticks = 0;
    var timer = setInterval(function () {
      removeOverlays();
      ticks += 1;
      if (ticks > 8) clearInterval(timer);
    }, 250);

    fetch(boot.stepBase.replace(/\/$/, '') + '/' + encodeURIComponent(stepId), {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (res) {
        if (!res.ok) throw new Error('unavailable');
        return res.json();
      })
      .then(function (json) {
        var data = json && json.data ? json.data : null;
        if (!data) throw new Error('unavailable');

        showBar(
          data.dashboard_url,
          data.already
            ? 'This is already saved. Add another if you want, then return to the dashboard.'
            : (data.required
              ? 'Save this record to finish the compulsory step, then return to the dashboard.'
              : 'Save this record when you are ready, then return to the dashboard.')
        );

        // Do not open a second popup here. The page's own How it works tour
        // uses a full-screen overlay; together they freeze the screen.
        if (window.Shepherd && Shepherd.activeTour) {
          try { Shepherd.activeTour.cancel(); } catch (err) {}
        }
        removeOverlays();
      })
      .catch(function () {
        showBar(boot.dashboardUrl, 'This setup step is already finished.');
      });
  });
})();
