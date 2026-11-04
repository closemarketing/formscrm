<?php
/**
 * Gravity Forms entry list columns
 *
 * Makes available, for every CRM connected to a form, a status column and a
 * contact ID column in the Gravity Forms entry list column selector. The values
 * come from the sync status store, so they always reflect the latest send,
 * resend or automatic retry.
 *
 * Nothing is shown by default: each column is chosen one by one with Gravity
 * Forms' own column selector (gear icon of the entries list).
 *
 * @package    WordPress
 * @author     Close Marketing <info@closemarketing.com>
 * @copyright  2026 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FormsCRM_GravityForms_Entry_Columns' ) ) {
	/**
	 * Class FormsCRM_GravityForms_Entry_Columns
	 */
	class FormsCRM_GravityForms_Entry_Columns {

		/**
		 * Prefix of the column keys.
		 *
		 * @var string
		 */
		const COLUMN_PREFIX = 'formscrm_';

		/**
		 * Per-request cache of entry results.
		 *
		 * @var array
		 */
		private $cache = array();

		/**
		 * Construct of class
		 */
		public function __construct() {
			// Keep the store clean even when the columns are disabled.
			add_action( 'gform_delete_entry', array( $this, 'delete_entry_status' ), 10, 1 );

			if ( ! self::is_enabled() ) {
				return;
			}

			add_filter( 'gform_entry_meta', array( $this, 'register_entry_meta' ), 20, 2 );
			add_filter( 'gform_entries_field_value', array( $this, 'column_value' ), 20, 4 );
		}

		/**
		 * Whether the columns are offered in the column selector.
		 *
		 * They are always offered; nothing is shown until a user picks a column
		 * with the gear icon of the entries list.
		 *
		 * @return bool
		 */
		public static function is_enabled() {
			/**
			 * Filters whether the CRM columns are offered in the Gravity Forms column selector.
			 *
			 * @param bool $enabled Enabled by default.
			 */
			return (bool) apply_filters( 'formscrm_entry_list_columns_enabled', true );
		}

		/**
		 * Returns the display label of a CRM.
		 *
		 * @param string $crm_type CRM slug.
		 * @return string
		 */
		public static function crm_label( $crm_type ) {
			foreach ( (array) formscrm_get_choices() as $choice ) {
				if ( is_array( $choice ) && isset( $choice['value'], $choice['label'] ) && (string) $choice['value'] === (string) $crm_type ) {
					return (string) $choice['label'];
				}
			}
			return ucfirst( (string) $crm_type );
		}

		/**
		 * Builds the column key of a CRM and kind.
		 *
		 * @param string $crm_type CRM slug.
		 * @param string $kind     'status' or 'id'.
		 * @return string
		 */
		public static function column_key( $crm_type, $kind ) {
			return self::COLUMN_PREFIX . $crm_type . '_' . $kind;
		}

		/**
		 * Splits a column key into CRM slug and kind.
		 *
		 * @param string $key Column key.
		 * @return array|null array( crm_type, kind ) or null when it is not ours.
		 */
		public static function parse_column_key( $key ) {
			if ( 0 !== strpos( (string) $key, self::COLUMN_PREFIX ) ) {
				return null;
			}
			if ( ! preg_match( '/^' . self::COLUMN_PREFIX . '(.+)_(status|id)$/', (string) $key, $matches ) ) {
				return null;
			}
			return array( $matches[1], $matches[2] );
		}

		/**
		 * Registers the CRM columns as Gravity Forms entry meta.
		 *
		 * Entry meta shows up in Gravity Forms' own column selector (the gear icon
		 * of the entries list), so every column can be shown or hidden one by one,
		 * per form, with the native drag and drop ordering. The values are not
		 * stored as entry meta: they are served by column_value().
		 *
		 * @param array $entry_meta Registered entry meta.
		 * @param int   $form_id    Form ID.
		 * @return array
		 */
		public function register_entry_meta( $entry_meta, $form_id ) {
			$crm_types = FormsCRM_Sync_Status::get_crm_types_for_form( 'gravityforms', (int) $form_id );

			foreach ( $crm_types as $crm_type ) {
				$label = self::crm_label( $crm_type );

				$entry_meta[ self::column_key( $crm_type, 'status' ) ] = array(
					/* translators: %s: CRM name */
					'label'             => sprintf( __( '%s status', 'formscrm' ), $label ),
					'is_numeric'        => false,
					'is_default_column' => false,
				);
				$entry_meta[ self::column_key( $crm_type, 'id' ) ]     = array(
					/* translators: %s: CRM name */
					'label'             => sprintf( __( '%s ID', 'formscrm' ), $label ),
					'is_numeric'        => false,
					'is_default_column' => false,
				);
			}

			return $entry_meta;
		}

		/**
		 * Returns the cell value of a CRM column.
		 *
		 * @param mixed      $value    Current value.
		 * @param int        $form_id  Form ID.
		 * @param int|string $field_id Field ID or column name.
		 * @param array      $entry    Entry.
		 * @return mixed
		 */
		public function column_value( $value, $form_id, $field_id, $entry ) {
			$parsed = self::parse_column_key( (string) $field_id );
			if ( null === $parsed || empty( $entry['id'] ) ) {
				return $value;
			}

			list( $crm_type, $kind ) = $parsed;
			$entry_id                = (int) $entry['id'];

			if ( ! isset( $this->cache[ $entry_id ] ) ) {
				$this->cache[ $entry_id ] = FormsCRM_Sync_Status::get_for_entry( 'gravityforms', $entry_id );
			}
			$row = isset( $this->cache[ $entry_id ][ $crm_type ] ) ? $this->cache[ $entry_id ][ $crm_type ] : null;

			return 'status' === $kind ? self::render_status( $row ) : self::render_contact_id( $row );
		}

		/**
		 * Renders the status cell (escaped HTML).
		 *
		 * @param object|null $row Stored result.
		 * @return string
		 */
		public static function render_status( $row ) {
			if ( empty( $row ) ) {
				return '<span aria-label="' . esc_attr__( 'Not sent', 'formscrm' ) . '" style="opacity:.5">&mdash;</span>';
			}

			$is_success = 'success' === $row->status;
			$label      = $is_success ? __( 'Sent', 'formscrm' ) : __( 'Error', 'formscrm' );
			$color      = $is_success ? '#1a7f37' : '#b32d2e';
			$icon       = $is_success ? '&#10003;' : '&#10007;';

			$date  = ! empty( $row->updated_at ) ? get_date_from_gmt( $row->updated_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : '';
			$title = trim( $date . ( ! empty( $row->message ) ? ' — ' . $row->message : '' ) );

			return sprintf(
				'<span title="%1$s" style="color:%2$s;font-weight:600;white-space:nowrap">%3$s %4$s</span>',
				esc_attr( $title ),
				esc_attr( $color ),
				$icon, // Static entity.
				esc_html( $label )
			);
		}

		/**
		 * Renders the contact ID cell (escaped HTML).
		 *
		 * @param object|null $row Stored result.
		 * @return string
		 */
		public static function render_contact_id( $row ) {
			if ( empty( $row ) || '' === (string) $row->contact_id ) {
				return '<span style="opacity:.5">&mdash;</span>';
			}

			$title = ! empty( $row->crm_name ) ? $row->crm_name : '';

			return sprintf(
				'<code title="%1$s">%2$s</code>',
				esc_attr( $title ),
				esc_html( $row->contact_id )
			);
		}

		/**
		 * Removes the stored results when an entry is deleted.
		 *
		 * @param int $entry_id Entry ID.
		 * @return void
		 */
		public function delete_entry_status( $entry_id ) {
			FormsCRM_Sync_Status::delete_for_entry( 'gravityforms', (int) $entry_id );
		}
	}

	new FormsCRM_GravityForms_Entry_Columns();
}
