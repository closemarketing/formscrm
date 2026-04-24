/**
 * FormsCRM Error Log JavaScript
 *
 * Handles AJAX interactions for error log management.
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Date picker initialization.
		const datePickerOptions = {
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			yearRange: '-10:+0'
		};

		// Add locale if available.
		if ($.datepicker && typeof $.datepicker.regional !== 'undefined') {
			const lang = document.documentElement.lang || 'en';
			const langCode = lang.substring(0, 2);
			if ($.datepicker.regional[langCode]) {
				$.extend(datePickerOptions, $.datepicker.regional[langCode]);
			}
		}

		$('#fcrm-export-date-from').datepicker(datePickerOptions);
		$('#fcrm-export-date-to').datepicker(datePickerOptions);

		// Fix datepicker layout via CSS (handled in formscrm-admin.css).
		// No JS manipulation needed, CSS handles all layout.

		// View details toggle.
		$('.fcrm-view-details-btn').on('click', function() {
			const logId = $(this).data('log-id');
			const detailsRow = $('#fcrm-details-' + logId);
			
			if (detailsRow.is(':visible')) {
				detailsRow.hide();
				$(this).text(formscrmErrorLog.viewDetails || 'Details');
			} else {
				// Hide all other details.
				$('.fcrm-log-details').hide();
				detailsRow.show();
				$(this).text(formscrmErrorLog.hideDetails || 'Hide');
			}
		});

		// Resend entry.
		$('.fcrm-resend-btn').on('click', function() {
			const $button = $(this);
			const logId = $button.data('log-id');
			const originalText = $button.text();

			if ($button.prop('disabled')) {
				return;
			}

			$button.prop('disabled', true).text(formscrmErrorLog.resending || 'Resending...');

			$.ajax({
				url: formscrmErrorLog.ajaxurl,
				type: 'POST',
				data: {
					action: 'formscrm_resend_entry',
					log_id: logId,
					nonce: formscrmErrorLog.nonce
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message || 'Entry resent successfully');
						
						// Update status in table.
						const $row = $button.closest('tr');
						$row.find('.fcrm-status')
							.removeClass('fcrm-status-error')
							.addClass('fcrm-status-success')
							.css({
								'background': '#e8f5e9',
								'color': '#2e7d32'
							})
							.text(formscrmErrorLog.successText || 'Success');
						
						// Update attempts count.
						const $attemptsCell = $row.find('td').eq(5);
						const currentAttempts = parseInt($attemptsCell.text());
						$attemptsCell.text(currentAttempts + 1);
					} else {
						alert('Error: ' + (response.data.message || 'Failed to resend entry'));
					}
				},
				error: function(xhr, status, error) {
					alert('AJAX Error: ' + error);
				},
				complete: function() {
					$button.prop('disabled', false).text(originalText);
				}
			});
		});

		// Delete log.
		$('.fcrm-delete-log-btn').on('click', function() {
			const $button = $(this);
			const logId = $button.data('log-id');

			if (!confirm(formscrmErrorLog.confirmDelete || 'Are you sure you want to delete this log entry?')) {
				return;
			}

			$button.prop('disabled', true);

			$.ajax({
				url: formscrmErrorLog.ajaxurl,
				type: 'POST',
				data: {
					action: 'formscrm_delete_log',
					log_id: logId,
					nonce: formscrmErrorLog.nonce
				},
				success: function(response) {
					if (response.success) {
						// Remove the row and details row.
						$button.closest('tr').remove();
						$('#fcrm-details-' + logId).remove();
						
						// Reload page if no more rows.
						if ($('.fcrm-table tbody tr:not(.fcrm-log-details)').length === 0) {
							location.reload();
						}
					} else {
						alert('Error: ' + (response.data.message || 'Failed to delete log'));
						$button.prop('disabled', false);
					}
				},
				error: function(xhr, status, error) {
					alert('AJAX Error: ' + error);
					$button.prop('disabled', false);
				}
			});
		});

		// Clear all logs.
		$('#fcrm-clear-all-logs').on('click', function() {
			const $button = $(this);

			if (!confirm(formscrmErrorLog.confirmClear)) {
				return;
			}

			$button.prop('disabled', true).text(formscrmErrorLog.clearing || 'Clearing...');

			$.ajax({
				url: formscrmErrorLog.ajaxurl,
				type: 'POST',
				data: {
					action: 'formscrm_clear_all_logs',
					nonce: formscrmErrorLog.nonce
				},
				success: function(response) {
					if (response.success) {
						location.reload();
					} else {
						alert('Error: ' + (response.data.message || 'Failed to clear logs'));
						$button.prop('disabled', false).text(formscrmErrorLog.clearAll || 'Clear All Logs');
					}
				},
				error: function(xhr, status, error) {
					alert('AJAX Error: ' + error);
					$button.prop('disabled', false).text(formscrmErrorLog.clearAll || 'Clear All Logs');
				}
			});
		});

		// Export CSV.
		$('#fcrm-export-csv').on('click', function() {
			const $button = $(this);
			const dateFrom = $('#fcrm-export-date-from').val();
			const dateTo = $('#fcrm-export-date-to').val();

			if (!dateFrom || !dateTo) {
				alert('Please select both start and end dates');
				return;
			}

			const originalText = $button.text();
			$button.prop('disabled', true).text(formscrmErrorLog.exporting || 'Exporting...');

			$.ajax({
				url: formscrmErrorLog.ajaxurl,
				type: 'POST',
				data: {
					action: 'formscrm_export_csv',
					date_from: dateFrom,
					date_to: dateTo,
					nonce: formscrmErrorLog.nonce
				},
				success: function(response) {
					if (response.success) {
						// Create blob and download.
						const csvContent = response.data.csv_content;
						const filename = response.data.filename;
						const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
						const link = document.createElement('a');
						link.setAttribute('href', URL.createObjectURL(blob));
						link.setAttribute('download', filename);
						link.style.visibility = 'hidden';
						document.body.appendChild(link);
						link.click();
						document.body.removeChild(link);
					} else {
						alert('Error: ' + (response.data.message || 'Failed to export CSV'));
					}
				},
				error: function(xhr, status, error) {
					alert('AJAX Error: ' + error);
				},
				complete: function() {
					$button.prop('disabled', false).text(originalText);
				}
			});
		});
	});
})(jQuery);
