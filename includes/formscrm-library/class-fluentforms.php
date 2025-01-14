<?php

namespace FormsCRM\Forms\FluentForms;

use FluentForm\App\Http\Controllers\IntegrationManagerController;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper;

class FluentFormsIntegration extends IntegrationManagerController
{
    public function __construct(Application $application)
    {
        parent::__construct(
            $application,
            'FormsCRM',
            'formscrm',
            '_fluentform_formscrm_details',
            'formscrm_feeds',
            12
        );

        $this->description = __('Fluent Forms FormsCRM module allows you to create FormsCRM newsletter signup forms in WordPress', 'fluentform');

        $this->logo = fluentFormMix('img/integrations/formscrm.png');
        $this->registerAdminHooks();

        add_action('wp_ajax_fluentform_formscrm_interest_groups', [$this, 'fetchInterestGroups']);

        add_filter('fluentform/save_integration_value_formscrm', [$this, 'sanitizeSettings'], 10, 3);
    }

    public function getGlobalFields($fields)
    {
        return [
            'logo'             => $this->logo,
            'menu_title'       => __('FormsCRM Settings', 'fluentform'),
            'menu_description' => __('FormsCRM is a marketing platform for small businesses. Send beautiful emails, connect your e-commerce store, advertise, and build your brand. Use Fluent Forms to collect customer information and automatically add it to your FormsCRM campaign list. If you don\'t have a FormsCRM account, you can <a href="https://formscrm.com/" target="_blank">sign up for one here.</a>', 'fluentform'),
            'valid_message'    => __('Your FormsCRM API Key is valid', 'fluentform'),
            'invalid_message'  => __('Your FormsCRM API Key is not valid', 'fluentform'),
            'save_button_text' => __('Save Settings', 'fluentform'),
            'fields'           => [
                'apiKey' => [
                    'type'       => 'text',
                    'label_tips' => __('Enter your FormsCRM API Key, if you do not have <br>Please login to your FormsCRM account and go to<br>Profile -> Extras -> Api Keys', 'fluentform'),
                    'label'      => __('FormsCRM API Key', 'fluentform'),
                ],
            ],
            'hide_on_valid'    => true,
            'discard_settings' => [
                'section_description' => __('Your FormsCRM API integration is up and running', 'fluentform'),
                'button_text'         => __('Disconnect FormsCRM', 'fluentform'),
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

    public function saveGlobalSettings($mailChimp)
    {
        if (! $mailChimp['apiKey']) {
            $mailChimpSettings = [
                'apiKey' => '',
                'status' => false,
            ];
            // Update the reCaptcha details with siteKey & secretKey.
            update_option($this->optionKey, $mailChimpSettings, 'no');
            wp_send_json_success([
                'message' => __('Your settings has been updated and disconnected', 'fluentform'),
                'status'  => false,
            ], 200);
        }

        // Verify API key now
        try {
            $MailChimp = new MailChimp($mailChimp['apiKey']);
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

        $mailChimpSettings = [
            'apiKey' => sanitize_text_field($mailChimp['apiKey']),
            'status' => true,
        ];

        // Update the reCaptcha details with siteKey & secretKey.
        update_option($this->optionKey, $mailChimpSettings, 'no`');

        wp_send_json_success([
            'message' => __('Your formscrm api key has been verified and successfully set', 'fluentform'),
            'status'  => true,
        ], 200);
    }

    public function pushIntegration($integrations, $formId)
    {
        $integrations['formscrm'] = [
            'title'                 => __('FormsCRM Feed', 'fluentform'),
            'logo'                  => $this->logo,
            'is_active'             => $this->isConfigured(),
            'configure_title'       => __('Configuration required!', 'fluentform'),
            'global_configure_url'  => admin_url('admin.php?page=fluent_forms_settings#general-formscrm-settings'),
            'configure_message'     => __('FormsCRM is not configured yet! Please configure your formscrm api first', 'fluentform'),
            'configure_button_text' => __('Set FormsCRM API', 'fluentform'),
        ];

        return $integrations;
    }

    public function getIntegrationDefaults($settings, $formId)
    {
        $settings = [
            'conditionals' => [
                'conditions' => [],
                'status'     => false,
                'type'       => 'all',
            ],
            'enabled'                => true,
            'list_id'                => '',
            'list_name'              => '',
            'name'                   => '',
            'merge_fields'           => (object) [],
            'tags'                   => '',
            'tag_routers'            => [],
            'tag_ids_selection_type' => 'simple',
            'markAsVIP'              => false,
            'fieldEmailAddress'      => '',
            'doubleOptIn'            => false,
            'resubscribe'            => false,
            'note'                   => '',
        ];

        return $settings;
    }

    public function getSettingsFields($settings, $formId)
    {
        return [
            'fields' => [
                [
                    'key'         => 'name',
                    'label'       => __('Name', 'fluentform'),
                    'required'    => true,
                    'placeholder' => __('Your Feed Name', 'fluentform'),
                    'component'   => 'text',
                ],
                [
                    'key'         => 'list_id',
                    'label'       => __('FormsCRM List', 'fluentform'),
                    'placeholder' => __('Select FormsCRM List', 'fluentform'),
                    'tips'        => __('Select the FormsCRM list you would like to add your contacts to.', 'fluentform'),
                    'component'   => 'list_ajax_options',
                    'options'     => $this->getLists(),
                ],
                [
                    'key'                => 'merge_fields',
                    'require_list'       => true,
                    'label'              => __('Map Fields', 'fluentform'),
                    'tips'               => __('Associate your FormsCRM merge tags to the appropriate Fluent Forms fields by selecting the appropriate form field from the list. Also, FormsCRM Date fields supports only MM/DD/YYYY and DD/MM/YYYY format.', 'fluentform'),
                    'component'          => 'map_fields',
                    'field_label_remote' => __('FormsCRM Field', 'fluentform'),
                    'field_label_local'  => __('Form Field', 'fluentform'),
                    'primary_fileds'     => [
                        [
                            'key'           => 'fieldEmailAddress',
                            'label'         => __('Email Address', 'fluentform'),
                            'required'      => true,
                            'input_options' => 'emails',
                        ],
                    ],
                ],
                [
                    'key'               => 'interest_group',
                    'require_list'      => true,
                    'label'             => __('Interest Group', 'fluentform'),
                    'tips'              => __('You can map your formscrm interest group for this contact', 'fluentform'),
                    'component'         => 'chained_fields',
                    'sub_type'          => 'radio',
                    'category_label'    => __('Select Interest Category', 'fluentform'),
                    'subcategory_label' => __('Select Interest', 'fluentform'),
                    'remote_url'        => admin_url('admin-ajax.php?action=fluentform_formscrm_interest_groups'),
                    'inline_tip'        => __('Select the formscrm interest category and interest', 'fluentform'),
                ],
                [
                    'key'                => 'tags',
                    'require_list'       => true,
                    'label'              => __('Tags', 'fluentform'),
                    'tips'               => __('Associate tags to your FormsCRM contacts with a comma separated list (e.g. new lead, FluentForms, web source). Commas within a merge tag value will be created as a single tag.', 'fluentform'),
                    'component'          => 'selection_routing',
                    'simple_component'   => 'value_text',
                    'routing_input_type' => 'text',
                    'routing_key'        => 'tag_ids_selection_type',
                    'settings_key'       => 'tag_routers',
                    'labels'             => [
                        'choice_label'      => __('Enable Dynamic Tag Input', 'fluentform'),
                        'input_label'       => '',
                        'input_placeholder' => __('Tag', 'fluentform'),
                    ],
                    'inline_tip' => __('Please provide each tag by comma separated value, You can use dynamic smart codes', 'fluentform'),
                ],
                [
                    'key'          => 'note',
                    'require_list' => true,
                    'label'        => __('Note', 'fluentform'),
                    'tips'         => __('You can write a note for this contact', 'fluentform'),
                    'component'    => 'value_textarea',
                ],
                [
                    'key'            => 'doubleOptIn',
                    'require_list'   => true,
                    'label'          => __('Double Opt-in', 'fluentform'),
                    'tips'           => __('When the double opt-in option is enabled, FormsCRM will send a confirmation email to the user and will only add them to your <br /FormsCRM list upon confirmation.', 'fluentform'),
                    'component'      => 'checkbox-single',
                    'checkbox_label' => __('Enable Double Opt-in', 'fluentform'),
                ],
                [
                    'key'            => 'resubscribe',
                    'require_list'   => true,
                    'label'          => __('ReSubscribe', 'fluentform'),
                    'tips'           => __('When this option is enabled, if the subscriber is in an inactive state or has previously been unsubscribed, they will be re-added to the active list. Therefore, this option should be used with caution and only when appropriate.', 'fluentform'),
                    'component'      => 'checkbox-single',
                    'checkbox_label' => __('Enable ReSubscription', 'fluentform'),
                ],
                [
                    'key'            => 'markAsVIP',
                    'require_list'   => true,
                    'label'          => __('VIP', 'fluentform'),
                    'tips'           => __('When enabled, This contact will be marked as VIP.', 'fluentform'),
                    'component'      => 'checkbox-single',
                    'checkbox_label' => __('Mark as VIP Contact', 'fluentform'),
                ],
                [
                    'require_list' => true,
                    'key'          => 'conditionals',
                    'label'        => __('Conditional Logics', 'fluentform'),
                    'tips'         => __('Allow formscrm integration conditionally based on your submission values', 'fluentform'),
                    'component'    => 'conditional_block',
                ],
                [
                    'require_list'   => true,
                    'key'            => 'enabled',
                    'label'          => __('Status', 'fluentform'),
                    'component'      => 'checkbox-single',
                    'checkbox_label' => __('Enable This feed', 'fluentform'),
                ],
            ],
            'button_require_list' => true,
            'integration_title'   => __('FormsCRM', 'fluentform'),
        ];
    }

    public function prepareIntegrationFeed($setting, $feed, $formId)
    {
        $defaults = $this->getIntegrationDefaults([], $formId);

        foreach ($setting as $settingKey => $settingValue) {
            if ('true' == $settingValue) {
                $setting[$settingKey] = true;
            } elseif ('false' == $settingValue) {
                $setting[$settingKey] = false;
            } elseif ('conditionals' == $settingKey) {
                if ('true' == $settingValue['status']) {
                    $settingValue['status'] = true;
                } elseif ('false' == $settingValue['status']) {
                    $settingValue['status'] = false;
                }
                $setting['conditionals'] = $settingValue;
            }
        }

        if (! empty($setting['list_id'])) {
            $setting['list_id'] = (string) $setting['list_id'];
        }

        $settings['markAsVIP'] = ArrayHelper::isTrue($setting, 'markAsVIP');
        $settings['doubleOptIn'] = ArrayHelper::isTrue($setting, 'doubleOptIn');

        return wp_parse_args($setting, $defaults);
    }

    private function getLists()
    {
        $settings = get_option('_fluentform_formscrm_details');
        try {
            $MailChimp = new MailChimp($settings['apiKey']);
            $lists = $MailChimp->get('lists', ['count' => 9999]);
            if (! $MailChimp->success()) {
                return [];
            }
        } catch (\Exception $exception) {
            return [];
        }

        $formattedLists = [];
        foreach ($lists['lists'] as $list) {
            $formattedLists[$list['id']] = $list['name'];
        }

        return $formattedLists;
    }

    public function getMergeFields($list, $listId, $formId)
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $mergedFields = $this->findMergeFields($listId);

        $fields = [];

        foreach ($mergedFields as $merged_field) {
            $fields[$merged_field['tag']] = $merged_field['name'];
        }

        return $fields;
    }

    public function findMergeFields($listId)
    {
        $settings = get_option('_fluentform_formscrm_details');

        try {
            $MailChimp = new MailChimp($settings['apiKey']);

            $list = $MailChimp->get('lists/' . $listId . '/merge-fields', ['count' => 9999]);

            if (! $MailChimp->success()) {
                return false;
            }
        } catch (\Exception $exception) {
            return false;
        }

        return $list['merge_fields'];
    }

    public function fetchInterestGroups()
    {
        $settings = wp_unslash($this->app->request->get('settings'));

        $listId = ArrayHelper::get($settings, 'list_id');
        if (! $listId) {
            wp_send_json_success([
                'categories'    => [],
                'subcategories' => [],
                'reset_values'  => true,
            ]);
        }

        $categoryId = ArrayHelper::get($settings, 'interest_group.category');
        $categories = $this->getInterestCategories($listId);

        $subCategories = [];
        if ($categoryId) {
            $subCategories = $this->getInterestSubCategories($listId, $categoryId);
        }

        wp_send_json_success([
            'categories'    => $categories,
            'subcategories' => $subCategories,
            'reset_values'  => ! $categories && ! $subCategories,
        ]);
    }

    private function getInterestCategories($listId)
    {
        $settings = get_option('_fluentform_formscrm_details');
        try {
            $MailChimp = new MailChimp($settings['apiKey']);
            $categories = $MailChimp->get('/lists/' . $listId . '/interest-categories', [
                'count'  => 9999,
                'fields' => 'categories.id,categories.title',
            ]);
            if (! $MailChimp->success()) {
                return [];
            }
        } catch (\Exception $exception) {
            return [];
        }
        $categories = ArrayHelper::get($categories, 'categories', []);
        $formattedLists = [];
        foreach ($categories as $list) {
            $formattedLists[] = [
                'value' => $list['id'],
                'label' => $list['title'],
            ];
        }
        return $formattedLists;
    }

    private function getInterestSubCategories($listId, $categoryId)
    {
        $settings = get_option('_fluentform_formscrm_details');
        try {
            $MailChimp = new MailChimp($settings['apiKey']);
            $categories = $MailChimp->get('/lists/' . $listId . '/interest-categories/' . $categoryId . '/interests', [
                'count'  => 9999,
                'fields' => 'interests.id,interests.name',
            ]);
            if (! $MailChimp->success()) {
                return [];
            }
        } catch (\Exception $exception) {
            return [];
        }
        $categories = ArrayHelper::get($categories, 'interests', []);
        $formattedLists = [];
        foreach ($categories as $list) {
            $formattedLists[] = [
                'value' => $list['id'],
                'label' => $list['name'],
            ];
        }
        return $formattedLists;
    }

    public function sanitizeSettings($integration, $integrationId, $formId)
    {
        if (fluentformCanUnfilteredHTML()) {
            return $integration;
        }
        $sanitizeMap = [
            'status'                 => 'rest_sanitize_boolean',
            'enabled'                => 'rest_sanitize_boolean',
            'type'                   => 'sanitize_text_field',
            'list_id'                => 'sanitize_text_field',
            'list_name'              => 'sanitize_text_field',
            'name'                   => 'sanitize_text_field',
            'tags'                   => 'sanitize_text_field',
            'tag_ids_selection_type' => 'sanitize_text_field',
            'fieldEmailAddress'      => 'sanitize_text_field',
            'doubleOptIn'            => 'rest_sanitize_boolean',
            'resubscribe'            => 'rest_sanitize_boolean',
            'note'                   => 'sanitize_text_field',
        ];
        return fluentform_backend_sanitizer($integration, $sanitizeMap);
    }

    /*
    * For Handling Notifications broadcast
    */
    public function notify($feed, $formData, $entry, $form)
    {
        $response = $this->subscribe($feed, $formData, $entry, $form);

        if (true == $response && !is_wp_error($response)) {
            $message = __('FormsCRM feed has been successfully initialed and pushed data', 'fluentform');
            do_action('fluentform/integration_action_result', $feed, 'success', $message);
        } else {
            $message = __('FormsCRM feed has been failed to deliver feed', 'fluentform');
            if (is_wp_error($response)) {
                $message = $response->get_error_message();
                if (is_array($message)) {
                    $messageArray = $message;
                    $message = '';
                    foreach ($messageArray as $error) {
                        $message .= ArrayHelper::get($error, 'message');
                    }
                }
            }
            do_action('fluentform/integration_action_result', $feed, 'failed', $message);
        }
    }
}
