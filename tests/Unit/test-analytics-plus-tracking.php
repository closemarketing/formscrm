<?php
/**
 * Class AnalyticsPlusTrackingTest
 *
 * Command: composer test-debug --filter AnalyticsPlusTrackingTest
 *
 * @package Formscrm
 */

/**
 * Tests for the Clientify visitor tracking identifier (Analytics PLUS
 * visitor_uuid, falling back to the legacy vk cookie — both sent as
 * visitor_key) client-side capture wiring.
 *
 * @see formscrm_enqueue_analytics_plus_tracking()
 * @see formscrm_get_analytics_plus_merge_vars()
 */
class AnalyticsPlusTrackingTest extends WP_UnitTestCase {

	/**
	 * Tear down: reset the shared filter and dequeue/deregister the script.
	 */
	public function tearDown(): void {
		remove_all_filters( 'formscrm_needs_analytics_plus_tracking' );
		wp_dequeue_script( 'formscrm-analytics-plus-tracking' );
		wp_deregister_script( 'formscrm-analytics-plus-tracking' );
		parent::tearDown();
	}

	/**
	 * With no form on the page reporting a Clientify feed, the tracking
	 * script must not be enqueued.
	 */
	public function test_script_not_enqueued_when_not_needed() {
		formscrm_enqueue_analytics_plus_tracking();

		$this->assertFalse( wp_script_is( 'formscrm-analytics-plus-tracking', 'enqueued' ) );
	}

	/**
	 * Once an integration reports needing it, the script must be enqueued.
	 */
	public function test_script_enqueued_when_reported_needed() {
		add_filter( 'formscrm_needs_analytics_plus_tracking', '__return_true' );

		formscrm_enqueue_analytics_plus_tracking();

		$this->assertTrue( wp_script_is( 'formscrm-analytics-plus-tracking', 'enqueued' ) );
	}

	/**
	 * The tracking identifier, read from FormsCRM's fixed-name hidden field,
	 * must be forwarded as a visitor_key merge var.
	 */
	public function test_get_analytics_plus_merge_vars_forwards_identifier() {
		$merge_vars = formscrm_get_analytics_plus_merge_vars(
			'clientify',
			array( 'formscrm_vk' => 'tracking-value' )
		);

		$this->assertSame(
			array( array( 'name' => 'visitor_key', 'value' => 'tracking-value' ) ),
			$merge_vars
		);
	}

	/**
	 * A missing/empty identifier must simply be omitted, not sent as blank.
	 */
	public function test_get_analytics_plus_merge_vars_omits_missing_identifier() {
		$merge_vars = formscrm_get_analytics_plus_merge_vars( 'clientify', array() );

		$this->assertSame( array(), $merge_vars );
	}

	/**
	 * Non-Clientify CRMs must never get this merge var, even if the
	 * fixed-name field happens to be present.
	 */
	public function test_get_analytics_plus_merge_vars_ignores_other_crms() {
		$merge_vars = formscrm_get_analytics_plus_merge_vars(
			'holded',
			array( 'formscrm_vk' => 'tracking-value' )
		);

		$this->assertSame( array(), $merge_vars );
	}
}
