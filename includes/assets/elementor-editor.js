jQuery(document).ready(function($) {
	elementor.channels.editor.on('formscrm:editor:connectCRM', function(e) {
		let $form = $('#formscrm-popup');
		// current widget settings
		let currentSettings = e.options.elementSettingsModel.attributes;

		// get the form fields names
		let formFields = {};

		currentSettings.form_fields.models.map(function( field ) {
			formFields[field.attributes.custom_id] = field.attributes.field_label;
		});

		let crmSettings = {
			fc_crm_type: currentSettings.fc_crm_type,
			fc_crm_url: currentSettings.fc_crm_url,
			fc_crm_username: currentSettings.fc_crm_username,
			fc_crm_password: currentSettings.fc_crm_password,
			fc_crm_apipassword: currentSettings.fc_crm_apipassword,
			fc_crm_apisales: currentSettings.fc_crm_apisales,
			fc_crm_odoodb: currentSettings.fc_crm_odoodb,
		}

		let data = {
			action: 'elementor_formscrm_connect_crm',
			nonce: formcrm_elementor.nonce,
			hiddenSettings: currentSettings.formscrm_settings_hidden,
			formFields,
			crmSettings
		};

		$form.html('Loading...');

		$.ajax({
			type: 'POST',
			url: formcrm_elementor.ajaxurl,
			data: data,
			dataType: 'json',
			success: function(response) {
				console.log('Response:', response);
				$form.html(response.data);

				if ( !$('#fc_crm_module').val() ) {
					// select first
					let firstSelect = $form.find('select').first();
					let firstOption = firstSelect.find('option').first();
					firstSelect.val(firstOption.val());
				}

				$('.elementor-map-table[data-module="'+$('#fc_crm_module').val()+'"]').addClass('active');
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', error);
			}
		});
	});

	// save field settings
	function saveFieldSettings() {
		let $form = $('#formscrm-popup');
		let settings = {};

		$form.find('.active select').each(function() {
			let fieldName = $(this).attr('name');
			let fieldValue = $(this).val();

			if ( fieldValue ) settings[fieldName] = fieldValue;
		});

		let settingsModel = elementor.getPanelView().getCurrentPageView().model;

		// add model
		settings[settingsModel.getSetting('fc_crm_type')] = $('#fc_crm_module').val();

		// Update a specific field programmatically
		settingsModel.setSetting('formscrm_settings_hidden', JSON.stringify(settings));

		// Log the updated settings for verification
		console.log('Updated Settings:', settingsModel.attributes);
	}

	$(document).on('change', '#fc_crm_module', function() {
		let selectedModule = $(this).val();
		$('.elementor-map-table').removeClass('active');
		$('.elementor-map-table[data-module="'+selectedModule+'"]').addClass('active');

		saveFieldSettings();
	});

	$(document).on('change', '.elementor-map-column select', function() {
		saveFieldSettings();
	});
});

