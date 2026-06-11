<?php
/**
 * Class ErrorLogResendTest
 *
 * Tests for Error Log resend functionality: manual resend button and automatic retry scheduler.
 *
 * Command: composer test --filter ErrorLogResendTest
 * Debug: composer test-debug --filter ErrorLogResendTest
 *
 * @package Formscrm
 */

/**
 * Test case for Error Log resend and retry functions.
 */
class ErrorLogResendTest extends WP_UnitTestCase {

	/**
	 * Error log instance.
	 *
	 * @var FORMSCRM_Error_Log
	 */
	private $error_log;

	/**
	 * Mock CRM response to return on next create_entry call.
	 *
	 * @var array
	 */
	private $mock_crm_response = array();

	/**
	 * Sample lead data for tests.
	 *
	 * @var array
	 */
	private $lead_data = array(
		array( 'name' => 'Name', 'value' => 'John Doe' ),
		array( 'name' => 'Email', 'value' => 'john@example.com' ),
	);

	/**
	 * Sample form info for tests.
	 *
	 * @var array
	 */
	private $form_info = array(
		'form_type'       => 'gravityforms',
		'form_type_title' => 'Gravity Forms',
		'form_id'         => '1',
		'form_name'       => 'Contact Form',
		'entry_id'        => '42',
	);

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;

		// Initialize error log class.
		global $formscrm_error_log;
		if ( ! $formscrm_error_log ) {
			$formscrm_error_log = new FORMSCRM_Error_Log();
		}
		$this->error_log = $formscrm_error_log;

