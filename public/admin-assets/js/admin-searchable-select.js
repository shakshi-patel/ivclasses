/**
 * Common searchable Select2 for all admin form-select dropdowns.
 * Opt-out: data-no-search / class "no-search-select"
 * Re-init: window.initAdminSearchableSelects(rootElement)
 */
'use strict';

(function ($) {
  function placeholderText($el) {
    var custom = $el.data('placeholder');
    if (custom) return custom;

    var empty = $el.find('option[value=""]').first().text();
    if (empty && $.trim(empty) !== '') return $.trim(empty);

    return 'Search & select';
  }

  function shouldSkip($el) {
    if (!$el.length || !$el.is('select')) return true;
    if ($el.data('no-search') === true || $el.data('no-search') === '') return true;
    if ($el.hasClass('no-search-select')) return true;
    if ($el.hasClass('select2-hidden-accessible')) return true;
    if ($el.closest('.dataTables_length, .dataTables_filter, .dt-length, .dt-search, .select2').length) {
      return true;
    }
    // Native multi listboxes (size attribute) stay as-is
    if ($el.prop('multiple') && $el.attr('size')) return true;
    return false;
  }

  window.initAdminSearchableSelects = function (root) {
    if (!$.fn || !$.fn.select2) return;

    var $scope = root ? $(root) : $(document);
    var $selects = $scope.find('select.form-select');

    if ($scope.is('select.form-select')) {
      $selects = $selects.add($scope);
    }

    $selects.each(function () {
      var $el = $(this);
      if (shouldSkip($el)) return;

      var $wrap = $el.parent();
      if (!$wrap.hasClass('admin-select-wrap')) {
        $el.wrap('<div class="admin-select-wrap"></div>');
        $wrap = $el.parent();
      }

      var $modal = $el.closest('.modal');
      var dropdownParent = $modal.length ? $modal : $wrap;

      $el.select2({
        width: '100%',
        placeholder: placeholderText($el),
        allowClear: $el.data('allow-clear') === true || $el.data('allow-clear') === '',
        dropdownParent: dropdownParent,
        minimumResultsForSearch: 0,
        closeOnSelect: !$el.prop('multiple')
      });
    });
  };

  $(function () {
    window.initAdminSearchableSelects();
  });

  // After Bootstrap modal opens, ensure search focus works
  $(document).on('shown.bs.modal', '.modal', function () {
    window.initAdminSearchableSelects(this);
  });
})(jQuery);
