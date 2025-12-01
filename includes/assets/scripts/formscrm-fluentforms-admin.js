/**
 * FormsCRM FluentForms Global Settings
 * Handles contextual fields based on CRM type selection
 */

(function () {
	'use strict';

var previousCrmType = null;
var isSaving = false;
var watcherSetup = false;
var vueComponentInstance = null;
var vueInstanceType = null;

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
				vueComponentInstance = vueInstance;
				vueInstanceType = 'vue2';
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
				vueComponentInstance = vueInstance;
				vueInstanceType = 'vue3';
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

		// Try 1: Get from Vue instance settings (most reliable)
		var vueInfo = getVueSettingsInfo();
		if (vueInfo && vueInfo.settings && vueInfo.settings.fc_crm_type) {
			return vueInfo.settings.fc_crm_type;
		}

		// Try 2: Check for hidden input with name (standard HTML)
		var hiddenInput = container.querySelector('input[name="fc_crm_type"]');
		if (hiddenInput && hiddenInput.value) {
			return hiddenInput.value.trim();
		}

		// Try 3: Check for Select element (standard HTML)
		var select = container.querySelector('select[name="fc_crm_type"]');
		if (select && select.value) {
			return select.value.trim();
		}

		// Try 4: Check for ElementUI input (visible text) - look for the one with label "CRM Type"
		var inputs = container.querySelectorAll('input.el-input__inner');
		var choices = (window.formscrmAjax && window.formscrmAjax.choices) || [];
		
		for (var i = 0; i < inputs.length; i++) {
			var input = inputs[i];
			var value = input.value;
			
			// Check if this input is the CRM type selector by looking for nearby label
			var parent = input.closest('.el-form-item') || input.closest('.ff-field-wrapper');
			if (parent) {
				var label = parent.querySelector('label');
				if (label && (label.textContent.indexOf('CRM Type') !== -1 || label.textContent.indexOf('CRM') !== -1)) {
					// This is likely the CRM type selector
					if (value && value !== 'Select' && value.trim() !== '') {
						// Try to match with choices
						for (var j = 0; j < choices.length; j++) {
							if (choices[j].label === value) {
								return choices[j].value; // Return the value, not the label
							}
							if (choices[j].value === value) {
								return value.trim();
							}
						}
						// If no match found but value exists, return it
						return value.trim();
					}
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
	 * Get mapping for toggle keys (used by FluentForms dependencies).
	 */
	function getToggleMap() {
		return (window.formscrmAjax && window.formscrmAjax.toggles) || {};
	}

	/**
	 * Returns Vue instance + settings info if available.
	 */
	function getVueSettingsInfo() {
		if (!vueComponentInstance) {
			return null;
		}

		if ('vue2' === vueInstanceType && vueComponentInstance.$data && vueComponentInstance.$data.settings) {
			return {
				instance: vueComponentInstance,
				settings: vueComponentInstance.$data.settings,
				type: 'vue2'
			};
		}

		if ('vue3' === vueInstanceType && vueComponentInstance.settings) {
			return {
				instance: vueComponentInstance,
				settings: vueComponentInstance.settings,
				type: 'vue3'
			};
		}

		return null;
	}

	/**
	 * Apply toggle values so FluentForms dependency engine can react.
	 */
	function applyVueToggles(visibilityMap) {
		var toggleMap = getToggleMap();
		var vueInfo = getVueSettingsInfo();

		if (!toggleMap || !vueInfo) {
			return;
		}

		Object.keys(toggleMap).forEach(function (fieldType) {
			var toggleKey = toggleMap[fieldType];
			if (!toggleKey) {
				return;
			}

			var isVisible = !!visibilityMap[fieldType];
			var value = isVisible ? 'show' : 'hide';

			if ('vue2' === vueInfo.type && typeof vueInfo.instance.$set === 'function') {
				vueInfo.instance.$set(vueInfo.settings, toggleKey, value);
			} else {
				vueInfo.settings[toggleKey] = value;
			}
		});
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
		var visibilityMap = {};

		Object.keys(dependencies).forEach(function (fieldType) {
			visibilityMap[fieldType] = false;
		});

		applyVueToggles(visibilityMap);
		applyDomVisibility(container, visibilityMap);
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
		var visibilityMap = {};

		// Map label to value if needed.
		var crmValue = crmType;
		if (choices.length > 0 && crmType) {
			for (var i = 0; i < choices.length; i++) {
				if (choices[i].label === crmType || choices[i].value === crmType) {
					crmValue = choices[i].value;
					break;
				}
			}
		}

		Object.keys(dependencies).forEach(function (fieldType) {
			var allowed = dependencies[fieldType] || [];
			visibilityMap[fieldType] = crmValue && allowed.indexOf(crmValue) !== -1;
		});

		applyVueToggles(visibilityMap);
		applyDomVisibility(container, visibilityMap);
	}

	/**
	 * Apply DOM visibility as a defensive fallback.
	 */
	function applyDomVisibility(container, visibilityMap) {
		if (!container) {
			return;
		}

		Object.keys(visibilityMap).forEach(function (fieldType) {
			var wrapper = findFieldWrapper(container, fieldType);
			if (!wrapper) {
				return;
			}

			if (visibilityMap[fieldType]) {
				wrapper.style.display = '';
			} else {
				wrapper.style.display = 'none';
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
				// Get the value from the clicked item
				var itemValue = item.getAttribute('data-value') || item.textContent.trim();
				var choices = (window.formscrmAjax && window.formscrmAjax.choices) || [];
				
				// Try to match with choices to get the actual value
				for (var i = 0; i < choices.length; i++) {
					if (choices[i].label === itemValue || choices[i].value === itemValue) {
						itemValue = choices[i].value;
						break;
					}
				}
				
				setTimeout(function () {
					var currentValue = getCurrentCrmType() || itemValue;
					if (currentValue && currentValue !== previousCrmType) {
						previousCrmType = currentValue;
						toggleFieldsByCrmType(currentValue);
						// Small delay before save to ensure Vue has updated
						setTimeout(function () {
							triggerSave();
						}, 300);
					}
				}, 200); // Reduced delay since we have the value from click
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

		// Watch for Element UI input readonly changes (MutationObserver for value changes)
		var inputObserver = new MutationObserver(function (mutations) {
			var currentValue = getCurrentCrmType();
			if (currentValue && currentValue !== previousCrmType) {
				previousCrmType = currentValue;
				toggleFieldsByCrmType(currentValue);
				triggerSave();
			}
		});

		// Observe all readonly inputs in the container
		function observeReadonlyInputs() {
			var container = document.querySelector(containerSelector);
			if (!container) {
				return;
			}
			
			var readonlyInputs = container.querySelectorAll('input.el-input__inner[readonly]');
			readonlyInputs.forEach(function (input) {
				// Observe value attribute changes
				inputObserver.observe(input, {
					attributes: true,
					attributeFilter: ['value'],
					subtree: true
				});
				// Also observe parent for changes
				var parent = input.closest('.el-form-item') || input.closest('.ff-field-wrapper');
				if (parent) {
					inputObserver.observe(parent, {
						childList: true,
						subtree: true
					});
				}
			});
		}

		// Initial observation
		setTimeout(observeReadonlyInputs, 1000);
		
		// Re-observe when container changes
		var containerObserver = new MutationObserver(function () {
			observeReadonlyInputs();
		});
		var container = document.querySelector(containerSelector);
		if (container) {
			containerObserver.observe(container, {
				childList: true,
				subtree: true
			});
		}
	}

	// Start when DOM is ready.
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
