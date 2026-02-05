/**
 * FormsCRM Markdown Export JavaScript
 *
 * Handles UI interactions for Markdown export functionality.
 *
 * @package FormsCRM
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		var formSelect = $('#formscrm-form-select');
		var exportTypeSelected = $('#export-type-selected');
		var entrySelection = $('#formscrm-entry-selection');
		var entriesList = $('#formscrm-entries-list');
		var exportForm = $('#formscrm-markdown-export-form');

		// Show/hide entry selection based on export type
		$('input[name="export_type"]').on('change', function() {
			if ($(this).val() === 'selected') {
				entrySelection.slideDown();
				if (formSelect.val()) {
					loadEntries(formSelect.val());
				}
			} else {
				entrySelection.slideUp();
			}
		});

		// Load entries when form is selected
		formSelect.on('change', function() {
			var formId = $(this).val();
			if (formId && exportTypeSelected.is(':checked')) {
				loadEntries(formId);
			}
		});

		// Load entries via AJAX
		function loadEntries(formId) {
			entriesList.html('<p class="fcrm-help-text">' + formscrmMarkdownExport.loading + '</p>');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'formscrm_get_form_entries',
					form_id: formId,
					nonce: formscrmMarkdownExport.nonce
				},
				success: function(response) {
					if (response.success && response.data.entries) {
						renderEntries(response.data.entries);
					} else {
						entriesList.html('<p class="fcrm-notice fcrm-notice-error">' + formscrmMarkdownExport.noEntries + '</p>');
					}
				},
				error: function() {
					entriesList.html('<p class="fcrm-notice fcrm-notice-error">' + formscrmMarkdownExport.ajaxError + '</p>');
				}
			});
		}

		// Render entries as checkboxes
		function renderEntries(entries) {
			if (entries.length === 0) {
				entriesList.html('<p class="fcrm-help-text">' + formscrmMarkdownExport.noEntries + '</p>');
				return;
			}

			var html = '<div class="fcrm-entries-checkboxes">';
			html += '<label class="fcrm-checkbox-label fcrm-select-all"><input type="checkbox" id="select-all-entries"> <strong>' + formscrmMarkdownExport.selectAll + '</strong></label>';
			
			entries.forEach(function(entry) {
				html += '<label class="fcrm-checkbox-label">';
				html += '<input type="checkbox" name="entry_ids[]" value="' + entry.id + '" class="entry-checkbox"> ';
				html += 'Entry #' + entry.id + ' - ' + entry.date;
				if (entry.preview) {
					html += ' <small>(' + entry.preview + ')</small>';
				}
				html += '</label>';
			});
			
			html += '</div>';
			entriesList.html(html);

			// Select all functionality
			$('#select-all-entries').on('change', function() {
				$('.entry-checkbox').prop('checked', $(this).is(':checked'));
			});

			// Update select all state when individual checkboxes change
			$('.entry-checkbox').on('change', function() {
				var allChecked = $('.entry-checkbox:checked').length === $('.entry-checkbox').length;
				$('#select-all-entries').prop('checked', allChecked);
			});
		}

		// Validation before submit
		exportForm.on('submit', function(e) {
			var formId = formSelect.val();
			var exportType = $('input[name="export_type"]:checked').val();

			if (!formId) {
				e.preventDefault();
				alert(formscrmMarkdownExport.selectForm);
				return false;
			}

			if (exportType === 'selected') {
				var selectedEntries = $('.entry-checkbox:checked').length;
				if (selectedEntries === 0) {
					e.preventDefault();
					alert(formscrmMarkdownExport.selectEntries);
					return false;
				}
			}

			return true;
		});
	});
})(jQuery);
