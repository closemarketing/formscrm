<?php

namespace FormsCRM\Forms\FluentForms;

use FluentForm\App\Http\Controllers\IntegrationManagerController;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;

class Bootstrap extends IntegrationManagerController {
  public $hasGlobalMenu = false;

  public $disableGlobalSettings = 'yes';

  public function __construct( Application $app ) {
		parent::__construct(
			$app,
			'FormsCRM',
			'formscrm',
			'_fluentform_formscrm_settings',
			'formscrm_feeds',
			99
		);

		$this->logo = FORMSCRM_PLUGIN_URL . 'includes/assets/addon-icon-wpforms.png';

		$this->description = __('Connect FormsCRM with WP Fluent Forms and subscribe a contact when a form is submitted.', 'ffformscrm');

		$this->registerAdminHooks();
  }

	public function getGlobalFields( $fields ) {
			return [
					'logo'             => $this->logo,
					'menu_title'       => __('FormsCRM Settings', 'formscrm'),
					'menu_description' => __('FormsCRM is a marketing platform for small businesses. Send beautiful emails, connect your e-commerce store, advertise, and build your brand. Use Fluent Forms to collect customer information and automatically add it to your FormsCRM campaign list. If you don\'t have a FormsCRM account, you can <a href="https://formscrm.com/" target="_blank">sign up for one here.</a>', 'formscrm'),
					'valid_message'    => __('Your FormsCRM API Key is valid', 'formscrm'),
					'invalid_message'  => __('Your FormsCRM API Key is not valid', 'formscrm'),
					'save_button_text' => __('Save Settings', 'formscrm'),
					'fields'           => [
							'apiKey' => [
									'type'       => 'text',
									'label_tips' => __('Enter your FormsCRM API Key, if you do not have <br>Please login to your FormsCRM account and go to<br>Profile -> Extras -> Api Keys', 'formscrm'),
									'label'      => __('FormsCRM API Key', 'formscrm'),
							],
					],
					'hide_on_valid'    => true,
					'discard_settings' => [
							'section_description' => __('Your FormsCRM API integration is up and running', 'formscrm'),
							'button_text'         => __('Disconnect FormsCRM', 'formscrm'),
							'data'                => [
									'apiKey' => '',
							],
							'show_verify' => true,
					],
			];
	}

	public function getGlobalSettings($settings)
	{
			$globalSettings = get_option($this->optionKey);
			if (! $globalSettings) {
					$globalSettings = [];
			}
			$defaults = [
					'apiKey' => '',
					'status' => '',
			];

			return wp_parse_args($globalSettings, $defaults);
	}

	public function saveGlobalSettings($formsCRM)
	{
			if (! $formsCRM['apiKey']) {
					$formsCRMSettings = [
							'apiKey' => '',
							'status' => false,
					];
					// Update the reCaptcha details with siteKey & secretKey.
					update_option($this->optionKey, $formsCRMSettings, 'no');
					wp_send_json_success([
							'message' => __('Your settings has been updated and disconnected', 'formscrm'),
							'status'  => false,
					], 200);
			}

			// Verify API key now
			try {
					$MailChimp = new MailChimp($formsCRM['apiKey']);
					$result = $MailChimp->get('lists');
					if (! $MailChimp->success()) {
							throw new \Exception($MailChimp->getLastError());
					}
			} catch (\Exception $exception) {
					wp_send_json_error([
							'message' => $exception->getMessage(),
					], 400);
			}

			// FormsCRM key is verified now, Proceed now

			$formsCRMSettings = [
					'apiKey' => sanitize_text_field($formsCRM['apiKey']),
					'status' => true,
			];

			// Update the reCaptcha details with siteKey & secretKey.
			update_option($this->optionKey, $formsCRMSettings, 'no`');

			wp_send_json_success([
					'message' => __('Your formscrm api key has been verified and successfully set', 'formscrm'),
					'status'  => true,
			], 200);
	}
    public function pushIntegration( $integrations, $formId ) {
			$integrations[ $this->integrationKey ] = [
				'title' => $this->title . ' Integration',
				'logo' => $this->logo,
				'is_active' => $this->isConfigured(),
				'configure_title' => __('Configuration required!', 'ffformscrm'),
				'global_configure_url'  => admin_url('admin.php?page=fluent_forms_settings#general-formscrm-settings'),
				'configure_message' => __('FormsCRM is not configured yet! Please configure your FormsCRM first', 'ffformscrm'),
				'configure_button_text' => __('Set FormsCRM', 'ffformscrm')
			];
			return $integrations;
    }

