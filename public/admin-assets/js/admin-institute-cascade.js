/**
 * Institute-scoped cascading dropdowns for admin Add/Edit forms + list filters.
 *
 * Usage on a form:
 *   <form data-institute-cascade>
 *     <select name="institute_id" data-cascade-source></select>
 *     <select data-cascade-target="branches" data-placeholder="Select branch"></select>
 *     <select data-cascade-target="batches" data-placeholder="All Batches"></select>
 *   </form>
 *
 * Optional: data-cascade-url on form overrides base lookup URL
 * Optional: data-keep-value on target to preserve selected id after reload
 * Optional: data-filter-batches-by-branch on form — reloads batches when branch changes
 */
(function () {
  function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  function baseUrl(form) {
    return form.getAttribute('data-cascade-url')
      || document.querySelector('meta[name="admin-lookups-url"]')?.content
      || '';
  }

  function destroySelect2(el) {
    if (window.jQuery && jQuery.fn.select2 && jQuery(el).hasClass('select2-hidden-accessible')) {
      jQuery(el).select2('destroy');
    }
  }

  function refreshSelect2(el) {
    if (window.jQuery && jQuery.fn.select2 && !el.hasAttribute('data-no-search')) {
      var $el = jQuery(el);
      if ($el.hasClass('select2-hidden-accessible')) {
        $el.select2('destroy');
      }
      if (typeof window.initAdminSearchableSelects === 'function') {
        window.initAdminSearchableSelects(el.parentElement || document);
      } else {
        $el.select2({ width: '100%', placeholder: el.dataset.placeholder || '', allowClear: !el.required });
      }
    }
  }

  function uniqueItems(items) {
    var seen = {};
    var out = [];
    (items || []).forEach(function (item) {
      if (!item || item.id === undefined || item.id === null || item.id === '') return;
      var id = String(item.id);
      if (seen[id]) return;
      seen[id] = true;
      out.push(item);
    });
    return out;
  }

  function fillSelect(el, items, keepValue, silent) {
    var placeholder = el.getAttribute('data-placeholder') || el.dataset.placeholder || 'Select';
    var prev = keepValue || el.getAttribute('data-keep-value') || el.value || '';
    destroySelect2(el);
    el.innerHTML = '';
    var opt0 = document.createElement('option');
    opt0.value = '';
    opt0.textContent = placeholder;
    el.appendChild(opt0);
    uniqueItems(items).forEach(function (item) {
      var opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = item.text;
      if (item.branch_id !== undefined && item.branch_id !== null) {
        opt.setAttribute('data-branch-id', item.branch_id);
      }
      if (String(item.id) === String(prev)) {
        opt.selected = true;
      }
      el.appendChild(opt);
    });
    refreshSelect2(el);
    if (!silent) {
      el.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  async function fetchItems(form, type, instituteId, extraParams) {
    var qs = new URLSearchParams(extraParams || {});
    qs.set('institute_id', instituteId);
    var url = baseUrl(form) + '/' + type + '?' + qs.toString();
    var res = await fetch(url, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf()
      }
    });
    var data = await res.json().catch(function () {
      return {};
    });
    if (!res.ok || data.success === false) {
      throw new Error(data.message || 'Lookup failed');
    }
    var raw = data.data;
    if (Array.isArray(raw)) return raw;
    if (raw && typeof raw === 'object') return Object.values(raw);
    return [];
  }

  async function cascadeForm(form) {
    var source = form.querySelector('[data-cascade-source]');
    if (!source) return;
    var instituteId = source.value;
    var targets = form.querySelectorAll('[data-cascade-target]');

    form.__cascadeSeq = (form.__cascadeSeq || 0) + 1;
    var seq = form.__cascadeSeq;

    if (!instituteId) {
      if (seq !== form.__cascadeSeq) return;
      targets.forEach(function (el) {
        fillSelect(el, [], '', true);
      });
      form.dispatchEvent(new CustomEvent('institute-cascade:done', { bubbles: true }));
      return;
    }

    var types = {};
    targets.forEach(function (el) {
      var t = el.getAttribute('data-cascade-target');
      if (t) types[t] = true;
    });

    try {
      var entries = Object.keys(types);
      var results = await Promise.all(
        entries.map(function (type) {
          var extra = {};
          if (type === 'batches') {
            var branchEl = form.querySelector('[name="branch_id"]');
            if (branchEl && branchEl.value) {
              extra.branch_id = branchEl.value;
            }
          }
          return fetchItems(form, type, instituteId, extra).then(function (items) {
            return { type: type, items: items };
          });
        })
      );

      if (seq !== form.__cascadeSeq) return;

      var map = {};
      results.forEach(function (r) {
        map[r.type] = r.items;
      });
      targets.forEach(function (el) {
        var type = el.getAttribute('data-cascade-target');
        fillSelect(el, map[type] || [], el.getAttribute('data-keep-value') || '', true);
      });
      form.dispatchEvent(new CustomEvent('institute-cascade:done', { bubbles: true }));
    } catch (e) {
      if (seq !== form.__cascadeSeq) return;
      console.error(e);
      targets.forEach(function (el) {
        fillSelect(el, [], '', true);
      });
      form.dispatchEvent(new CustomEvent('institute-cascade:done', { bubbles: true }));
    }
  }

  async function reloadBatchesForBranch(form) {
    var source = form.querySelector('[data-cascade-source]');
    var batchEl = form.querySelector('[data-cascade-target="batches"]');
    if (!source || !batchEl || !source.value) {
      if (batchEl) fillSelect(batchEl, [], '', true);
      return;
    }

    form.__batchSeq = (form.__batchSeq || 0) + 1;
    var seq = form.__batchSeq;
    var branchEl = form.querySelector('[name="branch_id"]');
    var extra = {};
    if (branchEl && branchEl.value) {
      extra.branch_id = branchEl.value;
    }

    try {
      var items = await fetchItems(form, 'batches', source.value, extra);
      if (seq !== form.__batchSeq) return;
      fillSelect(batchEl, items, '', true);
    } catch (e) {
      if (seq !== form.__batchSeq) return;
      console.error(e);
      fillSelect(batchEl, [], '', true);
    }
  }

  function onSourceChange(form) {
    form.querySelectorAll('[data-cascade-target]').forEach(function (el) {
      el.removeAttribute('data-keep-value');
    });
    cascadeForm(form);
  }

  function bindBranchBatchFilter(form) {
    if (form.__branchBatchBound) return;
    if (!form.hasAttribute('data-filter-batches-by-branch')) return;
    var branchEl = form.querySelector('[name="branch_id"]');
    if (!branchEl) return;
    form.__branchBatchBound = true;

    var handler = function () {
      var batchEl = form.querySelector('[data-cascade-target="batches"]');
      if (batchEl) batchEl.removeAttribute('data-keep-value');
      reloadBatchesForBranch(form);
    };

    if (window.jQuery) {
      jQuery(branchEl).off('change.filterBatches').on('change.filterBatches', handler);
    } else {
      branchEl.addEventListener('change', handler);
    }
  }

  function bindForm(form) {
    if (form.__cascadeBound) return;
    form.__cascadeBound = true;
    var source = form.querySelector('[data-cascade-source]');
    if (!source) return;
    if (window.jQuery) {
      jQuery(source).off('change.instituteCascade').on('change.instituteCascade', function () {
        onSourceChange(form);
      });
    } else {
      source.addEventListener('change', function () {
        onSourceChange(form);
      });
    }
    bindBranchBatchFilter(form);
    // Initial load for platform (empty until selected) or when institute already chosen
    if (source.value) {
      cascadeForm(form);
    } else {
      form.querySelectorAll('[data-cascade-target]').forEach(function (el) {
        if (!el.options || el.options.length <= 1) {
          fillSelect(el, [], '', true);
        }
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-institute-cascade]').forEach(bindForm);
  });

  window.AdminInstituteCascade = { bind: bindForm, refresh: cascadeForm };
})();
