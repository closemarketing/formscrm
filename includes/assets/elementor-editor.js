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

		// Include every connection setting registered by FormsCRM or an add-on.
		// Integrations such as Redsys use their own credentials (FUC, terminal and
		// SHA key), rather than the generic URL/API key fields.
		let crmSettings = {};
		Object.keys( currentSettings ).forEach( function( key ) {
			if ( key.indexOf( 'fc_crm_' ) === 0 && key.indexOf( 'fc_crm_field-' ) !== 0 ) {
				crmSettings[ key ] = currentSettings[ key ];
			}
		} );

		let data = {
			action: 'elementor_formscrm_connect_crm',
			nonce: formcrm_elementor.nonce,
			hiddenSettings: currentSettings.formscrm_settings_hidden,
			formFields,
			crmSettings
		};

		// Show loading state.
		$('#formscrm-connection-status').html(
			'<div style="padding: 12px; background: #f9f9f9; border-left: 4px solid #0073aa; border-radius: 4px;">' +
			'<div style="display: flex; align-items: center; gap: 8px;">' +
			'<strong style="color: #23282d;">' + 'API Connection Status:' + '</strong> ' +
			'<span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 3px; background: #0073aa; color: white; font-size: 12px; font-weight: bold;">' +
			'<span style="margin-right: 5px;">⟳</span>' + 'Connecting...' +
			'</span>' +
			'</div></div>'
		);
		$form.html('<p style="text-align: center; padding: 20px; color: #666;">Loading...</p>');

		$.ajax({
			type: 'POST',
			url: formcrm_elementor.ajaxurl,
			data: data,
			dataType: 'json',
			success: function(response) {
				console.log('Response:', response);
				
				if (response.success) {
					// Update connection status.
					if (response.data.status_html) {
						$('#formscrm-connection-status').html(response.data.status_html);
					}

					// Update form content with modules and fields.
					$form.html(response.data.form_html);

					if ( !$('#fc_crm_module').val() ) {
						// select first
						let firstSelect = $form.find('select').first();
						let firstOption = firstSelect.find('option').first();
						firstSelect.val(firstOption.val());
					}

					$('.elementor-map-table[data-module="'+$('#fc_crm_module').val()+'"]').addClass('active');
				} else {
					// Handle error response.
					// Update connection status with error HTML if available.
					if (response.data && response.data.status_html) {
						$('#formscrm-connection-status').html(response.data.status_html);
					} else {
						let errorMessage = response.data && response.data.message ? response.data.message : (response.data || 'Connection failed');
						
						// Show error in status indicator.
						$('#formscrm-connection-status').html(
							'<div style="padding: 12px; background: #ffebee; border-left: 4px solid #dc3232; border-radius: 4px;">' +
							'<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">' +
							'<strong style="color: #23282d;">API Connection Status:</strong> ' +
							'<span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 3px; background: #dc3232; color: white; font-size: 12px; font-weight: bold;">' +
							'<span style="margin-right: 5px;">✕</span>Error' +
							'</span>' +
							'</div>' +
							'<p style="margin: 0; color: #dc3232; font-size: 12px;"><strong>Error:</strong> ' + errorMessage + '</p>' +
							'</div>'
						);
					}
					$form.html('');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', error);
				// Show error in status indicator.
				$('#formscrm-connection-status').html(
					'<div style="padding: 12px; background: #ffebee; border-left: 4px solid #dc3232; border-radius: 4px;">' +
					'<div style="display: flex; align-items: center; gap: 8px;">' +
					'<strong style="color: #23282d;">API Connection Status:</strong> ' +
					'<span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 3px; background: #dc3232; color: white; font-size: 12px; font-weight: bold;">' +
					'<span style="margin-right: 5px;">✕</span>Error' +
					'</span>' +
					'</div>' +
					'<p style="margin: 8px 0 0 0; color: #dc3232; font-size: 12px;"><strong>Error:</strong> Network error - ' + error + '</p>' +
					'</div>'
				);
				$form.html('');
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

		$('.elementor-control-fc_crm_apipassword').find('input').trigger('input');
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

	function hasConnectionCredentials( settingsModel ) {
		let crmType = settingsModel.getSetting( 'fc_crm_type' );

		if ( crmType === 'redsys' ) {
			return !! (
				settingsModel.getSetting( 'fc_crm_fuc' ) &&
				settingsModel.getSetting( 'fc_crm_terminal' ) &&
				settingsModel.getSetting( 'fc_crm_sha_secret' )
			);
		}

		return !! ( crmType && settingsModel.getSetting( 'fc_crm_apipassword' ) );
	}

	function connectWhenCredentialsAreReady() {
		let settingsModel = elementor.getPanelView().getCurrentPageView().model;

		if ( hasConnectionCredentials( settingsModel ) ) {
			$('[data-event="formscrm:editor:connectCRM"]').click();
		}
	}

	elementor.channels.editor.on('section:activated', function (panel) {
		if( panel !== 'section_formscrm' ) return;

		connectWhenCredentialsAreReady();
	});

	$('body').on('change', '[data-setting="fc_crm_type"], [data-setting="fc_crm_fuc"], [data-setting="fc_crm_terminal"], [data-setting="fc_crm_sha_secret"], [data-setting="fc_crm_apipassword"]', function() {
		// Elementor updates its settings model after the control change event.
		window.setTimeout( connectWhenCredentialsAreReady, 0 );
	});
});