    public function getIntegrationDefaults($settings, $formId)
    {
        return [
            'name' => '',
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'other_fields' => [
                [
                    'label' => '',
                    'item_value' => ''
                ]
            ],
            'list_id' => '',
            'send_confirmation_email' => false,
            'conditionals' => [
                'conditions' => [],
                'status' => false,
                'type' => 'all'
            ],
            'enabled' => true
        ];
    }

    public function getSettingsFields($settings, $formId)
    {
        return [
            'fields' => [
                [
                    'key' => 'name',
                    'label' => __('Feed Name', 'ffformscrm'),
                    'required' => true,
                    'placeholder' => __('Your Feed Name', 'ffformscrm'),
                    'component' => 'text'
                ],
                [
                    'key' => 'list_id',
                    'label' => __('FormsCRM List', 'ffformscrm'),
                    'placeholder' => __('Select FormsCRM List', 'ffformscrm'),
                    'tips' => __('Select the FormsCRM List you would like to add your contacts to.', 'ffformscrm'),
                    'component' => 'select',
                    'required' => true,
                    'options' => $this->getLists(),
                ],
                [
                    'key' => 'CustomFields',
                    'require_list' => false,
                    'label' => __('Primary Fields', 'ffformscrm'),
                    'tips' => __('Associate your FormsCRM merge tags to the appropriate Fluent Form fields by selecting the appropriate form field from the list.', 'ffformscrm'),
                    'component' => 'map_fields',
                    'field_label_remote' => __('FormsCRM Field', 'ffformscrm'),
                    'field_label_local' => __('Form Field', 'ffformscrm'),
                    'primary_fileds' => [
                        [
                            'key' => 'email',
                            'label' => __('Email Address', 'ffformscrm'),
                            'required' => true,
                            'input_options' => 'emails'
                        ],
                        [
                            'key' => 'first_name',
                            'label' => __('First Name', 'ffformscrm')
                        ],
                        [
                            'key' => 'last_name',
                            'label' => __('Last Name', 'ffformscrm')
                        ]
                    ]
                ],
                [
                    'key' => 'other_fields',
                    'require_list' => false,
                    'label' => __('Custom Fields', 'ffformscrm'),
                    'tips' => __('Select which Fluent Form fields pair with their respective FormsCRM fields. Checkbox fields must contain true/false value like Terms&Condition/GDPR field. Date fields supports values for year_month_day: MM/DD/YYYY, DD/MM/YYYY, YYYY/MM/DD, for year_month: YYYY/M, MM/YY, for year: YYYY, for month: MM types', 'ffformscrm'),
                    'component' => 'dropdown_many_fields',
                    'field_label_remote' => __('FormsCRM Field', 'ffformscrm'),
                    'field_label_local' => __('Form Field', 'ffformscrm'),
                    'options' => $this->getCustomFields()
                ],
                [
                    'key' => 'send_confirmation_email',
                    'require_list' => false,
                    'tips' => __('User needed to log out to send confirmation email feature to work','ffformscrm'),
                    'checkbox_label' => __('Send Confirmation Email', 'ffformscrm'),
                    'component' => 'checkbox-single'
                ],
                [
                    'require_list' => false,
                    'key' => 'conditionals',
                    'label' => __('Conditional Logics', 'ffformscrm'),
                    'tips' => __('Allow FormsCRM integration conditionally based on your submission values', 'ffformscrm'),
                    'component' => 'conditional_block'
                ],
                [
                    'require_list' => false,
                    'key' => 'enabled',
                    'label' => __('Status', 'ffformscrm'),
                    'component' => 'checkbox-single',
                    'checkbox_label' => __('Enable This feed', 'ffformscrm')
                ]
            ],
            'button_require_list' => false,
            'integration_title' => $this->title
        ];
    }

    public function getMergeFields($list, $listId, $formId)
    {
        return [];
    }

