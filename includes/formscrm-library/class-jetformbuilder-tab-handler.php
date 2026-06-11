<?php
/**
 * FormsCRM JetFormBuilder Global Settings Tab Handler
 *
 * Adds a "FormsCRM" tab to the JetFormBuilder Settings page (JetForm > Settings)
 * so users can store global CRM credentials — the same way ActiveCampaign and
 * GetResponse do it. The JS editor reads these via `use_global: true`.
 *
 * @package   WordPress
 * @author    David Perez <david@closemarketing.es>
 * @copyright 2024 Closemarketing
 * @version   2.0
 */

defined( 'ABSPATH' ) || exit;

use Jet_Form_Builder\Admin\Tabs_Handlers\Base_Handler;

/**
 * Handles saving/loading global FormsCRM credentials inside JFB settings.
 */
class FORMSCRM_JFB_Tab_Handler extends Base_Handler {

	const SLUG = 'formscrm-tab';

	/**
	 * Returns the tab slug, used as the Vue component name and option key.
	 *
	 * @return string
	 */
	public function slug(): string {
		return self::SLUG;
	}

	/**
	 * Called via AJAX when the user saves the settings tab.
	 * Sanitizes and persists the submitted credentials.
	 */
	public function on_get_request(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified by Base_Handler::on_raw_request.
		$allowed_keys = array(
			'fc_crm_type',
			'fc_crm_url',
			'fc_crm_username',
			'fc_crm_password',
			'fc_crm_apipassword',
			'fc_crm_apisales',
			'fc_crm_odoodb',
		);

		$data = array();
		foreach ( $allowed_keys as $key ) {
			$data[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$result = $this->update_options( $data );
		$this->send_response( $result );
	}

	/**
	 * Called when the JFB Settings page loads this tab.
	 * Returns the currently stored credentials.
	 *
	 * @return array<string,string>
	 */
	public function on_load(): array {
		return $this->get_options(
			array(
				'fc_crm_type'        => '',
				'fc_crm_url'         => '',
				'fc_crm_username'    => '',
				'fc_crm_password'    => '',
				'fc_crm_apipassword' => '',
				'fc_crm_apisales'    => '',
				'fc_crm_odoodb'      => '',
			)
		);
	}
}
