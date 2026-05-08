/**
 * FormsCRM Error Log JavaScript
 *
 * Handles AJAX interactions for error log management.
 */
(function($) {
	'use strict';

	$(document).ready(function() {
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
						location.reload();
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
	});
})(jQuery);
