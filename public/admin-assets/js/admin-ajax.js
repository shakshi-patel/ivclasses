/**
 * Shared AJAX + centered SweetAlert helpers for admin CRUD.
 *
 * AdminAjax.get/post/put/destroy(url, data?)
 * AdminAjax.confirm({ title, text, confirmText, icon }) → Promise<boolean>
 * AdminAjax.success(message, title?)
 * AdminAjax.error(message, title?)
 * AdminAjax.removeRow(tr) — removes row and renumbers Sr column
 * AdminAjax.renumberSr(table) — refresh Sr. No. after DOM changes
 * AdminAjax.setStatusPill(cellOrRow, isActive)
 * AdminAjax.applyStatusToggle(btn, isActive) — pill + toggle icon/title
 * AdminAjax.statusFrom(response) — read status from Ajax payload
 * AdminAjax.submitForm(form, { redirectUrl })
 * AdminAjax.clearFieldErrors(root?) — remove is-invalid + field error text in a form/modal
 * AdminAjax.showFieldErrors(errors, fieldMap, { root?, fallback? }) — map Laravel errors to fields
 * AdminAjax.escapeHtml(value) — escape user text before innerHTML / attribute interpolation
 * AdminAjax.followFirstEditLink(selector?) — ?open=edit on list → first Edit page link
 */
