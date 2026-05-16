/**
 * Admin Dashboard JavaScript
 */

(function ($) {
  'use strict';

  const NCP_Admin = {
    init: function () {
      this.bindEvents();
      this.loadAnalytics();
    },

    bindEvents: function () {
      $('#ncp-export-logs').on('click', this.exportLogs.bind(this));
      $('#ncp-clear-logs').on('click', this.clearLogs.bind(this));
      $('#ncp-refresh-analytics').on('click', this.loadAnalytics.bind(this));
    },

    exportLogs: function (e) {
      e.preventDefault();

      const nonce = ncpAdmin?.nonce || '';
      const format = 'json';

      window.location.href =
        ncpAdmin?.ajaxUrl +
        '?action=ncp_export_log&format=' +
        format +
        '&nonce=' +
        nonce;
    },

    clearLogs: function (e) {
      e.preventDefault();

      if (!confirm(ncpAdmin?.i18n?.confirm_delete || 'Are you sure?')) {
        return;
      }

      $.ajax({
        url: ncpAdmin?.ajaxUrl || '/wp-admin/admin-ajax.php',
        type: 'POST',
        data: {
          action: 'ncp_clear_log',
          nonce: ncpAdmin?.nonce || '',
        },
        success: function (response) {
          if (response.success) {
            alert(ncpAdmin?.i18n?.success || 'Operation successful');
            location.reload();
          }
        },
        error: function () {
          alert(ncpAdmin?.i18n?.error || 'An error occurred');
        },
      });
    },

    loadAnalytics: function (e) {
      if (e) {
        e.preventDefault();
      }

      const dateFrom = $('#ncp-date-from').val() || '';
      const dateTo = $('#ncp-date-to').val() || '';

      $.ajax({
        url: ncpAdmin?.ajaxUrl || '/wp-admin/admin-ajax.php',
        type: 'POST',
        data: {
          action: 'ncp_analytics',
          date_from: dateFrom,
          date_to: dateTo,
          nonce: ncpAdmin?.nonce || '',
        },
        success: function (response) {
          if (response.success) {
            NCP_Admin.displayAnalytics(response.data);
          }
        },
        error: function () {
          console.error('Failed to load analytics');
        },
      });
    },

    displayAnalytics: function (data) {
      // Update cards
      $('.ncp-stat-value').each(function () {
        const key = $(this).closest('.ncp-analytics-card').data('key');
        if (data.analytics && data.analytics[key]) {
          $(this).text(
            NCP_Admin.formatNumber(data.analytics[key])
          );
        }
      });
    },

    formatNumber: function (num) {
      if (typeof num !== 'number') {
        return 0;
      }
      return num.toLocaleString('en-US');
    },
  };

  // Initialize
  $(document).ready(function () {
    NCP_Admin.init();

    // Auto-refresh analytics every 30 seconds
    setInterval(function () {
      const activeTab = $('.nav-tab-active').attr('data-tab');
      if (activeTab === 'analytics') {
        NCP_Admin.loadAnalytics();
      }
    }, 30000);

    // Color picker
    if (typeof wp.customize !== 'undefined') {
      // WordPress color picker
      $('input[type="color"]').wpColorPicker();
    }
  });

  window.NCapAdmin = NCP_Admin;
})(jQuery);
