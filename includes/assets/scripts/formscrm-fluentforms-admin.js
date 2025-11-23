/**
 * FormsCRM FluentForms Global Settings
 * Handles contextual fields based on CRM type selection
 */

(function() {
	'use strict';

	var previousCrmType = null;
	var isSaving = false;
	var watcherSetup = false;

	/**
	 * Trigger save action
	 */
	function triggerSave() {
		if (isSaving) {
			return;
		}
		isSaving = true;
		
		// Find save button using multiple strategies.
		var saveBtn = null;
		var container = document.querySelector('[data-settings_key="formscrm"]') ||
		                document.querySelector('.ff_global_integration_form');

		if (container) {
			// Try to find save button in container.
			saveBtn = container.querySelector('button[type="submit"]') ||
			          container.querySelector('.ff-btn-primary') ||
			          container.querySelector('button.el-button--primary');

			// If not found, search in parent elements.
			if (!saveBtn) {
				var parent = container.parentElement;
				for (var i = 0; i < 5 && parent; i++) {
					saveBtn = parent.querySelector('button[type="submit"]') ||
					          parent.querySelector('.ff-btn-primary') ||
					          parent.querySelector('button.el-button--primary');
					if (saveBtn) {
						break;
					}
					parent = parent.parentElement;
				}
			}
		}

		if (saveBtn) {
			saveBtn.click();
			setTimeout(function() {
				isSaving = false;
			}, 3000);
			return true;
		}
		
		isSaving = false;
		return false;
	}

	/**
	 * Setup Vue watcher for CRM type field
	 */
	function setupVueWatcher() {
		if (watcherSetup) {
			return;
		}

		// Find container.
		var container = document.querySelector('[data-settings_key="formscrm"]');
		if (!container) {
			return;
		}

		// Try to find Vue instance by traversing DOM.
		var element = container;
		var vueInstance = null;
		var maxDepth = 10;
		var depth = 0;

		while (element && depth < maxDepth && !vueInstance) {
			// Vue 2.
			if (element.__vue__) {
				vueInstance = element.__vue__;
				break;
			}

			// Vue 3.
			if (element.__vueParentComponent) {
				var setupState = element.__vueParentComponent.setupState;
				if (setupState) {
					vueInstance = setupState;
					break;
				}
			}

			element = element.parentElement;
			depth++;
		}

		// If found, try to watch the settings.
		if (vueInstance) {
			// Try Vue 2 $watch.
			if (vueInstance.$watch && vueInstance.$data && vueInstance.$data.settings) {
				previousCrmType = vueInstance.$data.settings.fc_crm_type || null;
				
				vueInstance.$watch('settings.fc_crm_type', function(newVal, oldVal) {
					if (newVal && newVal !== oldVal && oldVal !== null && oldVal !== undefined) {
						previousCrmType = newVal;
						// Toggle fields visibility immediately.
						toggleFieldsByCrmType(newVal);
						setTimeout(function() {
							triggerSave();
						}, 500);
					}
				});
				
				watcherSetup = true;
				return true;
			}

			// Try Vue 3 watch.
			if (typeof Vue !== 'undefined' && Vue.watch && vueInstance.settings) {
				previousCrmType = vueInstance.settings.fc_crm_type || null;
				
				Vue.watch(function() {
					return vueInstance.settings.fc_crm_type;
				}, function(newVal, oldVal) {
					if (newVal && newVal !== oldVal && oldVal !== null && oldVal !== undefined) {
						previousCrmType = newVal;
						// Toggle fields visibility immediately.
						toggleFieldsByCrmType(newVal);
						setTimeout(function() {
							triggerSave();
						}, 500);
					}
				});
				
				watcherSetup = true;
				return true;
			}
		}

		return false;
	}

	/**
	 * Get current CRM type from readonly input
	 */
	function getCurrentCrmType() {
		var container = document.querySelector('[data-settings_key="formscrm"]');
		if (!container) {
			return null;
		}

		var inputs = container.querySelectorAll('input.el-input__inner[readonly], input[readonly]');
		for (var i = 0; i < inputs.length; i++) {
			var value = inputs[i].value;
			if (value && value !== 'Select' && value.trim() !== '') {
				return value.trim();
			}
		}
		
		return null;
	}

	/**
	 * Polling fallback to check for changes
	 */
	function startPolling() {
		var pollCount = 0;
		var maxPolls = 300; // 5 minutes max.
		
		var pollInterval = setInterval(function() {
			pollCount++;
			if (pollCount > maxPolls) {
				clearInterval(pollInterval);
				return;
			}

			var currentValue = getCurrentCrmType();
			if (currentValue && currentValue !== previousCrmType) {
				if (previousCrmType !== null) {
					previousCrmType = currentValue;
					// Toggle fields visibility immediately.
					toggleFieldsByCrmType(currentValue);
					// Also trigger save to persist the change.
					triggerSave();
				} else {
					previousCrmType = currentValue;
					// Initial load - show/hide fields based on current selection.
					toggleFieldsByCrmType(currentValue);
				}
			}
		}, 1000);
	}

	/**
	 * Show/hide fields based on CRM type (fallback if FluentForms doesn't support dependencies)
	 */
	function toggleFieldsByCrmType(crmType) {
		if (!crmType) {
			return;
		}

		var container = document.querySelector('[data-settings_key="formscrm"]');
		if (!container) {
			return;
		}

		// Get dependencies from PHP via wp_localize_script.
		var dependencies = (window.formscrmAjax && window.formscrmAjax.dependencies) || {};

		// Show/hide fields based on dependencies.
		Object.keys(dependencies).forEach(function(fieldType) {
			var fieldName = 'fc_crm_' + fieldType;
			var fieldElement = container.querySelector('[name="' + fieldName + '"]') || 
			                   container.querySelector('input[data-field="' + fieldName + '"]') ||
			                   container.querySelector('[data-field-name="' + fieldName + '"]');
			
			// Find wrapper element.
			var wrapper = null;
			if (fieldElement) {
				wrapper = fieldElement.closest('.ff-field-wrapper') || 
				          fieldElement.closest('.el-form-item') ||
				          fieldElement.parentElement;
			}
			
			// If not found by field element, try to find by label.
			if (!wrapper) {
				var labels = container.querySelectorAll('label');
				for (var i = 0; i < labels.length; i++) {
					if (labels[i].textContent && labels[i].textContent.toLowerCase().indexOf(fieldName.toLowerCase()) !== -1) {
						wrapper = labels[i].closest('.ff-field-wrapper') || 
						          labels[i].closest('.el-form-item') ||
						          labels[i].parentElement;
						break;
					}
				}
			}
			
			// Show/hide based on dependency.
			if (wrapper && dependencies[fieldType] && Array.isArray(dependencies[fieldType])) {
				if (dependencies[fieldType].indexOf(crmType) !== -1) {
					wrapper.style.display = '';
				} else {
					wrapper.style.display = 'none';
				}
			}
		});
	}

	/**
	 * Initialize
	 */
	function init() {
		// Wait for page and Vue to be ready.
		setTimeout(function() {
			previousCrmType = getCurrentCrmType();

			// Try to setup Vue watcher multiple times.
			var vueAttempts = 0;
			var vueInterval = setInterval(function() {
				vueAttempts++;
				if (setupVueWatcher() || vueAttempts > 20) {
					clearInterval(vueInterval);
				}
			}, 500);

			// Watch for clicks on dropdown items.
			document.addEventListener('click', function(e) {
				var item = e.target.closest('.el-select-dropdown__item');
				if (item) {
					setTimeout(function() {
						var currentValue = getCurrentCrmType();
						if (currentValue && currentValue !== previousCrmType && previousCrmType !== null) {
							previousCrmType = currentValue;
							// Toggle fields visibility immediately.
							toggleFieldsByCrmType(currentValue);
							// Also trigger save to persist the change.
							triggerSave();
						}
					}, 1000);
				}
			}, true);

			// Start polling as fallback.
			startPolling();
		}, 2000);
	}

	// Start when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
