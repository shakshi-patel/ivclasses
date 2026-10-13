/**
 * Restricts [data-phone-input] fields to digits only — typing, paste, and
 * autofill alike. Uses event delegation so dynamically added/cloned fields
 * (e.g. new branch rows) are covered automatically with no re-init call.
 */
(function () {
  function sanitize(el) {
    var digits = (el.value || '').replace(/\D+/g, '');
    var max = el.getAttribute('maxlength');
    if (max) digits = digits.slice(0, parseInt(max, 10));
    if (el.value !== digits) el.value = digits;
  }

  document.addEventListener('input', function (e) {
    if (e.target && e.target.matches && e.target.matches('[data-phone-input]')) {
      sanitize(e.target);
    }
  });
})();
