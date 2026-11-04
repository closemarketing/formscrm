<?php
/**
 * Class SyncStatusTest
 *
 * Tests for the sync status store and the Gravity Forms entry list columns.
 *
 * Command: composer test --filter SyncStatusTest
 *
 * @package Formscrm
 */

/**
 * Test case for FormsCRM_Sync_Status and FormsCRM_GravityForms_Entry_Columns.
 */
class SyncStatusTest extends WP_UnitTestCase {

	/**
	 * Set up: make sure the table exists and is empty.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wpdb;
		delete_option( FormsCRM_Sync_Status::DB_VERSION_OPTION );
		FormsCRM_Sync_Status::maybe_create_table();
		$wpdb->query( 'DELETE FROM ' . FormsCRM_Sync_Status::table_name() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Form type aliases are normalized.
	 */
	public function test_normalize_form_type() {
		$this->assertSame( 'gravityforms', FormsCRM_Sync_Status::normalize_form_type( 'gravity' ) );
		$this->assertSame( 'gravityforms', FormsCRM_Sync_Status::normalize_form_type( 'gravityforms' ) );
		$this->assertSame( 'wpforms', FormsCRM_Sync_Status::normalize_form_type( 'WPForms' ) );
	}

	/**
	 * Status values are reduced to success/error.
	 */
	public function test_normalize_status() {
		$this->assertSame( 'success', FormsCRM_Sync_Status::normalize_status( 'success' ) );
		$this->assertSame( 'success', FormsCRM_Sync_Status::normalize_status( 'OK' ) );
		$this->assertSame( 'error', FormsCRM_Sync_Status::normalize_status( 'failed' ) );
		$this->assertSame( 'error', FormsCRM_Sync_Status::normalize_status( '' ) );
	}

	/**
	 * Invalid entry or CRM are rejected without touching the table.
	 */
	public function test_record_rejects_invalid_input() {
		$this->assertFalse( FormsCRM_Sync_Status::record( 'gravityforms', 1, 0, 'clientify', array( 'status' => 'success' ) ) );
		$this->assertFalse( FormsCRM_Sync_Status::record( 'gravityforms', 1, 5, '', array( 'status' => 'success' ) ) );
	}

	/**
	 * A new record is stored and a later one replaces it (resend / retry).
	 */
	public function test_record_is_replaced_by_latest_result() {
		formscrm_record_sync_status(
			'gravityforms',
			7,
			100,
			'clientify',
			array(
				'status'  => 'error',
				'message' => 'Timeout',
			)
		);
		$row = FormsCRM_Sync_Status::get_one( 'gravityforms', 100, 'clientify' );
		$this->assertSame( 'error', $row->status );
		$this->assertSame( 'Timeout', $row->message );

		formscrm_record_sync_status(
			'gravityforms',
			7,
			100,
			'clientify',
			array(
				'status'     => 'success',
				'contact_id' => 555,
				'crm_name'   => 'Clientify',
			)
		);
		$row = FormsCRM_Sync_Status::get_one( 'gravityforms', 100, 'clientify' );
		$this->assertSame( 'success', $row->status );
		$this->assertSame( '555', $row->contact_id );
		$this->assertCount( 1, FormsCRM_Sync_Status::get_for_entry( 'gravityforms', 100 ) );
	}

	/**
	 * A failed resend keeps the contact ID created by a previous success.
	 */
	public function test_failed_resend_keeps_previous_contact_id() {
		formscrm_record_sync_status(
			'gravityforms',
			7,
			101,
			'holded',
			array(
				'status'     => 'success',
				'contact_id' => 'abc123',
			)
		);
		formscrm_record_sync_status(
			'gravityforms',
			7,
			101,
			'holded',
			array(
				'status'  => 'error',
				'message' => 'API down',
			)
		);

		$row = FormsCRM_Sync_Status::get_one( 'gravityforms', 101, 'holded' );
		$this->assertSame( 'error', $row->status );
		$this->assertSame( 'abc123', $row->contact_id );
	}

	/**
	 * Several CRMs on the same entry are stored independently.
	 */
	public function test_multiple_crms_per_entry() {
		formscrm_record_sync_status(
			'gravityforms',
			7,
			102,
			'clientify',
			array(
				'status'     => 'success',
				'contact_id' => 1,
			)
		);
		formscrm_record_sync_status(
			'gravityforms',
			7,
			102,
			'brevo',
			array(
				'status'  => 'error',
				'message' => 'Bad key',
			)
		);

		$rows = FormsCRM_Sync_Status::get_for_entry( 'gravityforms', 102 );
		$this->assertCount( 2, $rows );
		$this->assertSame( 'success', $rows['clientify']->status );
		$this->assertSame( 'error', $rows['brevo']->status );
		$this->assertSame( array( 'brevo', 'clientify' ), FormsCRM_Sync_Status::get_crm_types_for_form( 'gravityforms', 7 ) );
	}

