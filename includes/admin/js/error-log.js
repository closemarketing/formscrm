/**
 * FormsCRM Error Log JavaScript
 *
 * Handles AJAX interactions for error log management.
 */
document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	// Select all checkbox.
	const selectAllCheckbox = document.getElementById('fcrm-select-all-logs');
	if (selectAllCheckbox) {
		selectAllCheckbox.addEventListener('change', function() {
			const isChecked = this.checked;
			document.querySelectorAll('.fcrm-log-checkbox').forEach(checkbox => {
				checkbox.checked = isChecked;
			});
		});
	}

	// Individual checkbox change - deselect "select all" if any unchecked.
	document.addEventListener('change', function(e) {
		if (e.target.classList.contains('fcrm-log-checkbox')) {
			const checkedCount = document.querySelectorAll('.fcrm-log-checkbox:checked').length;
			if (selectAllCheckbox && checkedCount === 0) {
				selectAllCheckbox.checked = false;
			}
		}
	});

	// Bulk action apply button.
	const bulkActionBtn = document.getElementById('fcrm-bulk-action-btn');
	if (bulkActionBtn) {
		bulkActionBtn.addEventListener('click', function() {
			const action = document.getElementById('fcrm-bulk-action-select').value;
			const checkedBoxes = document.querySelectorAll('.fcrm-log-checkbox:checked');

			if (!action) {
				alert(formscrmErrorLog.selectActionMessage || 'Please select an action');
				return;
			}

			if (checkedBoxes.length === 0) {
				alert(formscrmErrorLog.selectLogsMessage || 'Please select at least one log');
				return;
			}

			const logIds = Array.from(checkedBoxes).map(checkbox => checkbox.value);

			if (action === 'delete') {
				const confirmMsg = formscrmErrorLog.confirmBulkDelete.replace('%d', logIds.length);
				if (!confirm(confirmMsg)) {
					return;
				}
				bulkDeleteLogs(logIds);
			} else if (action === 'resend') {
				const confirmMsg = formscrmErrorLog.confirmBulkResend.replace('%d', logIds.length);
				if (!confirm(confirmMsg)) {
					return;
				}
				bulkResendLogs(logIds);
			}
		});
	}

	// Bulk delete logs via AJAX - process in parallel batches for speed.
	function bulkDeleteLogs(logIds) {
		showBulkActionProgress(logIds.length, 'delete');

		let processedCount = 0;
		const batchSize = 5; // Process 5 logs in parallel

		function processBatch() {
			if (processedCount >= logIds.length) {
				// All done.
				updateBulkActionProgressFinal(100, logIds.length, 'delete');
				setTimeout(() => {
					location.reload();
				}, 4000);
				return;
			}

			// Get next batch of log IDs.
			const batchIds = logIds.slice(processedCount, processedCount + batchSize);
			const batchPromises = [];

			// Send all logs in batch in parallel.
			batchIds.forEach(logId => {
				const formData = new FormData();
				formData.append('action', 'formscrm_bulk_delete_logs');
				formData.append('nonce', formscrmErrorLog.nonce);
				formData.append('log_ids[]', logId);

				const promise = fetch(formscrmErrorLog.ajaxurl, {
					method: 'POST',
					body: formData
				})
					.then(response => response.json())
					.catch(error => {
						// Handle error.
					});

				batchPromises.push(promise);
			});

			// Wait for all promises in batch to complete.
			Promise.all(batchPromises).then(() => {
				processedCount += batchSize;

				// Update progress.
				const percentage = Math.round((processedCount / logIds.length) * 100);
				updateBulkActionProgress(percentage, logIds.length, 'delete', processedCount);

				// Process next batch.
				setTimeout(processBatch, 50);
			});
		}

		processBatch();
	}

	// Bulk resend logs via AJAX - send all at once.
	function bulkResendLogs(logIds) {
		showBulkActionProgress(logIds.length, 'resend');

		const formData = new FormData();
		formData.append('action', 'formscrm_bulk_resend_logs');
		formData.append('nonce', formscrmErrorLog.nonce);
		logIds.forEach(logId => formData.append('log_ids[]', logId));

		let lastProgress = 0;
		const progressInterval = setInterval(() => {
			if (lastProgress < 95) {
				lastProgress += Math.random() * 15;
				if (lastProgress > 95) lastProgress = 95;
				const percentage = Math.round(lastProgress);
				updateBulkActionProgress(percentage, logIds.length, 'resend', Math.round((percentage / 100) * logIds.length));
			}
		}, 300);

		fetch(formscrmErrorLog.ajaxurl, {
			method: 'POST',
			body: formData
		})
			.then(response => response.json())
			.then(response => {
				clearInterval(progressInterval);
				if (response.success) {
					const data = response.data;
					updateBulkActionProgressFinal(100, logIds.length, 'resend', data.success, data.failed);
					setTimeout(() => {
						location.reload();
					}, 4000);
				} else {
					clearInterval(progressInterval);
					hideBulkActionProgress();
					alert('Error: ' + (response.data.message || formscrmErrorLog.bulkResendError));
				}
			})
			.catch(error => {
				clearInterval(progressInterval);
				hideBulkActionProgress();
				alert(formscrmErrorLog.ajaxError + ': ' + error);
			});
	}

	// View details toggle.
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('fcrm-view-details-btn')) {
			const logId = e.target.dataset.logId;
			const detailsRow = document.getElementById('fcrm-details-' + logId);

			if (detailsRow) {
				const isVisible = detailsRow.style.display !== 'none';
				if (isVisible) {
					detailsRow.style.display = 'none';
					e.target.textContent = formscrmErrorLog.viewDetails || 'Details';
				} else {
					document.querySelectorAll('.fcrm-log-details').forEach(row => {
						row.style.display = 'none';
					});
					detailsRow.style.display = 'table-row';
					e.target.textContent = formscrmErrorLog.hideDetails || 'Hide';
				}
			}
		}
	});

	// Resend entry.
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('fcrm-resend-btn')) {
			const logId = e.target.dataset.logId;
			const originalText = e.target.textContent;

			if (e.target.disabled) {
				return;
			}

			e.target.disabled = true;
			e.target.textContent = formscrmErrorLog.resending || 'Resending...';

			const formData = new FormData();
			formData.append('action', 'formscrm_resend_entry');
			formData.append('log_id', logId);
			formData.append('nonce', formscrmErrorLog.nonce);

			fetch(formscrmErrorLog.ajaxurl, {
				method: 'POST',
				body: formData
			})
				.then(response => response.json())
				.then(response => {
					if (response.success) {
						alert(response.data.message || 'Entry resent successfully');
						location.reload();
					} else {
						alert('Error: ' + (response.data.message || 'Failed to resend entry'));
					}
				})
				.catch(error => alert('AJAX Error: ' + error))
				.finally(() => {
					e.target.disabled = false;
					e.target.textContent = originalText;
				});
		}
	});

	// Delete log.
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('fcrm-delete-log-btn')) {
			const logId = e.target.dataset.logId;

			if (!confirm(formscrmErrorLog.confirmDelete || 'Are you sure you want to delete this log entry?')) {
				return;
			}

			e.target.disabled = true;

			const formData = new FormData();
			formData.append('action', 'formscrm_delete_log');
			formData.append('log_id', logId);
			formData.append('nonce', formscrmErrorLog.nonce);

			fetch(formscrmErrorLog.ajaxurl, {
				method: 'POST',
				body: formData
			})
				.then(response => response.json())
				.then(response => {
					if (response.success) {
						const row = e.target.closest('tr');
						const detailsRow = document.getElementById('fcrm-details-' + logId);

						if (row) {
							row.remove();
						}
						if (detailsRow) {
							detailsRow.remove();
						}

						const remainingRows = document.querySelectorAll('.fcrm-table tbody tr:not(.fcrm-log-details)').length;
						if (remainingRows === 0) {
							location.reload();
						}
					} else {
						alert('Error: ' + (response.data.message || 'Failed to delete log'));
						e.target.disabled = false;
					}
				})
				.catch(error => {
					alert('AJAX Error: ' + error);
					e.target.disabled = false;
				});
		}
	});

	// Clear all logs.
	const clearAllBtn = document.getElementById('fcrm-clear-all-logs');
	if (clearAllBtn) {
		clearAllBtn.addEventListener('click', function() {
			if (!confirm(formscrmErrorLog.confirmClear)) {
				return;
			}

			const originalText = this.textContent;
			this.disabled = true;
			this.textContent = formscrmErrorLog.clearing || 'Clearing...';

			const formData = new FormData();
			formData.append('action', 'formscrm_clear_all_logs');
			formData.append('nonce', formscrmErrorLog.nonce);

			fetch(formscrmErrorLog.ajaxurl, {
				method: 'POST',
				body: formData
			})
				.then(response => response.json())
				.then(response => {
					if (response.success) {
						location.reload();
					} else {
						alert('Error: ' + (response.data.message || 'Failed to clear logs'));
						this.disabled = false;
						this.textContent = formscrmErrorLog.clearAll || 'Clear All Logs';
					}
				})
				.catch(error => {
					alert('AJAX Error: ' + error);
					this.disabled = false;
					this.textContent = formscrmErrorLog.clearAll || 'Clear All Logs';
				});
		});
	}

	// Show bulk action progress.
	function showBulkActionProgress(total, action) {
		const bulkBtn = document.getElementById('fcrm-bulk-action-btn');
		const bulkStatus = document.getElementById('fcrm-bulk-action-status');
		const statusText = document.getElementById('fcrm-status-text');

		if (bulkBtn) {
			bulkBtn.disabled = true;
		}
		if (bulkStatus) {
			bulkStatus.style.display = 'flex';
		}
		if (statusText) {
			const actionLabel = action === 'resend' ? 'Resending' : 'Deleting';
			statusText.textContent = actionLabel + ' 0 of ' + total + '...';
		}
	}

	// Update bulk action progress.
	function updateBulkActionProgress(percentage, total, action, processed) {
		const progressFill = document.getElementById('fcrm-progress-fill');
		const statusText = document.getElementById('fcrm-status-text');

		if (progressFill) {
			progressFill.style.width = percentage + '%';
		}
		if (statusText) {
			const actionLabel = action === 'resend' ? 'Resending' : 'Deleting';
			statusText.textContent = actionLabel + ' ' + processed + ' of ' + total + '...';
		}
	}

	// Update bulk action progress - final state.
	function updateBulkActionProgressFinal(percentage, total, action, successCount, failCount) {
		const progressFill = document.getElementById('fcrm-progress-fill');
		const statusText = document.getElementById('fcrm-status-text');

		if (progressFill) {
			progressFill.style.width = percentage + '%';
		}
		if (statusText) {
			if (successCount !== undefined && failCount !== undefined) {
				statusText.textContent = 'Terminado: ' + successCount + ' ' + formscrmErrorLog.bulkResendSuccessful + ', ' + failCount + ' ' + formscrmErrorLog.bulkResendFailed;
			} else {
				statusText.textContent = '¡Terminado!';
			}
		}
	}

	// Hide bulk action progress.
	function hideBulkActionProgress() {
		const bulkBtn = document.getElementById('fcrm-bulk-action-btn');
		const bulkStatus = document.getElementById('fcrm-bulk-action-status');

		if (bulkBtn) {
			bulkBtn.disabled = false;
		}
		if (bulkStatus) {
			bulkStatus.style.display = 'none';
		}
	}
});