window.AdminAjax = (function () {
  var OPEN_QUERY_PARAM = 'open';
  var OPEN_QUERY_EDIT = 'edit';

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function swalAvailable() {
    return typeof window.Swal !== 'undefined';
  }

  async function request(method, url, data) {
    var options = {
      method: method,
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrf(),
        'X-Requested-With': 'XMLHttpRequest'
      }
    };

    // Some Apache/IIS setups reject native PUT/PATCH/DELETE (HTTP 405).
    // Always spoof via POST so Laravel MethodField middleware can handle it.
    var spoof = method === 'PUT' || method === 'PATCH' || method === 'DELETE';

    if (data !== undefined && data !== null) {
      if (data instanceof FormData) {
        if (spoof) {
          data.append('_method', method);
          options.method = 'POST';
        }
        options.body = data;
      } else {
        var payload = Object.assign({}, data);
        if (spoof) {
          payload._method = method;
          options.method = 'POST';
          options.headers['X-HTTP-Method-Override'] = method;
        }
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
      }
    } else if (spoof) {
      options.method = 'POST';
      options.headers['Content-Type'] = 'application/json';
      options.headers['X-HTTP-Method-Override'] = method;
      options.body = JSON.stringify({ _method: method });
    }

    var res = await fetch(url, options);
    var raw = await res.text();
    var json;
    try {
      json = raw ? JSON.parse(raw) : {};
    } catch (e) {
      var hint = res.status === 419
        ? 'Session expired. Please refresh the page and try again.'
        : res.status === 403
          ? 'You do not have permission for this action.'
          : res.status === 404
            ? 'URL not found. Check APP_URL / site base path.'
            : 'Server returned a non-JSON response (HTTP ' + res.status + ').';
      json = { success: false, message: hint };
    }

    if (!res.ok || json.success === false) {
      var msg = json.message || 'Request failed';
      if (json.errors) {
        var flat = Object.values(json.errors).flat();
        if (flat.length) msg = flat.join('\n');
      }
      var err = new Error(msg);
      err.status = res.status;
      err.errors = json.errors || null;
      err.payload = json;
      throw err;
    }

    return json;
  }

  function success(message, title) {
    if (!swalAvailable()) {
      alert(message || 'Success');
      return Promise.resolve();
    }
    return Swal.fire({
      icon: 'success',
      title: title || 'Success',
      text: message || '',
      confirmButtonText: 'OK',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  function error(message, title) {
    if (!swalAvailable()) {
      alert(message || 'Error');
      return Promise.resolve();
    }
    return Swal.fire({
      icon: 'error',
      title: title || 'Error',
      text: message || 'Something went wrong',
      confirmButtonText: 'OK',
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  function confirm(opts) {
    opts = opts || {};
    if (!swalAvailable()) {
      return Promise.resolve(window.confirm(opts.text || opts.title || 'Are you sure?'));
    }
    return Swal.fire({
      icon: opts.icon || 'warning',
      title: opts.title || 'Are you sure?',
      text: opts.text || '',
      showCancelButton: true,
      confirmButtonText: opts.confirmText || 'Yes',
      cancelButtonText: opts.cancelText || 'Cancel',
      customClass: {
        confirmButton: 'btn btn-primary me-2',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false,
      reverseButtons: true
    }).then(function (result) {
      return !!result.isConfirmed;
    });
  }

  function renumberSr(table) {
    if (!table) return;
    var $ = window.jQuery;
    if ($ && $.fn.DataTable && $.fn.DataTable.isDataTable($(table))) {
      var dt = $(table).DataTable();
      var start = 0;
      try {
        start = dt.page.info().start || 0;
      } catch (e) {
        start = 0;
      }
      var visible = 0;
      dt.rows({ page: 'current', search: 'applied' }).every(function () {
        var node = this.node();
        if (!node) return;
        if (node.querySelector('td[colspan]')) return;
        var cell = node.querySelector('[data-role="sr"]') || (node.cells && node.cells[0]);
        if (!cell) return;
        visible += 1;
        cell.textContent = String(start + visible);
      });
      return;
    }

    var tbody = table.tBodies && table.tBodies[0];
    if (!tbody) return;
    var n = 0;
    tbody.querySelectorAll('tr').forEach(function (tr) {
      if (tr.querySelector('td[colspan]')) return;
      var cell = tr.querySelector('[data-role="sr"]') || (tr.cells && tr.cells[0]);
      if (!cell) return;
      // Skip action-only / empty placeholder rows with no numeric sr intent
      if (cell.getAttribute('colspan')) return;
      n += 1;
      cell.textContent = String(n);
    });
  }

  function removeRow(tr) {
    if (!tr) return;
    var table = tr.closest('table');
    var $ = window.jQuery;
    if ($ && $.fn.DataTable) {
      var $table = $(tr).closest('table');
      if ($.fn.DataTable.isDataTable($table)) {
        $table.DataTable().row(tr).remove().draw(false);
        renumberSr($table[0]);
        return;
      }
    }
    tr.remove();
    if (table) renumberSr(table);
  }

  function findStatusPill(scope) {
    if (!scope) return null;
    if (scope.classList && scope.classList.contains('admin-status-pill') && scope.getAttribute('data-role') === 'status-pill') {
      return scope;
    }
    var marked = scope.querySelector ? scope.querySelector('[data-role="status-pill"]') : null;
    if (marked) return marked;
    if (!scope.querySelectorAll) return null;
    var pills = scope.querySelectorAll('.admin-status-pill');
    for (var i = 0; i < pills.length; i++) {
      var text = (pills[i].textContent || '').trim().toLowerCase();
      if (text === 'active' || text === 'inactive') {
        return pills[i];
      }
    }
    return pills.length === 1 ? pills[0] : null;
  }

  function setStatusPill(scope, isActive) {
    var pill = findStatusPill(scope);
    if (!pill && scope && scope.tagName === 'TR') {
      // legacy: first pill whose parent is a TD (avoid type pills when marked)
      pill = findStatusPill(scope);
    }
    if (!pill) return;
    pill.textContent = isActive ? 'Active' : 'Inactive';
    pill.classList.toggle('is-active', !!isActive);
    pill.classList.toggle('is-muted', !isActive);
    pill.setAttribute('data-role', 'status-pill');
  }

  function applyStatusToggle(btn, isActive) {
    var active = !!isActive;
    if (btn) {
      var tr = btn.closest ? btn.closest('tr') : null;
      if (tr) setStatusPill(tr, active);
      var icon = btn.querySelector ? btn.querySelector('i') : null;
      if (icon) {
        icon.className = 'bx ' + (active ? 'bx-user-x' : 'bx-user-check');
      }
      btn.title = active ? 'Deactivate' : 'Activate';
      btn.dataset.active = active ? '1' : '0';
      if (btn.dataset.status !== undefined) {
        btn.dataset.status = active ? 'active' : 'inactive';
      }
    }
    return active;
  }

  function statusFrom(response) {
    var payload = response && response.data !== undefined ? response.data : response;
    if (!payload) return '';
    if (typeof payload === 'string') return payload;
    if (payload.status) return String(payload.status);
    if (payload.data && payload.data.status) return String(payload.data.status);
    return '';
  }

  function resolveRoot(root) {
    if (!root) return document;
    if (typeof root === 'string') return document.querySelector(root) || document;
    return root;
  }

  function clearFieldErrors(root) {
    var scope = resolveRoot(root);
    if (!scope.querySelectorAll) return;
    scope.querySelectorAll('.is-invalid').forEach(function (el) {
      el.classList.remove('is-invalid');
    });
    scope.querySelectorAll('[data-field-error]').forEach(function (el) {
      el.textContent = '';
      el.classList.remove('d-block');
    });
    if (scope.id && /Error$/i.test(scope.id)) {
      scope.hidden = true;
      scope.textContent = '';
    }
    var fallback = scope.querySelector ? scope.querySelector('[data-form-error]') : null;
    if (fallback) {
      fallback.hidden = true;
      fallback.textContent = '';
    }
  }

  /**
   * @param {Record<string, string[]|string>|null} errors
   * @param {Record<string, string|Element>} fieldMap  Laravel key → input selector/element
   * @param {{ root?: string|Element, fallback?: string|Element }} options
   */
  function showFieldErrors(errors, fieldMap, options) {
    options = options || {};
    var scope = resolveRoot(options.root);
    clearFieldErrors(scope);
    if (!errors || typeof errors !== 'object') return false;

    var leftover = [];
    var shown = false;

    Object.keys(errors).forEach(function (key) {
      var messages = errors[key];
      var msg = Array.isArray(messages) ? messages[0] : messages;
      if (!msg) return;

      var target = fieldMap && fieldMap[key];
      var input = null;
      if (typeof target === 'string') {
        input = scope.querySelector ? scope.querySelector(target) : document.querySelector(target);
      } else if (target && target.nodeType) {
        input = target;
      }

      if (!input) {
        leftover.push(String(msg));
        return;
      }

      input.classList.add('is-invalid');
      var wrap = input.closest('.mb-3, .mb-0, .field, .col-md-6, .col-12') || input.parentElement;
      var feedback = wrap
        ? wrap.querySelector('[data-field-error="' + key + '"], [data-field-error]')
        : null;
      if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.setAttribute('data-field-error', key);
        input.insertAdjacentElement('afterend', feedback);
      }
      feedback.setAttribute('data-field-error', key);
      feedback.textContent = String(msg);
      feedback.classList.add('d-block');
      shown = true;
    });

    if (leftover.length) {
      var fb = options.fallback;
      if (typeof fb === 'string') fb = document.querySelector(fb);
      if (!fb && scope.querySelector) fb = scope.querySelector('[data-form-error]');
      if (fb) {
        fb.hidden = false;
        fb.textContent = leftover.join('\n');
        shown = true;
      }
    }

    return shown;
  }

  async function submitForm(form, options) {
    options = options || {};
    var url = form.getAttribute('action');
    var method = (form.querySelector('input[name="_method"]')?.value || form.method || 'POST').toUpperCase();
    var fd = new FormData(form);
    try {
      var json = await request(method === 'GET' ? 'POST' : method, url, fd);
      await success(json.message || 'Saved successfully.');
      var setupUrl = '';
      try { setupUrl = sessionStorage.getItem('iv_onboarding_dashboard') || ''; } catch (e) {}
      if (setupUrl) {
        try { sessionStorage.removeItem('iv_onboarding_dashboard'); } catch (e) {}
        window.location.href = setupUrl;
        return json;
      }
      if (options.redirectUrl) {
        window.location.href = options.redirectUrl;
      } else if (options.reload) {
        window.location.reload();
      }
      return json;
    } catch (e) {
      await error(e.message);
      throw e;
    }
  }

  /**
   * Page-only deep-link: ?open=edit → navigate to the first Edit page link in the list.
   * (Does not open modals.)
   */
  function openFromQuery(handlers, options) {
    options = options || {};
    var map = handlers;
    if (typeof handlers === 'function') {
      map = { edit: handlers };
    }
    if (!map || typeof map !== 'object') {
      return false;
    }

    var clean = options.clean !== false;
    var params;
    try {
      params = new URLSearchParams(window.location.search || '');
    } catch (e) {
      return false;
    }

    var value = params.get(OPEN_QUERY_PARAM);
    if (value !== OPEN_QUERY_EDIT) {
      return false;
    }

    var onMatch = map.edit;
    if (typeof onMatch !== 'function') {
      return false;
    }

    try {
      onMatch();
    } catch (e) {
      return false;
    }

    if (clean && window.history && typeof window.history.replaceState === 'function') {
      params.delete(OPEN_QUERY_PARAM);
      var qs = params.toString();
      var next = window.location.pathname + (qs ? '?' + qs : '') + (window.location.hash || '');
      window.history.replaceState({}, '', next);
    }

    return true;
  }

  /**
   * Follow the first Edit action link (page URL) in the list table.
   */
  function followFirstEditLink(selector) {
    var sel =
      selector ||
      '.admin-list-table a.admin-action-icon[href*="/edit"], .admin-list-table a[href*="/edit"]';
    var link = document.querySelector(sel);
    if (!link || !link.getAttribute('href')) {
      if (typeof error === 'function') {
        error('Nothing to edit yet. Add a record first.');
      }
      if (window.ModuleHelpWizard && typeof ModuleHelpWizard.resumeIfNeeded === 'function') {
        setTimeout(function () {
          ModuleHelpWizard.resumeIfNeeded();
        }, 400);
      }
      return false;
    }
    window.location.href = link.href;
    return true;
  }

  document.addEventListener('DOMContentLoaded', function () {
    openFromQuery({
      edit: function () {
        followFirstEditLink();
      }
    });
  });

  // Flash messages from Laravel session → center Swal
  document.addEventListener('DOMContentLoaded', function () {
    var flash = document.getElementById('admin-flash-success');
    if (flash && flash.textContent.trim()) {
      success(flash.textContent.trim());
      flash.remove();
    }
    var flashErr = document.getElementById('admin-flash-error');
    if (flashErr && flashErr.textContent.trim()) {
      error(flashErr.textContent.trim());
      flashErr.remove();
    }
  });

  function leaveForSetup() {
    var url = '';
    try { url = sessionStorage.getItem('iv_onboarding_dashboard') || ''; } catch (e) {}
    if (!url) return false;
    try { sessionStorage.removeItem('iv_onboarding_dashboard'); } catch (e) {}
    window.location.href = url;
    return true;
  }

  return {
    get: function (url) {
      return request('GET', url);
    },
    post: function (url, data) {
      return request('POST', url, data === undefined ? {} : data);
    },
    put: function (url, data) {
      return request('PUT', url, data === undefined ? {} : data);
    },
    destroy: function (url) {
      return request('DELETE', url);
    },
    request: request,
    success: success,
    error: error,
    confirm: confirm,
    leaveForSetup: leaveForSetup,
    removeRow: removeRow,
    renumberSr: renumberSr,
    setStatusPill: setStatusPill,
    applyStatusToggle: applyStatusToggle,
    statusFrom: statusFrom,
    submitForm: submitForm,
    clearFieldErrors: clearFieldErrors,
    showFieldErrors: showFieldErrors,
    escapeHtml: escapeHtml,
    openFromQuery: openFromQuery,
    followFirstEditLink: followFirstEditLink,
    OPEN_QUERY_PARAM: OPEN_QUERY_PARAM,
    OPEN_QUERY_EDIT: OPEN_QUERY_EDIT
  };
})();
