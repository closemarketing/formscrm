/**
 * FormsCRM FluentForms Global Settings
 * Handles contextual fields based on CRM type selection
 */

(function () {
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
			setTimeout(function () {
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

				vueInstance.$watch('settings.fc_crm_type', function (newVal, oldVal) {
					if (newVal && newVal !== oldVal) {
						previousCrmType = newVal;
						// Toggle fields visibility immediately.
						toggleFieldsByCrmType(newVal);
						setTimeout(function () {
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

				Vue.watch(function () {
					return vueInstance.settings.fc_crm_type;
				}, function (newVal, oldVal) {
					if (newVal && newVal !== oldVal) {
						previousCrmType = newVal;
						// Toggle fields visibility immediately.
						toggleFieldsByCrmType(newVal);
						setTimeout(function () {
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
	 * Get current CRM type from readonly input or select
	 */
	function getCurrentCrmType() {
		var container = document.querySelector('[data-settings_key="formscrm"]');
		if (!container) {
			return null;
		}

		// Try 1: Check for hidden input with name (standard HTML)
		var hiddenInput = container.querySelector('input[name="fc_crm_type"]');
		if (hiddenInput && hiddenInput.value) {
			return hiddenInput.value.trim();
		}

		// Try 2: Check for Select element (standard HTML)
		var select = container.querySelector('select[name="fc_crm_type"]');
		if (select && select.value) {
			return select.value.trim();
		}

		// Try 3: Check for ElementUI input (visible text)
		// We prioritize the one that is NOT empty and NOT 'Select'
		var inputs = container.querySelectorAll('input.el-input__inner');
		for (var i = 0; i < inputs.length; i++) {
			var value = inputs[i].value;
			if (value && value !== 'Select' && value.trim() !== '') {
				// Verify if this input looks like the CRM selector (e.g. by checking siblings or parent)
				// For now, we assume the first valid value in the container is the CRM type if it matches a choice
				var choices = (window.formscrmAjax && window.formscrmAjax.choices) || [];
				var isMatch = false;
				for (var j = 0; j < choices.length; j++) {
					if (choices[j].label === value || choices[j].value === value) {
						isMatch = true;
						break;
					}
				}

				if (isMatch) {
					return value.trim();
				}
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

		var pollInterval = setInterval(function () {
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
	 * Get localized dependency map.
	 */
	function getDependencies() {
		return (window.formscrmAjax && window.formscrmAjax.dependencies) || {};
	}

	/**
	 * Resolve field wrapper for a dependency-controlled input.
	 */
	function findFieldWrapper(container, fieldType) {
		if (!container) {
			return null;
		}

		var fieldName = 'fc_crm_' + fieldType;
		var fieldElement = container.querySelector('[name="' + fieldName + '"]') ||
			container.querySelector('input[data-field="' + fieldName + '"]') ||
			container.querySelector('[data-field-name="' + fieldName + '"]');

		var wrapper = null;
		if (fieldElement) {
			wrapper = fieldElement.closest('.ff-field-wrapper') ||
				fieldElement.closest('.el-form-item') ||
				fieldElement.parentElement;
		}

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

		return wrapper;
	}

	/**
	 * Hide every dependency-controlled field.
	 */
	function hideDependentFields() {
		var container = document.querySelector('[data-settings_key="formscrm"]');
		if (!container) {
			return;
		}

		var dependencies = getDependencies();
		Object.keys(dependencies).forEach(function (fieldType) {
			var wrapper = findFieldWrapper(container, fieldType);
			if (wrapper) {
				wrapper.style.display = 'none';
			}
		});
	}

	/**
	 * Show/hide fields based on CRM type (fallback if FluentForms doesn't support dependencies)
	 */
	function toggleFieldsByCrmType(crmType) {
		var container = document.querySelector('[data-settings_key="formscrm"]');
		if (!container) {
			return;
		}

		var dependencies = getDependencies();
		var choices = (window.formscrmAjax && window.formscrmAjax.choices) || [];

		// Always reset visibility before applying CRM-specific logic.
		Object.keys(dependencies).forEach(function (fieldType) {
			var wrapper = findFieldWrapper(container, fieldType);
			if (wrapper) {
				wrapper.style.display = 'none';
			}
		});

		if (!crmType) {
			return;
		}

		// Map label to value if needed.
		var crmValue = crmType;
		if (choices.length > 0) {
			for (var i = 0; i < choices.length; i++) {
				if (choices[i].label === crmType || choices[i].value === crmType) {
					crmValue = choices[i].value;
					break;
				}
			}
		}

		// Show/hide fields based on dependencies.
		Object.keys(dependencies).forEach(function (fieldType) {
			var wrapper = findFieldWrapper(container, fieldType);

			// Show/hide based on dependency.
			if (wrapper && dependencies[fieldType] && Array.isArray(dependencies[fieldType])) {
				if (dependencies[fieldType].indexOf(crmValue) !== -1) {
					wrapper.style.display = '';
				} else {
					wrapper.style.display = 'none';
				}
			}
		});
	}

	/**
	 * Initialize with MutationObserver
	 */
	function init() {
		var containerSelector = '[data-settings_key="formscrm"]';
		var observer = new MutationObserver(function (mutations) {
			var container = document.querySelector(containerSelector);
			if (container) {
				// Container found, initialize logic.
				if (!previousCrmType) {
					previousCrmType = getCurrentCrmType();
					hideDependentFields();
					if (previousCrmType) {
						toggleFieldsByCrmType(previousCrmType);
					}
				}

				// If we haven't setup the Vue watcher yet, try to.
				if (!watcherSetup) {
					setupVueWatcher();
				}
			}
		});

		// Start observing the document body for added nodes.
		observer.observe(document.body, {
			childList: true,
			subtree: true
		});

		// Also run once immediately in case it's already there.
		var container = document.querySelector(containerSelector);
		if (container) {
			previousCrmType = getCurrentCrmType();
			hideDependentFields();
			if (previousCrmType) {
				toggleFieldsByCrmType(previousCrmType);
			}
			setupVueWatcher();
		}

		// Watch for clicks on dropdown items (delegated).
		document.addEventListener('click', function (e) {
			var item = e.target.closest('.el-select-dropdown__item');
			if (item) {
				setTimeout(function () {
					var currentValue = getCurrentCrmType();
					if (currentValue && currentValue !== previousCrmType) {
						previousCrmType = currentValue;
						toggleFieldsByCrmType(currentValue);
						triggerSave();
					}
				}, 500); // Increased delay slightly to ensure Vue updates.
			}
		}, true);

		// Watch for standard select changes.
		document.addEventListener('change', function (e) {
			if (e.target && e.target.name === 'fc_crm_type') {
				var currentValue = e.target.value;
				if (currentValue !== previousCrmType) {
					previousCrmType = currentValue;
					toggleFieldsByCrmType(currentValue);
				}
			}
		});
	}

	// Start when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