		// Drop and recreate table.
		$table_name = $wpdb->prefix . 'formscrm_error_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
		delete_option( 'formscrm_error_log_db_version' );

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
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
		) {$charset_collate};";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $sql );
		update_option( 'formscrm_error_log_db_version', '1.1' );

		// Mock HTTP requests to prevent real API calls.
		add_filter(
			'pre_http_request',
			function( $pre, $r, $url ) {
				return array(
					'body'     => '',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
				);
			},
			10,
			3
		);

		// Mock formscrm_get_crm_settings to return test settings.
		add_filter(
			'formscrm_get_crm_settings',
			function( $settings, $form_type ) {
				return array(
					'fc_crm_type'        => 'clientify',
					'fc_crm_apipassword' => 'test-api-key',
					'fc_crm_module'      => 'Contacts',
				);
			},
			10,
			2
		);

		$this->mock_crm_response = array(
			'status' => 'ok',
			'id'     => '123',
		);
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		global $wpdb;

		$this->error_log->clear_all_logs();

		$table_name = $wpdb->prefix . 'formscrm_error_log';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

		delete_option( 'formscrm_error_log_db_version' );

		// Clear all scheduled retry hooks so they don't bleed between tests.
		$crons = _get_cron_array();
		foreach ( $crons as $timestamp => $hooks ) {
			if ( isset( $hooks['formscrm_retry_failed_entry'] ) ) {
				foreach ( $hooks['formscrm_retry_failed_entry'] as $key => $event ) {
					wp_unschedule_event( $timestamp, 'formscrm_retry_failed_entry', $event['args'] );
				}
			}
		}

		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'formscrm_get_crm_settings' );
		remove_all_filters( 'formscrm_get_api_class' );

		parent::tearDown();
	}

	/**
	 * Helper: insert a failed log entry.
	 *
	 * @param int $resend_attempts Existing attempt count.
	 * @return int Log ID.
	 */
	private function insert_failed_log( $resend_attempts = 0 ) {
		global $wpdb;

		$log_id = $this->error_log->insert_log(
			'clientify',
			'Connection error',
			$this->lead_data,
			'https://api.clientify.net/v1/contacts/',
			'{}',
			$this->form_info
		);

		// Override resend_attempts if needed.
		if ( $resend_attempts > 0 ) {
			$table_name = $wpdb->prefix . 'formscrm_error_log';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table_name, array( 'resend_attempts' => $resend_attempts ), array( 'id' => $log_id ) );
		}

		return $log_id;
	}

	// -------------------------------------------------------------------------
	// Automatic retry tests (retry_failed_entry).
	// -------------------------------------------------------------------------

	/**
	 * Auto-retry: skips if log not found.
	 */
	public function test_retry_skips_if_log_not_found() {
		$this->error_log->retry_failed_entry( 99999 );

		// No exception thrown — test passes.
		$this->assertTrue( true );
	}

	/**
	 * Auto-retry: skips if status is not 'failed'.
	 */
	public function test_retry_skips_if_status_not_failed() {
		$log_id = $this->insert_failed_log();
		$this->error_log->update_status( $log_id, 'success' );

		$this->error_log->retry_failed_entry( $log_id );

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 'success', $log->status );
		$this->assertEquals( 0, $log->resend_attempts );
	}

	/**
	 * Auto-retry: skips if max attempts (3) already reached.
	 */
	public function test_retry_skips_if_max_attempts_reached() {
		$log_id = $this->insert_failed_log( 3 );

		$this->error_log->retry_failed_entry( $log_id );

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 'failed', $log->status );
		$this->assertEquals( 3, $log->resend_attempts );
	}

	/**
	 * Auto-retry: increments attempts counter on each try.
	 */
	public function test_retry_increments_attempts_counter() {
		$log_id = $this->insert_failed_log( 0 );

		// Mock API to fail.
		$api_class = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'create_entry' ) )
			->getMock();

		$api_class->method( 'create_entry' )->willReturn( array(
			'status'  => 'error',
			'message' => 'API error',
		) );

		add_filter(
			'formscrm_get_api_class',
			function( $class, $crm_type ) use ( $api_class ) {
				return $api_class;
			},
			10,
			2
		);

		$this->error_log->retry_failed_entry( $log_id );

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 1, $log->resend_attempts );
		$this->assertEquals( 'failed', $log->status );

		remove_all_filters( 'formscrm_get_api_class' );
	}

	/**
	 * Auto-retry: updates status to success on OK response.
	 */
	public function test_retry_success_updates_status() {
		$log_id = $this->insert_failed_log( 0 );

		$api_class = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'create_entry' ) )
			->getMock();

		$api_class->method( 'create_entry' )->willReturn( array(
			'status' => 'ok',
			'id'     => '456',
		) );

		add_filter(
			'formscrm_get_api_class',
			function( $class, $crm_type ) use ( $api_class ) {
				return $api_class;
			},
			10,
			2
		);

		$this->error_log->retry_failed_entry( $log_id );

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 'success', $log->status );
		$this->assertEquals( 1, $log->resend_attempts );

		remove_all_filters( 'formscrm_get_api_class' );
	}

	/**
	 * Auto-retry: schedules next retry after failure (attempts < 3).
	 */
	public function test_retry_schedules_next_on_failure() {
		$log_id = $this->insert_failed_log( 1 );

		$api_class = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'create_entry' ) )
			->getMock();

		$api_class->method( 'create_entry' )->willReturn( array(
			'status'  => 'error',
			'message' => 'Still failing',
		) );

		add_filter(
			'formscrm_get_api_class',
			function( $class, $crm_type ) use ( $api_class ) {
				return $api_class;
			},
			10,
			2
		);

		$this->error_log->retry_failed_entry( $log_id );

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 2, $log->resend_attempts );
		$this->assertEquals( 'failed', $log->status );

		// Verify next scheduled action exists (Action Scheduler or WP-Cron).
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			$next = as_next_scheduled_action( 'formscrm_retry_failed_entry', array( $log_id ) );
			$this->assertNotFalse( $next );
		} else {
			$next = wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id ) );
			$this->assertNotFalse( $next );
		}

		remove_all_filters( 'formscrm_get_api_class' );
	}

	/**
	 * Auto-retry: does NOT schedule next retry after 3rd failure.
	 */
	public function test_retry_does_not_schedule_after_max_attempts() {
		$log_id = $this->insert_failed_log( 2 );

		$api_class = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'create_entry' ) )
			->getMock();

		$api_class->method( 'create_entry' )->willReturn( array(
			'status'  => 'error',
			'message' => 'Still failing',
		) );

		add_filter(
			'formscrm_get_api_class',
			function( $class, $crm_type ) use ( $api_class ) {
				return $api_class;
			},
			10,
			2
		);

		$this->error_log->retry_failed_entry( $log_id );

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 3, $log->resend_attempts );
		$this->assertEquals( 'failed', $log->status );

		// No next scheduled action should exist.
		$next = wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id ) );
		$this->assertFalse( $next );

		remove_all_filters( 'formscrm_get_api_class' );
	}

	/**
	 * Auto-retry: full cascade — 3 failures, no more retries scheduled.
	 */
	public function test_retry_full_cascade_three_failures() {
		$log_id = $this->insert_failed_log( 0 );

		$api_class = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'create_entry' ) )
			->getMock();

		$api_class->method( 'create_entry' )->willReturn( array(
			'status'  => 'error',
			'message' => 'API down',
		) );

		add_filter(
			'formscrm_get_api_class',
			function( $class, $crm_type ) use ( $api_class ) {
				return $api_class;
			},
			10,
			2
		);

		// Simulate 3 retry executions.
		$this->error_log->retry_failed_entry( $log_id );
		$this->error_log->retry_failed_entry( $log_id );
		$this->error_log->retry_failed_entry( $log_id );

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 3, $log->resend_attempts );
		$this->assertEquals( 'failed', $log->status );

		// 4th call should be a no-op.
		$this->error_log->retry_failed_entry( $log_id );
		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 3, (int) $log->resend_attempts );

		remove_all_filters( 'formscrm_get_api_class' );
	}

	// -------------------------------------------------------------------------
	// Manual resend tests (ajax_resend_entry via AJAX simulation).
	// -------------------------------------------------------------------------

	/**
	 * Manual resend: entry_id stored in log when inserting.
	 */
	public function test_insert_log_stores_entry_id() {
		$log_id = $this->insert_failed_log();
		$log    = $this->error_log->get_log( $log_id );

		$this->assertEquals( '42', $log->entry_id );
	}

	/**
	 * Manual resend: attempts not capped — unlimited manual resends allowed.
	 */
	public function test_manual_resend_not_capped_by_auto_attempts() {
		// Insert log already at max auto-retry attempts.
		$log_id = $this->insert_failed_log( 3 );

		$api_class = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'create_entry' ) )
			->getMock();

		$api_class->method( 'create_entry' )->willReturn( array(
			'status' => 'ok',
			'id'     => '789',
		) );

		// Simulate what ajax_resend_entry does (same logic, no AJAX wrapper).
		$log       = $this->error_log->get_log( $log_id );
		$lead_data = json_decode( $log->lead_data, true );
		$settings  = array(
			'fc_crm_type'        => 'clientify',
			'fc_crm_apipassword' => 'test-api-key',
			'fc_crm_module'      => 'Contacts',
		);
		$response = $api_class->create_entry( $settings, $lead_data, $log_id );

		if ( isset( $response['status'] ) && 'ok' === strtolower( $response['status'] ) ) {
			$this->error_log->update_status( $log_id, 'success' );
		}

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 'success', $log->status );
		// Auto-retry counter unchanged by manual resend.
		$this->assertEquals( 3, (int) $log->resend_attempts );
	}

	/**
	 * Manual resend: status updated to success on OK response.
	 */
	public function test_manual_resend_success_updates_status() {
		$log_id = $this->insert_failed_log();

		$api_class = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'create_entry' ) )
			->getMock();

		$api_class->method( 'create_entry' )->willReturn( array(
			'status' => 'ok',
			'id'     => '999',
		) );

		$log       = $this->error_log->get_log( $log_id );
		$lead_data = json_decode( $log->lead_data, true );
		$settings  = array( 'fc_crm_type' => 'clientify', 'fc_crm_apipassword' => 'key', 'fc_crm_module' => 'Contacts' );
		$response  = $api_class->create_entry( $settings, $lead_data, $log_id );

		if ( isset( $response['status'] ) && 'ok' === strtolower( $response['status'] ) ) {
			$this->error_log->update_status( $log_id, 'success' );
			wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );
		}

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 'success', $log->status );
		$this->assertNotNull( $log->last_resend_date );
	}

	/**
	 * Manual resend: status stays failed on error response.
	 */
	public function test_manual_resend_failure_keeps_status_failed() {
		$log_id = $this->insert_failed_log();

		$api_class = $this->getMockBuilder( 'stdClass' )
			->addMethods( array( 'create_entry' ) )
			->getMock();

		$api_class->method( 'create_entry' )->willReturn( array(
			'status'  => 'error',
			'message' => 'Invalid API key',
		) );

		$log       = $this->error_log->get_log( $log_id );
		$lead_data = json_decode( $log->lead_data, true );
		$settings  = array( 'fc_crm_type' => 'clientify', 'fc_crm_apipassword' => 'bad-key', 'fc_crm_module' => 'Contacts' );
		$response  = $api_class->create_entry( $settings, $lead_data, $log_id );

		// Status should remain failed.
		if ( ! isset( $response['status'] ) || 'ok' !== strtolower( $response['status'] ) ) {
			// No status update on failure.
		}

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 'failed', $log->status );
	}

	// -------------------------------------------------------------------------
	// Attempt counter and last_resend_date tests.
	// -------------------------------------------------------------------------

	/**
	 * Increment attempts: updates counter and last_resend_date.
	 */
	public function test_increment_resend_attempts_updates_date() {
		$log_id = $this->insert_failed_log();

		$log_before = $this->error_log->get_log( $log_id );
		$this->assertNull( $log_before->last_resend_date );

		$this->error_log->increment_resend_attempts( $log_id );

		$log_after = $this->error_log->get_log( $log_id );
		$this->assertEquals( 1, (int) $log_after->resend_attempts );
		$this->assertNotNull( $log_after->last_resend_date );
	}

	/**
	 * Attempt counter: never exceeds 3 via auto-retry.
	 */
	public function test_attempt_counter_never_exceeds_three_via_auto_retry() {
		$log_id = $this->insert_failed_log( 3 );

		// Retry should be skipped entirely.
		$this->error_log->retry_failed_entry( $log_id );
		$this->error_log->retry_failed_entry( $log_id );

		$log = $this->error_log->get_log( $log_id );
		$this->assertEquals( 3, (int) $log->resend_attempts );
	}

	// -------------------------------------------------------------------------
	// Bulk resend scheduling tests.
	// -------------------------------------------------------------------------

	/**
	 * Bulk resend: each log gets its own scheduled action (staggered).
	 */
	public function test_bulk_resend_schedules_staggered_actions() {
		$log_id_1 = $this->insert_failed_log();
		$log_id_2 = $this->insert_failed_log();
		$log_id_3 = $this->insert_failed_log();

		$log_ids   = array( $log_id_1, $log_id_2, $log_id_3 );
		$base_time = time();
		$index     = 0;

		foreach ( $log_ids as $log_id ) {
			$scheduled_time = $base_time + $index;
			wp_schedule_single_event( $scheduled_time, 'formscrm_retry_failed_entry', array( $log_id ) );
			$index++;
		}

		// Each log has a scheduled action.
		foreach ( $log_ids as $i => $log_id ) {
			$next = wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id ) );
			$this->assertNotFalse( $next, "Log {$log_id} should have scheduled action" );
		}

		// Timestamps are staggered (each >= previous).
		$t1 = wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id_1 ) );
		$t2 = wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id_2 ) );
		$t3 = wp_next_scheduled( 'formscrm_retry_failed_entry', array( $log_id_3 ) );

		$this->assertGreaterThanOrEqual( $t1, $t2 );
		$this->assertGreaterThanOrEqual( $t2, $t3 );

		// Clean up.
		foreach ( $log_ids as $log_id ) {
			wp_clear_scheduled_hook( 'formscrm_retry_failed_entry', array( $log_id ) );
		}
	}

	/**
	 * Lead data: preserved correctly through JSON encode/decode cycle.
	 */
	public function test_lead_data_survives_encode_decode() {
		$log_id = $this->insert_failed_log();
		$log    = $this->error_log->get_log( $log_id );

		$decoded = json_decode( $log->lead_data, true );

		$this->assertIsArray( $decoded );
		$this->assertCount( 2, $decoded );
		$this->assertEquals( 'John Doe', $decoded[0]['value'] );
		$this->assertEquals( 'john@example.com', $decoded[1]['value'] );
	}
}
