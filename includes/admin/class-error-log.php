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

require_once dirname( __DIR__ ) . '/formscrm-library/helpers-functions.php';

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
		 * Whether a retry execution is currently in progress.
		 * Prevents insert_log() from creating a new row — and a new scheduler job —
		 * when create_entry() internally triggers formscrm_alert_error().
		 *
		 * @var bool
		 */
		private $is_retrying = false;

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
			add_action( 'wp_ajax_formscrm_export_csv', array( $this, 'ajax_export_csv' ) );
			add_action( 'wp_ajax_formscrm_bulk_delete_logs', array( $this, 'ajax_bulk_delete_logs' ) );
			add_action( 'wp_ajax_formscrm_bulk_resend_logs', array( $this, 'ajax_bulk_resend_logs' ) );

			// Hook for automatic retry via Action Scheduler.
			add_action( 'formscrm_retry_failed_entry', array( $this, 'retry_failed_entry' ), 10, 1 );

			// Prevent Action Scheduler from retrying on its own; FormsCRM manages retries explicitly.
			add_filter( 'action_scheduler_retry_failed_action', array( $this, 'disable_as_retry_for_formscrm' ), 10, 2 );
		}

		/**
		 * Schedule retry using Action Scheduler or WP-Cron fallback
		 *
		 * @param int $log_id Log ID to retry.
		 * @return void
		 */
		private function schedule_action_scheduler_retry( $log_id ) {
			$retry_delay = HOUR_IN_SECONDS;
			$timestamp   = time() + $retry_delay;

			if ( function_exists( 'as_schedule_single_action' ) ) {
				// Skip if a pending AS action already exists for this log.
				if ( as_has_scheduled_action( 'formscrm_retry_failed_entry', array( $log_id ) ) ) {
					return;
				}
				try {
					as_schedule_single_action( $timestamp, 'formscrm_retry_failed_entry', array( $log_id ) );
					return;
				} catch ( Exception $e ) {
					formscrm_debug_message( "AS schedule failed for log {$log_id}, falling back to WP-Cron: {$e->getMessage()}" );
				}
			}

			// Fallback to WP-Cron if Action Scheduler not available.
			if ( ! wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id ) ) ) {
				wp_schedule_single_event( $timestamp, 'formscrm_retry_failed_entry', array( $log_id ) );
			}
		}

		/**
		 * Cancel all pending retry actions for a log entry (Action Scheduler + WP-Cron).
		 *
		 * @param int $log_id Log ID.
		 * @return void
		 */
		private function cancel_scheduled_retry( $log_id ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( 'formscrm_retry_failed_entry', array( $log_id ) );
			}
			wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );
		}

		/**
		 * Prevent Action Scheduler from retrying formscrm_retry_failed_entry on its own.
		 * FormsCRM manages retry scheduling explicitly via schedule_retry().
		 *
		 * @param int    $attempts Number of retries AS would make.
		 * @param object $action   The AS action object.
		 * @return int
		 */
		public function disable_as_retry_for_formscrm( $attempts, $action ) {
			if ( 'formscrm_retry_failed_entry' === $action->get_hook() ) {
				return 0;
			}
			return $attempts;
		}

		/**
		 * Check database version and create/update table if needed
		 *
		 * @return void
		 */
		public function check_database_version() {
			$installed_version = get_option( 'formscrm_error_log_db_version', '0' );
			$current_version   = '1.2';

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
			// Do not create a new log row during a retry: it would reset resend_attempts to 0
			// and schedule an extra Action Scheduler job, bypassing the 3-attempt cap.
			if ( $this->is_retrying ) {
				return false;
			}

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

				// Schedule automatic retry using Action Scheduler (if available).
				$this->schedule_action_scheduler_retry( $log_id );

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

			// Cancel any scheduled retry before deleting.
			$this->cancel_scheduled_retry( $log_id );

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

			// Cancel scheduled retries for all logs.
			foreach ( $log_ids as $log_id ) {
				$this->cancel_scheduled_retry( $log_id );
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

			// Decode lead data.
			$lead_data = json_decode( $log->lead_data, true );

			if ( ! $lead_data ) {
				wp_send_json_error( array( 'message' => __( 'Invalid lead data', 'formscrm' ) ) );
			}

			// Get CRM settings.
			$settings = formscrm_get_crm_settings( $log->form_type );

			if ( empty( $settings ) ) {
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

			try {
				$response = $api_class->create_entry( $settings, $lead_data, $log_id );

				if ( isset( $response['status'] ) && 'ok' === strtolower( $response['status'] ) ) {
					$this->update_status( $log_id, 'success' );

					// Cancel any scheduled retries.
					$this->cancel_scheduled_retry( $log_id );

					formscrm_add_entry_note(
						$log->form_type,
						$log->entry_id,
						sprintf(
							/* translators: %s: CRM name */
							__( 'FormsCRM manual resend success (%s)', 'formscrm' ),
							esc_html( $log->crm_type )
						),
						'success'
					);

					wp_send_json_success(
						array(
							'message' => __( 'Entry resent successfully', 'formscrm' ),
						)
					);
				} else {
					$error_message = isset( $response['message'] ) ? $response['message'] : __( 'Unknown error occurred', 'formscrm' );

					formscrm_add_entry_note(
						$log->form_type,
						$log->entry_id,
						sprintf(
							/* translators: %1$s: CRM name, %2$s: error message */
							__( 'FormsCRM manual resend failed (%1$s): %2$s', 'formscrm' ),
							esc_html( $log->crm_type ),
							esc_html( $error_message )
						),
						'error'
					);

					wp_send_json_error(
						array(
							'message' => $error_message,
						)
					);
				}
			} catch ( Exception $e ) {
				formscrm_add_entry_note(
					$log->form_type,
					$log->entry_id,
					sprintf(
						/* translators: %1$s: CRM name, %2$s: exception message */
						__( 'FormsCRM manual resend error (%1$s): %2$s', 'formscrm' ),
						esc_html( $log->crm_type ),
						esc_html( $e->getMessage() )
					),
					'error'
				);

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
		 * AJAX handler for exporting logs to CSV
		 *
		 * @return void
		 */
		public function ajax_export_csv() {
			check_ajax_referer( 'formscrm_error_log_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'formscrm' ) ) );
			}

			$date_from = isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '';
			$date_to   = isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '';

			// Validate date format (YYYY-MM-DD) only when provided.
			if ( ! empty( $date_from ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid date format', 'formscrm' ) ) );
			}
			if ( ! empty( $date_to ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid date format', 'formscrm' ) ) );
			}

			$csv_data = $this->export_csv( $date_from, $date_to );

			if ( ! $csv_data ) {
				wp_send_json_error( array( 'message' => __( 'No logs found', 'formscrm' ) ) );
			}

			// Generate CSV content in memory.
			$csv_content = $this->generate_csv_content( $csv_data );

			// Build filename based on whether dates were provided.
			if ( $date_from && $date_to ) {
				$filename = 'formscrm-error-logs-' . $date_from . '-to-' . $date_to . '.csv';
			} else {
				$filename = 'formscrm-error-logs-all.csv';
			}

			// Return CSV content to client for download.
			wp_send_json_success(
				array(
					'csv_content' => $csv_content,
					'filename'    => $filename,
				)
			);
		}

		/**
		 * Generate CSV content from array data
		 *
		 * @param array $csv_data Array of rows to export.
		 * @return string CSV formatted string.
		 */
		private function generate_csv_content( $csv_data ) {
			$output = '';

			foreach ( $csv_data as $row ) {
				$output .= $this->escape_csv_row( $row ) . "\n";
			}

			return $output;
		}

		/**
		 * Escape and format a single CSV row
		 *
		 * @param array $row Row data.
		 * @return string Formatted CSV row.
		 */
		private function escape_csv_row( $row ) {
			$escaped = array();

			foreach ( $row as $field ) {
				if ( null === $field ) {
					$escaped[] = '';
				} elseif ( strpos( $field, '"' ) !== false || strpos( $field, ',' ) !== false || strpos( $field, "\n" ) !== false ) {
					$escaped[] = '"' . str_replace( '"', '""', $field ) . '"';
				} else {
					$escaped[] = $field;
				}
			}

			return implode( ',', $escaped );
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

			// Use Action Scheduler (same as initial schedule).
			$this->schedule_action_scheduler_retry( $log_id );
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
		 * Export logs to CSV within date range
		 *
		 * @param string $date_from Start date (Y-m-d format).
		 * @param string $date_to   End date (Y-m-d format).
		 * @return array|false CSV data or false on failure.
		 */
		public function export_csv( $date_from, $date_to ) {
			global $wpdb;

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! empty( $date_from ) && ! empty( $date_to ) ) {
				// Convert dates to MySQL datetime format (start and end of day).
				$from_datetime = $date_from . ' 00:00:00';
				$to_datetime   = $date_to . ' 23:59:59';

				$query = $wpdb->prepare(
					"SELECT id, error_date, crm_type, form_type, form_type_title, form_name, entry_id, error_message, status, resend_attempts, last_resend_date
					 FROM {$this->table_name}
					 WHERE error_date >= %s AND error_date <= %s
					 ORDER BY error_date DESC",
					$from_datetime,
					$to_datetime
				);
			} else {
				$query = "SELECT id, error_date, crm_type, form_type, form_type_title, form_name, entry_id, error_message, status, resend_attempts, last_resend_date
					 FROM {$this->table_name}
					 ORDER BY error_date DESC";
			}
			// phpcs:enable

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$logs = $wpdb->get_results( $query );

			if ( empty( $logs ) ) {
				return false;
			}

			// Prepare CSV headers.
			$headers = array(
				'ID',
				'Date',
				'CRM Type',
				'Form Type',
				'Form Name',
				'Entry ID',
				'Error Message',
				'Status',
				'Resend Attempts',
				'Last Resend Date',
			);

			$csv_data = array( $headers );

			// Add rows.
			foreach ( $logs as $log ) {
				$csv_data[] = array(
					$log->id,
					$log->error_date,
					$log->crm_type,
					$log->form_type,
					$log->form_name ?? '',
					$log->entry_id ?? '',
					$log->error_message,
					$log->status,
					$log->resend_attempts,
					$log->last_resend_date ?? '',
				);
			}

			return $csv_data;
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
				formscrm_debug_message( "Retry skipped for log {$log_id}: log not found or not in failed status" );
				return;
			}

			// Check if we've reached max attempts.
			if ( $log->resend_attempts >= 3 ) {
				formscrm_debug_message( "Retry skipped for log {$log_id}: max attempts reached ({$log->resend_attempts}/3)" );
				return;
			}

			formscrm_debug_message( "Starting auto-retry for log {$log_id} (attempt {$log->resend_attempts}/3)" );

			// Decode lead data.
			$lead_data = json_decode( $log->lead_data, true );

			if ( ! $lead_data ) {
				formscrm_debug_message( "Retry failed for log {$log_id}: invalid lead data" );
				return;
			}

			// Get CRM settings.
			$settings = formscrm_get_crm_settings( $log->form_type );

			if ( empty( $settings ) ) {
				formscrm_debug_message( "Retry failed for log {$log_id}: no CRM settings found for form type {$log->form_type}" );
				return;
			}

			// Get CRM API class.
			$api_class = formscrm_get_api_class( $log->crm_type );

			if ( ! $api_class || ! method_exists( $api_class, 'create_entry' ) ) {
				formscrm_debug_message( "Retry failed for log {$log_id}: CRM API class not found or missing create_entry method" );
				return;
			}

			// Increment attempts before trying.
			$this->increment_resend_attempts( $log_id );

			$this->is_retrying = true;
			try {
				$response = $api_class->create_entry( $settings, $lead_data, $log_id );

				if ( isset( $response['status'] ) && 'ok' === strtolower( $response['status'] ) ) {
					// Success - update status.
					$this->update_status( $log_id, 'success' );
					formscrm_debug_message( "Auto-retry SUCCESS for log {$log_id}: status updated to 'success'" );

					// Cancel any pending scheduled retries (AS + WP-Cron).
					$this->cancel_scheduled_retry( $log_id );

					formscrm_add_entry_note(
						$log->form_type,
						$log->entry_id,
						sprintf(
							/* translators: %1$s: CRM name, %2$s: attempt number */
							__( 'FormsCRM auto-retry success (%1$s) - Attempt %2$s/3', 'formscrm' ),
							esc_html( $log->crm_type ),
							esc_html( (string) $log->resend_attempts )
						),
						'success'
					);

					// Clear any scheduled retries.
					wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );
				} else {
					$error_msg = isset( $response['message'] ) ? $response['message'] : 'Unknown error';
					formscrm_debug_message( "Auto-retry FAILED for log {$log_id}: {$error_msg}" );

					formscrm_add_entry_note(
						$log->form_type,
						$log->entry_id,
						sprintf(
							/* translators: %1$s: CRM name, %2$s: attempt number, %3$s: error message */
							__( 'FormsCRM auto-retry failed (%1$s) - Attempt %2$s/3: %3$s', 'formscrm' ),
							esc_html( $log->crm_type ),
							esc_html( (string) $log->resend_attempts ),
							esc_html( $error_msg )
						),
						'error'
					);

					// Failed - check if we should schedule another retry.
					$log = $this->get_log( $log_id );
					if ( $log && $log->resend_attempts < 3 ) {
						$this->schedule_retry( $log_id );
						formscrm_debug_message( "Scheduled next retry for log {$log_id}" );
					} else {
						wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );
						formscrm_debug_message( "No more retries scheduled for log {$log_id}: max attempts reached" );
					}
				}
			} catch ( Exception $e ) {
				formscrm_debug_message( "Auto-retry EXCEPTION for log {$log_id}: {$e->getMessage()}" );

				formscrm_add_entry_note(
					$log->form_type,
					$log->entry_id,
					sprintf(
						/* translators: %1$s: CRM name, %2$s: attempt number, %3$s: exception message */
						__( 'FormsCRM auto-retry error (%1$s) - Attempt %2$s/3: %3$s', 'formscrm' ),
						esc_html( $log->crm_type ),
						esc_html( (string) $log->resend_attempts ),
						esc_html( $e->getMessage() )
					),
					'error'
				);

				// Failed - check if we should schedule another retry.
				$log = $this->get_log( $log_id );
				if ( $log && $log->resend_attempts < 3 ) {
					$this->schedule_retry( $log_id );
					formscrm_debug_message( "Scheduled next retry for log {$log_id}" );
				} else {
					wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );
					formscrm_debug_message( "No more retries scheduled for log {$log_id}: max attempts reached" );
				}
			} finally {
				$this->is_retrying = false;
			}
		}

		/**
		 * AJAX handler for bulk deleting logs
		 *
		 * @return void
		 */
		public function ajax_bulk_delete_logs() {
			check_ajax_referer( 'formscrm_error_log_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'formscrm' ) ) );
			}

			$log_ids = isset( $_POST['log_ids'] ) ? array_map( 'intval', wp_unslash( $_POST['log_ids'] ) ) : array();

			if ( empty( $log_ids ) ) {
				wp_send_json_error( array( 'message' => __( 'No logs selected', 'formscrm' ) ) );
			}

			foreach ( $log_ids as $log_id ) {
				$this->delete_log( $log_id );
			}

			wp_send_json_success( array( 'message' => __( 'Selected logs deleted successfully', 'formscrm' ) ) );
		}

		/**
		 * AJAX handler for bulk resending logs
		 *
		 * Enqueues all selected logs for resend via Action Scheduler.
		 * Returns immediately; processing happens in background.
		 *
		 * @return void
		 */
		public function ajax_bulk_resend_logs() {
			check_ajax_referer( 'formscrm_error_log_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied', 'formscrm' ) ) );
			}

			$log_ids = isset( $_POST['log_ids'] ) ? array_map( 'intval', wp_unslash( $_POST['log_ids'] ) ) : array();

			if ( empty( $log_ids ) ) {
				wp_send_json_error( array( 'message' => __( 'No logs selected', 'formscrm' ) ) );
			}

			// Enqueue all logs for resend via Action Scheduler (stagger by 1 second each).
			$base_time = time();
			$index     = 0;

			foreach ( $log_ids as $log_id ) {
				$scheduled_time = $base_time + $index;

				if ( function_exists( 'as_schedule_single_action' ) ) {
					// Skip if a pending AS action already exists for this log.
					if ( ! as_has_scheduled_action( 'formscrm_retry_failed_entry', array( $log_id ) ) ) {
						try {
							as_schedule_single_action( $scheduled_time, 'formscrm_retry_failed_entry', array( $log_id ) );
						} catch ( Exception $e ) {
							if ( ! wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id ) ) ) {
								wp_schedule_single_event( $scheduled_time, 'formscrm_retry_failed_entry', array( $log_id ) );
							}
						}
					}
				} elseif ( ! wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id ) ) ) {
					wp_schedule_single_event( $scheduled_time, 'formscrm_retry_failed_entry', array( $log_id ) );
				}
				++$index;
			}

			wp_send_json_success(
				array(
					'success' => count( $log_ids ),
					'failed'  => 0,
				)
			);
		}
	}
}

// Initialize error log.
global $formscrm_error_log;
$formscrm_error_log = new FORMSCRM_Error_Log();
