<?php
/**
 * Insightly connect library
 *
 * Has functions to login, list fields and create lead
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

require_once 'debug.php';

/**
 * Class for CRM
 */
class CRMLIB_INSIGHTLY {

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		require_once 'insightly/insightly.php';

	}


	
	/**
	 * Logins to a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return false or id           returns false if cannot login and string if gets token
	 */
	function login( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

		$i = new Insightly( $apipassword );

		try {
			$users   = $i->getUsers();
			$user    = $users[0];
			$user_id = $user->USER_ID;
			echo '<div id="message" class="updated below-h2"><p><strong>' . __( 'Logged correctly in', 'gravityformscrm' ) . ' Insightly</strong></p></div>';
			return $apipassword;
		} catch ( Exception $ex ) {
			$user    = null;
			$users   = null;
			$user_id = null;

			// Handle vTiger error
			echo '<div id="message" class="error below-h2"><p><strong>' . __( 'Insightly Error', 'gravityformscrm' ) . ': </strong></p></div>';
			return false;
		}
	}
	/**
	 * List modules of a CRM
	 *
	 * @param  array $settings settings from Gravity Forms options
	 * @return array           returns an array of mudules
	 */
	function list_modules( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];

		$custom_modules = array(
			array(
				'label' => __( 'Leads', 'gravityforms-crm' ),
				'name'  => 'Leads',
			),
			array(
				'label' => __( 'Contacts', 'gravityforms-crm' ),
				'name'  => 'Contacts',
			),
			array(
				'label' => __( 'Events', 'gravityforms-crm' ),
				'name'  => 'Events',
			),
			array(
				'label' => __( 'Organizations', 'gravityforms-crm' ),
				'name'  => 'Organizations',
			),
			array(
				'label' => __( 'Opportunities', 'gravityforms-crm' ),
				'name'  => 'Opportunities',
			),
			array(
				'label' => __( 'Projects', 'gravityforms-crm' ),
				'name'  => 'Projects',
			),
		);

		return $custom_modules;
	}

	/**
	 * List Fields of actual module of CRM
	 *
	 * @param array $settings Settings from Gravity Forms options.
	 * @return array $custom_fields Array of fields
	 */
	pfunction list_fields( $settings ) {
		$url      = check_url_crm( $settings['gf_crm_url'] );
		$username = $settings['gf_crm_username'];
		$password = $settings['gf_crm_apipassword'];
		if ( isset( $settings['gf_crm_module'] ) ) {
			$module = $settings['gf_crm_module'];
		} else {
			$module = 'lead';
		}

		$i = new Insightly( $password );

		switch ( $module ) {
			case 'Contacts':
				$entries     = $i->getContacts();
				$entry_guide = get_object_vars( $entries[0] );

				foreach ( $entry_guide as $key => $value ) {
					$custom_fields[] = array(
						'label' => $key,
						'name'  => $key,
					);
				}
				return $custom_fields;

			case 'Leads':
				$entries     = $i->getOpportunities();
				$entry_guide = get_object_vars( $entries[0] );

				foreach ( $entry_guide as $key => $value ) {
					$custom_fields[] = array(
						'label' => $key,
						'name'  => $key,
					);
				}
				return $custom_fields;

			default:
				echo '<div class="error">';
				echo '<p><strong>Insightly ERROR getting custom fields.</p>';
				echo '</div>';
				return null;
		}
	}

	/**
	 * Create an entry in a module
	 *
	 * @param array $settings Settings from Gravity Forms options.
	 * @param array $merge_vars Values passed in the form.
	 * @return id ID of created entry.
	 */
	public function create_entry( $settings, $merge_vars ) {
	   $url      = check_url_crm( $settings['gf_crm_url'] );
	   $username = $settings['gf_crm_username'];
	   $password = $settings['gf_crm_apipassword'];
	   if ( isset( $settings['gf_crm_module'] ) ) {
		   $module = $settings['gf_crm_module'];
	   } else {
		   $module = 'lead';
	   }

		$i = new Insightly( $password );

		$merge_vars = (object) $this->convert_array( $merge_vars );

		switch ( $module ) {
			case 'Contacts':
				$entry = $i->addContact( $merge_vars );
				return $entry->CONTACT_ID;

			case 'Leads':
				$entry = $i->addOpportunity( $merge_vars );
				return $entry->OPPORTUNITY_ID;

			case 'Events':
					$entry = $i->addContact( $merge_vars );
					$event = $i->addEvent('sample');
					// $organization = $i->addOrganization('sample');
					// $project = $i->addProject('sample');
					return $entry->CONTACT_ID;

			default:
				echo '<div class="error">';
				echo '<p><strong>Insightly ERROR getting custom fields.</p>';
				echo '</div>';
				return null;
		}


	}


	/**
	 * Converts to array for insightly
	 *
	 * @since  1.0
	 * @access private
	 * @return array
	 */
	private function convert_array( $merge_vars ) {
		$array_ins = array();

		foreach ( $merge_vars as $array_merge ) {
			$array_ins[ $array_merge['name'] ] = $array_merge['value'];
		}
		return $array_ins;
	}
}
