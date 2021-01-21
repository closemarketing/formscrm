<?php
/**
 * Inmovilla connect library
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
 * Class for CRM Inmovilla.
 */
class CRM_INMOVILLA {

	/**
	 * Gets url info from api inmovilla
	 *
	 * @param string $url URL of API inmovilla.
	 * @param array $campospost Array post variables.
	 * @return $page 
	 */
	private function geturl( $url, $campospost ) {
		$header[0]  = 'Accept: text/xml,application/xml,application/xhtml+xml,';
		$header[0] .= 'text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';
		$header[]   = 'Cache-Control: max-age=0';
		$header[]   = 'Connection: keep-alive';
		$header[]   = 'Keep-Alive: 300';
		$header[]   = 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7';
		$header[]   = 'Accept-Language: en-us,en;q=0.5';
		$header[]   = 'Pragma: ';

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, '' );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		if ( strlen( $campospost ) > 0 ) {
			// los datos tienen que ser reales, de no ser asi se desactivara el servicio
			$campospost = $campospost . '&ia=' . $_SERVER['REMOTE_ADDR'] . '&ib=' . $_SERVER['HTTP_X_FORWARDED_FOR'];
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $campospost );
		}
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
		curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookie );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3' );
		$page = curl_exec( $ch );
		curl_close( $ch );

		return $page;
	}

	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return false or id     returns false if cannot login and string if gets token
	 */
	public function login( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

	}

	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options.
	 * @return array           returns an array of mudules
	 */
	public function list_modules( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

		if ( isset( $result['error'] ) ) {
			// Handle vTiger error.
			echo '<div class="error">';
			echo '<p><strong>vTiger ERROR ' . $result['error']->code . ': </strong> ' . $result['error']->message . '</p>';
			echo '</div>';
			return;
		}
		return $custom_modules;
	}

	/**
	 * List Fields
	 */
	function list_fields( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		$module   = isset( $settings['gf_crm_module'] ) ? $settings['gf_crm_module'] : 'lead';

		// Get fields from module
		$login_result = $this->login( $settings );

		debug_message( __( 'Login result:', 'gravityforms-crm' ) . $login_result );
		debug_message( __( 'Module active:', 'gravityforms-crm' ) . $module );

		$webservice = $url . 'webservice.php';
		$operation  = '?operation=describe&sessionName=' . $login_result . '&elementType=' . $module;

		$result = $this->call_vtiger_get( $webservice . $operation );
		$result = json_decode( $result );
		$result = get_object_vars( $result );

		if ( isset( $result['error'] ) ) {
			// Handle vTiger error
			echo '<div class="error">';
			echo '<p><strong>vTiger ERROR ' . $result['error']->code . ': </strong> ' . $result['error']->message . '</p>';
			echo '</div>';
			return;
		}
		$result = get_object_vars( $result['result'] );

		$i             = 0;
		$custom_fields = array();
		foreach ( $result['fields'] as $arrayob ) {
			$field = get_object_vars( $arrayob );

			if ( $field['mandatory'] == 1 ) {
				$custom_fields[ $i ] = array(
					'label'    => $field['label'],
					'name'     => $field['name'],
					'required' => true,
				);
			} else {
				$custom_fields[ $i ] = array(
					'label' => $field['label'],
					'name'  => $field['name'],
				);
			}
			$i++;
		}
		return $custom_fields;

	}

	/**
	 * Create Entry
	 */
	function create_entry( $settings, $merge_vars ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}

		$login_result = $this->login( $settings );

		// vTiger Method
		$webservice = $url . '/webservice.php';

		$jsondata = $this->convert_custom_fields( $merge_vars );

		debug_message( $jsondata );

		$params = array(
			'operation'   => 'create',
			'sessionName' => $login_result,
			'element'     => $jsondata,
			'elementType' => $module,
		);

		$result = $this->call_vtiger_post( $webservice, $params );
		$json   = json_decode( $result, true );

		debug_message( $json );

		if ( $json['success'] ) {
			$recordid = $json['result']['id'];
		} else {
			debug_email_lead( 'vTiger', 'Error ' . $json['error']['message'], $merge_vars );
			return false;
		}
		return $recordid;
	}
}


/*
Procesos ('tipo',1,100,"","");
Procesos ('ciudad',1,100,"","");
Procesos ('zonas',1,100,"key_loca=2013","");
Procesos ('destacados',1,20,"","precioinmo, precioalq");
Procesos ('paginacion',1,20,"ascensor=1","precioinmo, precioalq");
PedirDatos($numagencia,$password,$idioma);*/

function Procesos( $tipo, $posinicial, $numelementos, $where, $orden ) {
	global $arrpeticiones;

	$arrpeticiones[ count( $arrpeticiones ) ] = $tipo;
	$arrpeticiones[ count( $arrpeticiones ) ] = $posinicial;
	$arrpeticiones[ count( $arrpeticiones ) ] = $numelementos;
	$arrpeticiones[ count( $arrpeticiones ) ] = $where;
	$arrpeticiones[ count( $arrpeticiones ) ] = $orden;
}


function Pedirdatos( $numagencia, $password, $idioma, $json = 0 ) {

	global $arrpeticiones;
	global $addnumagencia;

	$texto = "$numagencia$addnumagencia;$password;$idioma;";
	$texto = $texto . 'lostipos';

	for ( $i = 0;$i < count( $arrpeticiones );$i++ ) {
		$texto = $texto . ';' . $arrpeticiones[ $i ];
	}

	$texto = rawurlencode( $texto );

	$url = 'http://apiweb.inmovilla.com/apiweb/apiweb.php';

	if ( $json ) {
		$contenido = geturl( $url, "param=$texto&json=1" );
	} else {
		@eval( geturl( $url, "param=$texto" ) );
	}

	// echo geturl($url,"param=$texto");
	// @eval (file_get_contents($url."?param=$texto"));
	// echo file_get_contents($url);
	$GLOBALS['arrpeticiones'] = array();

	if ( $json ) {
		return $contenido;
	}
}


