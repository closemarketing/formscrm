<?php
/**
 * Error Log Management
 *
 * Handles error logging and display for FormsCRM plugin.
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2024 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FORMSCRM_Error_Log' ) ) {
	/**
	 * Class FORMSCRM_Error_Log
	 *
	 * Handles error log table creation and CRUD operations.
	 */
	class FORMSCRM_Error_Log {
		/**
		 * Table name
		 *
		 * @var string
		 */
		private $table_name;

		/**
		 * Constructor
		 */
		public function __construct() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'formscrm_error_log';

			add_action( 'plugins_loaded', array( $this, 'check_database_version' ) );
			add_action( 'wp_ajax_formscrm_resend_entry', array( $this, 'ajax_resend_entry' ) );
			add_action( 'wp_ajax_formscrm_delete_log', array( $this, 'ajax_delete_log' ) );
			add_action( 'wp_ajax_formscrm_clear_all_logs', array( $this, 'ajax_clear_all_logs' ) );

			// Hook for automatic retry cron.
			add_action( 'formscrm_retry_failed_entry', array( $this, 'retry_failed_entry' ), 10, 1 );
		}

		/**
		 * Check database version and create/update table if needed
		 *
		 * @return void
		 */
		public function check_database_version() {
			$installed_version = get_option( 'formscrm_error_log_db_version', '0' );
			$current_version   = '1.1';

			if ( version_compare( $installed_version, $current_version, '<' ) ) {
				$this->create_table();
				update_option( 'formscrm_error_log_db_version', $current_version );
			}
		}

		/**
		 * Create error log table
		 *
		 * @return void
		 */
		public function create_table() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE {$this->table_name} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				error_date datetime NOT NULL,
				crm_type varchar(100) NOT NULL,
				error_message text NOT NULL,
				form_type varchar(50) DEFAULT NULL,
				form_type_title varchar(255) DEFAULT NULL,
				form_id varchar(50) DEFAULT NULL,
				form_name varchar(255) DEFAULT NULL,
				entry_id varchar(50) DEFAULT NULL,
				lead_data longtext NOT NULL,
				api_url text DEFAULT NULL,
				json_request longtext DEFAULT NULL,
				status varchar(20) DEFAULT 'failed',
				resend_attempts int(11) DEFAULT 0,
				last_resend_date datetime DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY crm_type (crm_type),
				KEY status (status),
				KEY error_date (error_date)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		/**
		 * Insert error log
		 *
		 * @param string $crm        CRM type.
		 * @param string $error      Error message.
		 * @param array  $data       Lead data.
		 * @param string $url        API URL.
		 * @param string $json       JSON request.
		 * @param array  $form_info  Form information.
		 * @return int|false Log ID or false on failure.
		 */
		public function insert_log( $crm, $error, $data, $url = '', $json = '', $form_info = array() ) {
			global $wpdb;

			$log_data = array(
				'error_date'      => current_time( 'mysql' ),
				'crm_type'        => sanitize_text_field( $crm ),
				'error_message'   => sanitize_textarea_field( $error ),
				'form_type'       => isset( $form_info['form_type'] ) ? sanitize_text_field( $form_info['form_type'] ) : null,
				'form_type_title' => isset( $form_info['form_type_title'] ) ? sanitize_text_field( $form_info['form_type_title'] ) : null,
				'form_id'         => isset( $form_info['form_id'] ) ? sanitize_text_field( $form_info['form_id'] ) : null,
				'form_name'       => isset( $form_info['form_name'] ) ? sanitize_text_field( $form_info['form_name'] ) : null,
				'entry_id'        => isset( $form_info['entry_id'] ) ? sanitize_text_field( $form_info['entry_id'] ) : null,
				'lead_data'       => wp_json_encode( $data ),
				'api_url'         => $url ? esc_url_raw( $url ) : null,
				'json_request'    => $json ? wp_json_encode( json_decode( $json ) ) : null,
				'status'          => 'failed',
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert( $this->table_name, $log_data );

			if ( $result ) {
				$log_id = $wpdb->insert_id;

				// Schedule automatic retry in 1 hour.
				$this->schedule_retry( $log_id );

				return $log_id;
			}

			return false;
		}

		/**
		 * Get error logs
		 *
		 * @param array $args Query arguments.
		 * @return array Array of log entries.
		 */
		public function get_logs( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'per_page' => 20,
				'page'     => 1,
				'status'   => '',
				'crm_type' => '',
				'orderby'  => 'error_date',
				'order'    => 'DESC',
			);

			$args = wp_parse_args( $args, $defaults );

			$where = array( '1=1' );

			if ( ! empty( $args['status'] ) ) {
				$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
			}

			if ( ! empty( $args['crm_type'] ) ) {
				$where[] = $wpdb->prepare( 'crm_type = %s', $args['crm_type'] );
			}

			$where_clause = implode( ' AND ', $where );
			$offset       = ( $args['page'] - 1 ) * $args['per_page'];
			$order_by     = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Variables are properly sanitized above.
			$query = $wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$order_by} LIMIT %d OFFSET %d",
				$args['per_page'],
				$offset
			);
			// phpcs:enable

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above and uses custom table.
			return $wpdb->get_results( $query );
		}

		/**
		 * Get total count of logs
		 *
		 * @param array $args Query arguments.
		 * @return int Total count.
		 */
		public function get_total_count( $args = array() ) {
			global $wpdb;

			$defaults = array(
				'status'   => '',
				'crm_type' => '',
			);

			$args = wp_parse_args( $args, $defaults );

			$where = array( '1=1' );

			if ( ! empty( $args['status'] ) ) {
				$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
			}

			if ( ! empty( $args['crm_type'] ) ) {
				$where[] = $wpdb->prepare( 'crm_type = %s', $args['crm_type'] );
			}

			$where_clause = implode( ' AND ', $where );

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Where clause is properly prepared.
			$query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
			// phpcs:enable

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above and uses custom table.
			return (int) $wpdb->get_var( $query );
		}

		/**
		 * Get log by ID
		 *
		 * @param int $log_id Log ID.
		 * @return object|null Log entry or null.
		 */
		public function get_log( $log_id ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$this->table_name} WHERE id = %d",
					$log_id
				)
			);
		}

		/**
		 * Update log status
		 *
		 * @param int    $log_id Log ID.
		 * @param string $status New status.
		 * @return bool Success status.
		 */
		public function update_status( $log_id, $status ) {
			global $wpdb;

			$update_data = array( 'status' => $status );

			if ( 'resent' === $status || 'success' === $status ) {
				$update_data['last_resend_date'] = current_time( 'mysql' );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->update(
				$this->table_name,
				$update_data,
				array( 'id' => $log_id )
			);
		}

		/**
		 * Increment resend attempts
		 *
		 * @param int $log_id Log ID.
		 * @return bool Success status.
		 */
		public function increment_resend_attempts( $log_id ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"UPDATE {$this->table_name} SET resend_attempts = resend_attempts + 1, last_resend_date = %s WHERE id = %d",
					current_time( 'mysql' ),
					$log_id
				)
			);
		}

		/**
		 * Delete log entry
		 *
		 * @param int $log_id Log ID.
		 * @return bool Success status.
		 */
		public function delete_log( $log_id ) {
			global $wpdb;

			// Clear any scheduled retry before deleting.
			wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->delete(
				$this->table_name,
				array( 'id' => $log_id ),
				array( '%d' )
			);
		}

		/**
		 * Delete all logs
		 *
		 * @return bool Success status.
		 */
		public function clear_all_logs() {
			global $wpdb;

			// Get all log IDs to clear scheduled events.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$log_ids = $wpdb->get_col( "SELECT id FROM {$this->table_name}" );

			// Clear scheduled retries for all logs.
			foreach ( $log_ids as $log_id ) {
				wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->query( "TRUNCATE TABLE {$this->table_name}" );
		}

		/**
		 * AJAX handler for resending entry
		 *
		 * @return void
		 */
		public function ajax_resend_entry() {
			check_ajax_referer( 'formscrm_error_log_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'formscrm' ) ) );
			}

			$log_id = isset( $_POST['log_id'] ) ? intval( $_POST['log_id'] ) : 0;

			if ( ! $log_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid log ID', 'formscrm' ) ) );
			}

			$log = $this->get_log( $log_id );

			if ( ! $log ) {
				wp_send_json_error( array( 'message' => __( 'Log entry not found', 'formscrm' ) ) );
			}

			// Check if we've reached max attempts.
			if ( $log->resend_attempts >= 3 ) {
				wp_send_json_error( array( 'message' => __( 'Max attempts reached', 'formscrm' ) ) );
			}

			// Decode lead data.
			$lead_data = json_decode( $log->lead_data, true );

			if ( ! $lead_data ) {
				wp_send_json_error( array( 'message' => __( 'Invalid lead data', 'formscrm' ) ) );
			}

			// Get CRM settings.
			$settings = formscrm_get_crm_settings( $log->form_type );

			if ( empty( $settings ) ) {
				formscrm_debug_message( 'ERROR: CRM settings not found for form type: ' . $log->form_type );
				wp_send_json_error(
					array(
						'message' => __( 'CRM settings not found. Please configure the CRM connection in FormsCRM settings.', 'formscrm' ),
					)
				);
			}

			// Get CRM API class.
			$api_class = formscrm_get_api_class( $log->crm_type );

			if ( ! $api_class ) {
				$error_msg = sprintf(
					/* translators: %s: CRM type name */
					__( 'CRM API class not found for "%s". Please check if the CRM plugin is active and the library file exists.', 'formscrm' ),
					$log->crm_type
				);

				wp_send_json_error(
					array(
						'message'  => $error_msg,
						'crm_type' => $log->crm_type,
						'log_id'   => $log_id,
					)
				);
			}

			// Verify API class has create_entry method.
			if ( ! method_exists( $api_class, 'create_entry' ) ) {
				$error_msg = sprintf(
					/* translators: %s: CRM type name */
					__( 'CRM API class for "%s" does not have create_entry method.', 'formscrm' ),
					$log->crm_type
				);

				wp_send_json_error( array( 'message' => $error_msg ) );
			}

			$this->increment_resend_attempts( $log_id );

			try {
				$response = $api_class->create_entry( $settings, $lead_data, $log_id );

				if ( isset( $response['status'] ) && 'ok' === strtolower( $response['status'] ) ) {
					$this->update_status( $log_id, 'success' );

					// Clear any scheduled retries.
					wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );

					wp_send_json_success(
						array(
							'message' => __( 'Entry resent successfully', 'formscrm' ),
						)
					);
				} else {
					$error_message = isset( $response['message'] ) ? $response['message'] : __( 'Unknown error occurred', 'formscrm' );

					// Schedule next retry if we haven't reached max attempts.
					$log = $this->get_log( $log_id );
					if ( $log && $log->resend_attempts < 3 ) {
						$this->schedule_retry( $log_id );
					}

					wp_send_json_error(
						array(
							'message' => $error_message,
						)
					);
				}
			} catch ( Exception $e ) {
				// Schedule next retry if we haven't reached max attempts.
				$log = $this->get_log( $log_id );
				if ( $log && $log->resend_attempts < 3 ) {
					$this->schedule_retry( $log_id );
				}

				wp_send_json_error(
					array(
						'message' => $e->getMessage(),
					)
				);
			}
		}

		/**
		 * AJAX handler for deleting log
		 *
		 * @return void
		 */
		public function ajax_delete_log() {
			check_ajax_referer( 'formscrm_error_log_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'formscrm' ) ) );
			}

			$log_id = isset( $_POST['log_id'] ) ? intval( $_POST['log_id'] ) : 0;

			if ( ! $log_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid log ID', 'formscrm' ) ) );
			}

			if ( $this->delete_log( $log_id ) ) {
				wp_send_json_success( array( 'message' => __( 'Log deleted successfully', 'formscrm' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to delete log', 'formscrm' ) ) );
			}
		}

		/**
		 * AJAX handler for clearing all logs
		 *
		 * @return void
		 */
		public function ajax_clear_all_logs() {
			check_ajax_referer( 'formscrm_error_log_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'formscrm' ) ) );
			}

			if ( $this->clear_all_logs() ) {
				wp_send_json_success( array( 'message' => __( 'All logs cleared successfully', 'formscrm' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Failed to clear logs', 'formscrm' ) ) );
			}
		}

		/**
		 * Schedule automatic retry for failed entry
		 *
		 * @param int $log_id Log ID.
		 * @return void
		 */
		private function schedule_retry( $log_id ) {
			$log = $this->get_log( $log_id );

			if ( ! $log ) {
				return;
			}

			// Only schedule if we haven't reached max attempts.
			if ( $log->resend_attempts >= 3 ) {
				return;
			}

			// Schedule retry in 1 hour.
			$timestamp = time() + HOUR_IN_SECONDS;

			// Clear any existing scheduled retry for this log.
			wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );

			// Schedule new retry.
			wp_schedule_single_event( $timestamp, 'formscrm_retry_failed_entry', array( $log_id ) );
		}

		/**
		 * Get next scheduled retry timestamp for a log entry
		 *
		 * @param int $log_id Log ID.
		 * @return int|false Timestamp or false if not scheduled.
		 */
		public function get_next_retry_time( $log_id ) {
			$timestamp = wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id ) );
			return $timestamp;
		}

		/**
		 * Retry failed entry automatically (called by cron)
		 *
		 * @param int $log_id Log ID.
		 * @return void
		 */
		public function retry_failed_entry( $log_id ) {
			$log = $this->get_log( $log_id );

			if ( ! $log || 'failed' !== $log->status ) {
				return;
			}

			// Check if we've reached max attempts.
			if ( $log->resend_attempts >= 3 ) {
				return;
			}

			// Decode lead data.
			$lead_data = json_decode( $log->lead_data, true );

			if ( ! $lead_data ) {
				return;
			}

			// Get CRM settings.
			$settings = formscrm_get_crm_settings( $log->form_type );

			if ( empty( $settings ) ) {
				return;
			}

			// Get CRM API class.
			$api_class = formscrm_get_api_class( $log->crm_type );

			if ( ! $api_class || ! method_exists( $api_class, 'create_entry' ) ) {
				return;
			}

			// Increment attempts before trying.
			$this->increment_resend_attempts( $log_id );

			try {
				$response = $api_class->create_entry( $settings, $lead_data );

				if ( isset( $response['success'] ) && $response['success'] ) {
					// Success - update status.
					$this->update_status( $log_id, 'success' );

					// Clear any scheduled retries.
					wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );
				} else {
					// Failed - check if we should schedule another retry.
					$log = $this->get_log( $log_id );
					if ( $log && $log->resend_attempts < 3 ) {
						$this->schedule_retry( $log_id );
					}
				}
			} catch ( Exception $e ) {
				// Failed - check if we should schedule another retry.
				$log = $this->get_log( $log_id );
				if ( $log && $log->resend_attempts < 3 ) {
					$this->schedule_retry( $log_id );
				}
			}
		}
	}
}

// Initialize error log.
if ( is_admin() ) {
	global $formscrm_error_log;
	$formscrm_error_log = new FORMSCRM_Error_Log();
}
