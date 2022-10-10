<?php
/**
 * MailerLite connect library
 *
 * Has functions to login, list fields and create leadº
 *
 * @author    David Perez <david@closemarketing.es>
 * @category  Functions
 * @package   FormsCRM
 * @version   1.0.0
 * @copyright 2021 Closemarketing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for MailerLite connection.
 */
class CRMLIB_Mailerlite {
	/**
	 * Mailer Lite Connector API
	 *
	 * @param string $method Method to connect: GET, POST..
	 * @param string $module URL endpoint.
	 * @param array  $data   Body data.
	 * @return array
	 */
	private function api( $method, $module, $apikey, $data = array() ) {
		if ( empty( $apikey ) ) {
			return;
		}
		$args = array(
			'method' => $method,
			'headers' => array(
				'X-MailerLite-ApiKey' => $apikey,
				'Content-Type'        => 'application/json',
			),
		);
		if ( ! empty( $data ) ) {
			$args['body'] = json_encode( $data );
		}
		$url = 'https://api.mailerlite.com/api/v2/' . $module;
		$response = wp_remote_request( $url, $args );

		if ( 200 === $response['response']['code'] ) {
			$body = wp_remote_retrieve_body( $response );
			return json_decode( $body, true );
		} else {
			return false;
		}
	}


	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id     returns false if cannot login and string if gets token
	 */
	public function login( $settings ) {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$login_result = $this->api( 'GET', 'contacts', $apikey );

		if ( $apikey && 'error' !== $login_result['status'] ) {
			return true;

		} else {
			return false;
		}
	}

	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_modules( $settings ) {
		$modules = array(
			array(
				'name'  => 'contacts',
				'value' => 'contacts',
				'label' => __( 'Contacts', 'formscrm' ),
			),
		);
		return $modules;
	}

	/**
	 * List fields for given module of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_fields( $settings, $module ) {
		$module = ! empty( $module ) ? $module : 'contacts';

		if ( 'contacts' === $module ) {
			// lead fields.
			$fields = array(
				// Contact Info static.
				array(
					'name'     => 'name',
					'label'    => __( 'Name', 'formscrm' ),
					'required' => true,
				),
				array(
					'name'     => 'tradename',
					'label'    => __( 'Fiscal name', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'code',
					'label'    => __( 'VAT No', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'type',
					'label'    => __( 'Type', 'formscrm' ),
					'tooltip'  => __( 'Type of contact. Use: supplier, debtor, creditor, client, lead.', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'          => 'isperson',
					'label'         => __( 'Is person?', 'formscrm' ),
					'default_value' => '0',
					'tooltip'       => __( 'Type of person. Use: 1 = Person, 0 = Company.', 'formscrm' ),
					'required'      => false,
				),
				array(
					'name'     => 'email',
					'label'    => __( 'Email', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'phone',
					'label'    => __( 'Phone', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'mobile',
					'label'    => __( 'Mobile', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|address',
					'label'    => __( 'Billing Address', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|city',
					'label'    => __( 'Billing City', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|postalCode',
					'label'    => __( 'Billing ZIP', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|province',
					'label'    => __( 'Billing Province', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'billAddress|country',
					'label'    => __( 'Billing Country', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'note',
					'label'    => __( 'Note', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'tags',
					'label'    => __( 'Tags', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'iban',
					'label'    => __( 'IBAN', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'swift',
					'label'    => __( 'SWIFT', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'sepaRef',
					'label'    => __( 'SEPA Ref', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'sepaDate',
					'label'    => __( 'SEPA Date', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'taxOperation',
					'label'    => __( 'Tax Operation', 'formscrm' ),
					'tooltip'  => __( 'Use: general, intra, nosujeto, receq, exento.', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'clientRecord',
					'label'    => __( 'Client Record', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'supplierRecord',
					'label'    => __( 'Supplier Record', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'socialNetworks|website',
					'label'    => __( 'Social Networks: Website', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|expensesAccountRecord',
					'label'    => __( 'Expenses Account Record', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|salesAccountRecord',
					'label'    => __( 'Sales Account Name', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|salesAccountName',
					'label'    => __( 'Sales Account Name', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|dueDays',
					'label'    => __( 'Due Days', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|salesTax',
					'label'    => __( 'Sales Tax', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|purchasesTax',
					'label'    => __( 'Purchases Tax', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|discount',
					'label'    => __( 'Discount', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|currency',
					'label'    => __( 'Expenses Account Name', 'formscrm' ),
					'required' => false,
					'tooltip' => __( 'Currency ISO code in lowercase (e.g., eur = Euro, usd = U.S. Dollar, etc )', 'formscrm' ),
				),
				array(
					'name'     => 'defaults|language',
					'label'    => __( 'Language', 'formscrm' ),
					'required' => false,
					'tooltip' => __( 'options (es = spanish, en = english, fr = french, de = german, it = italian, ca = catalan, eu = euskera)', 'formscrm' ),
				),
				array(
					'name'     => 'defaults|showTradeNameOnDocs',
					'label'    => __( 'Show Trade Name on Docs', 'formscrm' ),
					'tooltip' => __( 'Use: 1 = Yes, 0 = No.', 'formscrm' ),
					'required' => false,
				),
				array(
					'name'     => 'defaults|showCountryOnDocs',
					'label'    => __( 'Show Country on Docs', 'formscrm' ),
					'tooltip'  => __( 'Use: 1 = Yes, 0 = No.', 'formscrm' ),
					'required' => false,
				),
			);
		} // module contacts

		return $fields;
	}

	/**
	 * Creates an entry for given module of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @param  array $merge_vars array of values for the entry.
	 * @return array           id or false
	 */
	public function create_entry( $settings, $merge_vars ) {
		$apikey = isset( $settings['fc_crm_apipassword'] ) ? $settings['fc_crm_apipassword'] : '';
		$module = isset( $settings['fc_crm_module'] ) ? $settings['fc_crm_module'] : 'contacts';

		$contact = array();

		foreach ( $merge_vars as $element ) {
			$contact[ $element['name'] ] = (string) $element['value'];
		}

		$result = $this->post( $module, $contact, $apikey );

		if ( 'error' === $result['status'] ) {
			$response_result = array(
				'status'  => 'error',
				'message' => $result['data'],
			);
		} else {
			$response_result = array(
				'status'  => 'ok',
				'message' => 'success',
				'id'      => $result['data']['id'],
			);
		}

		return $response_result;
	}

} //from Class