	/**
	 * The legacy 'gravity' slug used by the error log hits the same row.
	 */
	public function test_legacy_gravity_slug_shares_rows() {
		formscrm_record_sync_status(
			'gravity',
			7,
			103,
			'clientify',
			array(
				'status'     => 'success',
				'contact_id' => 9,
			)
		);
		$this->assertNotNull( FormsCRM_Sync_Status::get_one( 'gravityforms', 103, 'clientify' ) );
	}

	/**
	 * Rows are removed with the entry.
	 */
	public function test_delete_for_entry() {
		formscrm_record_sync_status( 'gravityforms', 7, 104, 'clientify', array( 'status' => 'success' ) );
		FormsCRM_Sync_Status::delete_for_entry( 'gravityforms', 104 );
		$this->assertSame( array(), FormsCRM_Sync_Status::get_for_entry( 'gravityforms', 104 ) );
	}

	/**
	 * Column keys round trip, including CRM slugs with underscores.
	 */
	public function test_column_key_round_trip() {
		$key = FormsCRM_GravityForms_Entry_Columns::column_key( 'mailerlite_classic', 'status' );
		$this->assertSame( array( 'mailerlite_classic', 'status' ), FormsCRM_GravityForms_Entry_Columns::parse_column_key( $key ) );
		$this->assertSame( array( 'clientify', 'id' ), FormsCRM_GravityForms_Entry_Columns::parse_column_key( 'formscrm_clientify_id' ) );
		$this->assertNull( FormsCRM_GravityForms_Entry_Columns::parse_column_key( 'date_created' ) );
		$this->assertNull( FormsCRM_GravityForms_Entry_Columns::parse_column_key( 'formscrm_clientify_other' ) );
	}

	/**
	 * Cells are escaped and handle empty rows.
	 */
	public function test_render_cells() {
		$this->assertStringContainsString( '&mdash;', FormsCRM_GravityForms_Entry_Columns::render_status( null ) );
		$this->assertStringContainsString( '&mdash;', FormsCRM_GravityForms_Entry_Columns::render_contact_id( null ) );

		$row    = (object) array(
			'status'     => 'error',
			'message'    => '<script>alert(1)</script>',
			'contact_id' => '<b>9</b>',
			'crm_name'   => 'Clientify',
			'updated_at' => '2026-01-01 10:00:00',
		);
		$status = FormsCRM_GravityForms_Entry_Columns::render_status( $row );
		$this->assertStringNotContainsString( '<script>', $status );
		$this->assertStringContainsString( 'Error', $status );
		$this->assertStringNotContainsString( '<b>', FormsCRM_GravityForms_Entry_Columns::render_contact_id( $row ) );

		$row->status = 'success';
		$this->assertStringContainsString( 'Sent', FormsCRM_GravityForms_Entry_Columns::render_status( $row ) );
	}

	/**
	 * Columns are registered as entry meta (native column selector), one pair per CRM.
	 */
	public function test_register_entry_meta() {
		formscrm_record_sync_status(
			'gravityforms',
			9,
			200,
			'clientify',
			array(
				'status'     => 'success',
				'contact_id' => 1,
			)
		);

		$columns = new FormsCRM_GravityForms_Entry_Columns();

		$out = $columns->register_entry_meta( array( 'existing' => array( 'label' => 'Existing' ) ), 9 );
		$this->assertArrayHasKey( 'existing', $out );
		$this->assertArrayHasKey( 'formscrm_clientify_status', $out );
		$this->assertArrayHasKey( 'formscrm_clientify_id', $out );
		$this->assertFalse( $out['formscrm_clientify_status']['is_default_column'] );
		$this->assertStringContainsString( 'Clientify', $out['formscrm_clientify_id']['label'] );

		// Form without results: untouched.
		$base = array( 'existing' => array( 'label' => 'Existing' ) );
		$this->assertSame( $base, $columns->register_entry_meta( $base, 99 ) );
	}

	/**
	 * Cell values only change for our columns.
	 */
	public function test_column_value() {
		formscrm_record_sync_status(
			'gravityforms',
			9,
			201,
			'clientify',
			array(
				'status'     => 'success',
				'contact_id' => 4242,
			)
		);
		$columns = new FormsCRM_GravityForms_Entry_Columns();

		$this->assertSame( 'untouched', $columns->column_value( 'untouched', 9, 'date_created', array( 'id' => 201 ) ) );
		$this->assertStringContainsString( '4242', $columns->column_value( '', 9, 'formscrm_clientify_id', array( 'id' => 201 ) ) );
		$this->assertStringContainsString( 'Sent', $columns->column_value( '', 9, 'formscrm_clientify_status', array( 'id' => 201 ) ) );
	}

	/**
	 * Offered by default; the filter can turn it off.
	 */
	public function test_is_enabled() {
		$this->assertTrue( FormsCRM_GravityForms_Entry_Columns::is_enabled() );

		add_filter( 'formscrm_entry_list_columns_enabled', '__return_false' );
		$this->assertFalse( FormsCRM_GravityForms_Entry_Columns::is_enabled() );
		remove_filter( 'formscrm_entry_list_columns_enabled', '__return_false' );
	}
}
