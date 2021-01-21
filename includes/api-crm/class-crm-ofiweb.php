<?php
/**
 * OfiWeb connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.1.0
 */

require_once 'debug.php';

/**
 * Class for CRM OFIWeb.
 */
class CRMLIB_OFIWEB {

	private function post_value( $url, $fields ) {
		// Url-ify the data for the POST.
		$fields_string = '';
		foreach ( $fields as $field ) {
			$fields_string .= $field['name'] . '=' . urlencode( $field['value'] ) . '&';
		}
		rtrim( $fields_string, '&' );

		// Open connection.
		$ch = curl_init();

		// set the url, number of POST vars, POST data.
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, count( $fields ) );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );

		// Execute post.
		$result = curl_exec( $ch );

		// Close connection
		curl_close( $ch );

		return $result;
	}

	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id     returns false if cannot login and string if gets token
	 */
	public function login( $settings ) {
		$url      = $settings['gf_crm_url'];

		return $url;
	}

	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_modules( $settings ) {
		$custom_modules = array(
			array(
				'label' => 'Leads',
				'name'  => 'leads',
			),
		);
		return $custom_modules;
	}

	/**
	 * List fields of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_fields( $settings ) {
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'Leads';
		}
		if ( 'Leads' === $module ) {
			$custom_fields = array(
				array(
					'label'    => 'Nombre',
					'name'     => 'nombre',
					'required' => true,
				),
				array(
					'label'    => 'Apellidos',
					'name'     => 'apellidos',
					'required' => false,
				),
				array(
					'label'    => 'Nacionalidad',
					'name'     => 'nacion',
					'required' => false,
				),
				array(
					'label'    => 'NIF',
					'name'     => 'nif',
					'required' => false,
				),
				array(
					'label'    => 'Fecha de Nacimiento (dd/mm/aaaa)',
					'name'     => 'nacimiento',
					'required' => false,
				),
				array(
					'label'    => 'Sexo (H/M)',
					'name'     => 'sexo',
					'required' => false,
				),
				array(
					'label'    => 'Calle',
					'name'     => 'calle',
					'required' => false,
				),
				array(
					'label'    => 'Localidad',
					'name'     => 'local',
					'required' => false,
				),
				array(
					'label'    => 'Provincia',
					'name'     => 'provincia',
					'required' => false,
				),
				array(
					'label'    => 'Código postal (XXXXX)',
					'name'     => 'cp',
					'required' => false,
				),
				array(
					'label'    => 'País',
					'name'     => 'pais',
					'required' => false,
				),
				array(
					'label'    => 'Zona',
					'name'     => 'zona',
					'required' => false,
				),
				array(
					'label'    => 'Teléfono (XXX XXX XXX)',
					'name'     => 'tfno',
					'required' => false,
				),
				array(
					'label'    => 'Móvil (XXX XXX XXX)',
					'name'     => 'movil',
					'required' => false,
				),
				array(
					'label'    => 'Correo electrónico',
					'name'     => 'email',
					'required' => false,
				),
				array(
					'label'    => 'Clasificación 1',
					'name'     => 'clasif1',
					'required' => false,
				),
				array(
					'label'    => 'Clasificación 2',
					'name'     => 'clasif2',
					'required' => false,
				),
				array(
					'label'    => 'Clasificación 3',
					'name'     => 'clasif3',
					'required' => false,
				),
			);
		}
		return $custom_fields;

	}

	/**
	 * Create entry of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns result of entry
	 */
	public function create_entry( $settings, $merge_vars ) {
		$url = $settings['gf_crm_url'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'Leads';
		}

		$response = $this->post_value( $url, $merge_vars );

		debug_message( $response );

		if ( $response ) {
			return true;
		} else {
			debug_email_lead( 'OfiCRM', 'Error ' . $json['error']['message'], $merge_vars );
			return false;
		}
		return $recordid;
	}
}
