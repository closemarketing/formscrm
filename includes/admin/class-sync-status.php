<?php
/**
 * Sync status store
 *
 * Keeps, per form entry and CRM, the result of the latest send to the CRM
 * (success/error, contact ID, CRM name and message). It is form plugin
 * agnostic: Gravity Forms, WPForms and any other integration record their
 * results here, and the entry list columns read them back.
 *
 * @package    WordPress
 * @author     Close Marketing <info@closemarketing.com>
 * @copyright  2026 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FormsCRM_Sync_Status' ) ) {
	/**
	 * Class FormsCRM_Sync_Status
	 */
	class FormsCRM_Sync_Status {

		/**
		 * Database schema version.
		 *
		 * @var string
		 */
		const DB_VERSION = '1.0';

		/**
		 * Option that stores the installed schema version.
		 *
		 * @var string
		 */
		const DB_VERSION_OPTION = 'formscrm_sync_status_db_version';

		/**
		 * Returns the table name.
		 *
		 * @return string
		 */
		public static function table_name() {
			global $wpdb;
			return $wpdb->prefix . 'formscrm_sync_status';
		}

		/**
		 * Registers hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'plugins_loaded', array( __CLASS__, 'maybe_create_table' ) );
		}

		/**
		 * Creates or upgrades the table when needed.
		 *
		 * @return void
		 */
		public static function maybe_create_table() {
			if ( version_compare( get_option( self::DB_VERSION_OPTION, '0' ), self::DB_VERSION, '>=' ) ) {
				return;
			}

			global $wpdb;
			$table_name      = self::table_name();
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$table_name} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				form_type varchar(30) NOT NULL,
				form_id bigint(20) NOT NULL DEFAULT 0,
				entry_id bigint(20) NOT NULL,
				crm_type varchar(100) NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'error',
				contact_id varchar(191) DEFAULT NULL,
				crm_name varchar(191) DEFAULT NULL,
				message text DEFAULT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY entry_crm (form_type,entry_id,crm_type),
				KEY form_lookup (form_type,form_id)
			) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		}

		/**
		 * Normalizes the form type slug used by the different integrations.
		 *
		 * @param string $form_type Form type ('gravity', 'gravityforms', 'wpforms'...).
		 * @return string
		 */
		public static function normalize_form_type( $form_type ) {
			$form_type = sanitize_key( (string) $form_type );
			return 'gravity' === $form_type ? 'gravityforms' : $form_type;
		}

		/**
		 * Normalizes a status value.
		 *
		 * @param string $status Raw status.
		 * @return string 'success' or 'error'.
		 */
		public static function normalize_status( $status ) {
			return in_array( strtolower( (string) $status ), array( 'success', 'ok' ), true ) ? 'success' : 'error';
		}

		/**
		 * Records (insert or replace) the latest result of sending an entry to a CRM.
		 *
		 * Never throws: recording the status must not break the submission flow.
		 *
		 * @param string $form_type Form type slug.
		 * @param int    $form_id   Form ID.
		 * @param int    $entry_id  Entry ID.
		 * @param string $crm_type  CRM slug (e.g. 'clientify').
		 * @param array  $data      Keys: status, contact_id, crm_name, message, updated_at (GMT, optional).
		 * @return bool True when stored.
		 */
		public static function record( $form_type, $form_id, $entry_id, $crm_type, array $data = array() ) {
			$entry_id = (int) $entry_id;
			$crm_type = sanitize_key( (string) $crm_type );
			if ( $entry_id <= 0 || '' === $crm_type ) {
				return false;
			}

			global $wpdb;

			$previous   = self::get_one( $form_type, $entry_id, $crm_type );
			$contact_id = isset( $data['contact_id'] ) && is_scalar( $data['contact_id'] ) ? (string) $data['contact_id'] : '';
			$crm_name   = isset( $data['crm_name'] ) && is_scalar( $data['crm_name'] ) ? (string) $data['crm_name'] : '';
			$status     = self::normalize_status( isset( $data['status'] ) ? $data['status'] : '' );

			// A failed resend must not erase the contact created by a previous success.
			if ( '' === $contact_id && 'error' === $status && $previous && ! empty( $previous->contact_id ) ) {
				$contact_id = (string) $previous->contact_id;
			}

			$row = array(
				'form_type'  => self::normalize_form_type( $form_type ),
				'form_id'    => (int) $form_id,
				'entry_id'   => $entry_id,
				'crm_type'   => $crm_type,
				'status'     => $status,
				'contact_id' => $contact_id,
				'crm_name'   => wp_strip_all_tags( $crm_name ),
				'message'    => isset( $data['message'] ) ? wp_strip_all_tags( (string) $data['message'] ) : '',
				'updated_at' => ! empty( $data['updated_at'] ) ? (string) $data['updated_at'] : current_time( 'mysql', true ),
			);

			// REPLACE relies on the unique key (form_type, entry_id, crm_type).
			$result = $wpdb->replace( self::table_name(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return false !== $result;
		}

		/**
		 * Rebuilds the latest result per CRM from FormsCRM entry notes (history sent
		 * before the store existed).
		 *
		 * Notes do not carry the CRM slug as data, so in forms with several CRMs a
		 * note is attributed to the CRM whose slug or label appears in its text or
		 * author; in forms with a single CRM every note belongs to it. The contact ID
		 * comes from the entry meta that FormsCRM saves under the CRM slug.
		 *
		 * @param array $notes       Notes (objects or arrays) with note_type, sub_type, value, user_name, date_created.
		 * @param array $crm_types   CRM slugs connected to the form, slug => label.
		 * @param array $contact_ids Contact IDs by CRM slug.
		 * @return array<string, array> Result data by CRM slug, ready for record().
		 */
		public static function build_from_notes( array $notes, array $crm_types, array $contact_ids = array() ) {
			$only_one = 1 === count( $crm_types );
			$latest   = array();

			foreach ( $notes as $note ) {
				$note = (array) $note;
				if ( 'formscrm' !== ( isset( $note['note_type'] ) ? $note['note_type'] : '' ) ) {
					continue;
				}
				$sub_type = isset( $note['sub_type'] ) ? $note['sub_type'] : '';
				if ( ! in_array( $sub_type, array( 'success', 'error' ), true ) ) {
					continue;
				}

				$haystack = strtolower( ( isset( $note['value'] ) ? $note['value'] : '' ) . ' ' . ( isset( $note['user_name'] ) ? $note['user_name'] : '' ) );

				foreach ( $crm_types as $slug => $label ) {
					$matches = $only_one || false !== strpos( $haystack, strtolower( (string) $slug ) ) || ( '' !== (string) $label && false !== strpos( $haystack, strtolower( (string) $label ) ) );
					if ( ! $matches ) {
						continue;
					}
					$date = isset( $note['date_created'] ) ? (string) $note['date_created'] : '';
					$id   = isset( $note['id'] ) ? (int) $note['id'] : 0;
					if ( ! isset( $latest[ $slug ] ) || $date > $latest[ $slug ]['date'] || ( $date === $latest[ $slug ]['date'] && $id > $latest[ $slug ]['id'] ) ) {
						$latest[ $slug ] = array(
							'date'     => $date,
							'id'       => $id,
							'sub_type' => $sub_type,
							'value'    => isset( $note['value'] ) ? $note['value'] : '',
						);
					}
				}
			}

			$results = array();
			foreach ( $crm_types as $slug => $label ) {
				$contact_id = isset( $contact_ids[ $slug ] ) && is_scalar( $contact_ids[ $slug ] ) ? (string) $contact_ids[ $slug ] : '';

				if ( isset( $latest[ $slug ] ) ) {
					$results[ $slug ] = array(
						'status'     => $latest[ $slug ]['sub_type'],
						'contact_id' => $contact_id,
						'crm_name'   => (string) $label,
						'message'    => wp_strip_all_tags( $latest[ $slug ]['value'] ),
						'updated_at' => $latest[ $slug ]['date'],
					);
				} elseif ( '' !== $contact_id ) {
					// Created in the CRM, but the note is gone: the ID proves it was sent.
					$results[ $slug ] = array(
						'status'     => 'success',
						'contact_id' => $contact_id,
						'crm_name'   => (string) $label,
						'message'    => '',
					);
				}
			}

			return $results;
		}

		/**
		 * Returns the stored result for one entry and CRM.
		 *
		 * @param string $form_type Form type slug.
		 * @param int    $entry_id  Entry ID.
		 * @param string $crm_type  CRM slug.
		 * @return object|null
		 */
		public static function get_one( $form_type, $entry_id, $crm_type ) {
			global $wpdb;
			$table = self::table_name();

			return $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE form_type = %s AND entry_id = %d AND crm_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					self::normalize_form_type( $form_type ),
					(int) $entry_id,
					sanitize_key( (string) $crm_type )
				)
			);
		}

		/**
		 * Returns all CRM results of an entry, keyed by CRM slug.
		 *
		 * @param string $form_type Form type slug.
		 * @param int    $entry_id  Entry ID.
		 * @return array<string, object>
		 */
		public static function get_for_entry( $form_type, $entry_id ) {
			global $wpdb;
			$table = self::table_name();

			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE form_type = %s AND entry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					self::normalize_form_type( $form_type ),
					(int) $entry_id
				)
			);

			$by_crm = array();
			foreach ( (array) $rows as $row ) {
				$by_crm[ $row->crm_type ] = $row;
			}
			return $by_crm;
		}

		/**
		 * Returns the CRM slugs that have results for a form.
		 *
		 * @param string $form_type Form type slug.
		 * @param int    $form_id   Form ID.
		 * @return string[]
		 */
		public static function get_crm_types_for_form( $form_type, $form_id ) {
			global $wpdb;
			$table = self::table_name();

			$types = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT DISTINCT crm_type FROM {$table} WHERE form_type = %s AND form_id = %d ORDER BY crm_type ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					self::normalize_form_type( $form_type ),
					(int) $form_id
				)
			);

			return is_array( $types ) ? $types : array();
		}

		/**
		 * Deletes the stored results of an entry (entry deleted).
		 *
		 * @param string $form_type Form type slug.
		 * @param int    $entry_id  Entry ID.
		 * @return void
		 */
		public static function delete_for_entry( $form_type, $entry_id ) {
			global $wpdb;
			$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				self::table_name(),
				array(
					'form_type' => self::normalize_form_type( $form_type ),
					'entry_id'  => (int) $entry_id,
				)
			);
		}
	}

	FormsCRM_Sync_Status::init();
}

if ( ! function_exists( 'formscrm_record_sync_status' ) ) {
	/**
	 * Records the latest result of sending an entry to a CRM.
	 *
	 * Safe to call from any integration; it never throws.
	 *
	 * @param string $form_type Form type slug ('gravityforms', 'wpforms'...).
	 * @param int    $form_id   Form ID.
	 * @param int    $entry_id  Entry ID.
	 * @param string $crm_type  CRM slug.
	 * @param array  $data      Keys: status ('success'|'error'), contact_id, crm_name, message.
	 * @return bool
	 */
	function formscrm_record_sync_status( $form_type, $form_id, $entry_id, $crm_type, array $data = array() ) {
		try {
			return FormsCRM_Sync_Status::record( $form_type, $form_id, $entry_id, $crm_type, $data );
		} catch ( Throwable $e ) {
			formscrm_debug_message( 'Sync status not recorded: ' . $e->getMessage() );
			return false;
		}
	}
}