    protected function getCustomFields()
    {
        $api = $this->getApi();
        $customFields = $api->getSubscriberFields();

        $fields = [];
        foreach ($customFields as $customField) {
            $id = Arr::get($customField, 'id');
            if (strpos($id, 'cf') !== false) {
                $name = Arr::get($customField, 'name');
                $type = Arr::get($customField, 'type');
                if ($type) {
                    $type = '_**_' . $type;
                }
                if ($id && $name && $type) {
                    $fields[$customField['id'] . $type] = $customField['name'];
                }
            }
        }
        return $fields;
    }


    protected function getLists()
    {
        $api = $this->getApi();
        $lists = $api->getLists();
        $formattedLists = [];
        foreach ($lists as $list) {
            $formattedLists[$list['id']] = $list['name'];
        }

        return $formattedLists;
    }


    /*
     * Form Submission Hooks Here
     */
    public function notify($feed, $formData, $entry, $form)
    {
        $data = $feed['processedValues'];
        $contact = Arr::only($data, ['first_name', 'last_name', 'email']);

        if (!is_email($contact['email'])) {
            $contact['email'] = Arr::get($formData, $contact['email']);
        }

        foreach (Arr::get($data, 'other_fields') as $field) {
            if ($field['item_value']) {
                if (strpos($field['label'], '_**_') !== false) {
                    $fieldDetails = explode('_**_', $field['label']);
                    $fieldId = $fieldDetails[0];
                    $fieldType = $fieldDetails[1];
                    $value = $field['item_value'];
                    if ($fieldType == 'checkbox') {
                        if ($value == 'yes' || $value == 'on' || $value == 'true' || $value == '1' || $value == 'Accepted') {
                            $value = true;
                        }
                    }
                    $contact[$fieldId] = $value;
                }
            }
        }

        if (!is_email($contact['email'])) {
            do_action_deprecated(
                'ff_integration_action_result',
                [
                    $feed,
                    'info',
                    'FormsCRM API called skipped because no valid email available'
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/integration_action_result',
                'Use fluentform/integration_action_result instead of ff_integration_action_result.'
            );
            do_action('fluentform/integration_action_result', $feed, 'info', 'FormsCRM API called skipped because no valid email available');
            return;
        }

        $api = $this->getApi();

        try {
            $subscriber = $api->getSubscriber($contact['email']);
            if ($subscriber) {
                $message = __('Contact creation has been skipped because contact already exist at FormsCRM.', 'ffformscrm');
                do_action_deprecated(
                    'ff_integration_action_result',
                    [
                        $feed,
                        'info',
                        $message
                    ],
                    FLUENTFORM_FRAMEWORK_UPGRADE,
                    'fluentform/integration_action_result',
                    'Use fluentform/integration_action_result instead of ff_integration_action_result.'
                );
                do_action('fluentform/integration_action_result', $feed, 'info', $message);
                return;
            }
        } catch (\Exception $exception) {

        }

        try {
            $options = [
                'skip_subscriber_notification' => true,
                'send_confirmation_email' => Arr::isTrue($data, 'send_confirmation_email')
            ];

            $subscriber = $api->addSubscriber($contact, [
                Arr::get($data, 'list_id')
            ], $options);

            $message = __('Contact has been created in FormsCRM. Contact ID: ', 'ffformscrm');

            do_action_deprecated(
                'ff_integration_action_result',
                [
                    $feed,
                    'success',
                    $message . $subscriber['id']
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/integration_action_result',
                'Use fluentform/integration_action_result instead of ff_integration_action_result.'
            );

            do_action('fluentform/integration_action_result', $feed, 'success', $message . $subscriber['id']);
        } catch (\Exception $exception) {
            do_action_deprecated(
                'ff_integration_action_result',
                [
                    $feed,
                    'failed',
                    $exception->getMessage()
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/integration_action_result',
                'Use fluentform/integration_action_result instead of ff_integration_action_result.'
            );

            do_action('fluentform/integration_action_result', $feed, 'failed', $exception->getMessage());
        }
    }

    protected function getApi()
    {
        return \FormsCRM\API\API::MP('v1');
    }


    public function isConfigured()
    {
        return true;
    }

    public function isEnabled()
    {
        return true;
    }
}
