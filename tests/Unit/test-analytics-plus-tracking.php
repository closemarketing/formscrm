<?php
/**
 * Class AnalyticsPlusTrackingTest
 *
 * Command: composer test-debug --filter AnalyticsPlusTrackingTest
 *
 * @package Formscrm
 */

/**
 * Tests for the Analytics PLUS (visitor_key2) client-side capture wiring.
 *
 * @see formscrm_enqueue_analytics_plus_tracking()
 */
class AnalyticsPlusTrackingTest extends WP_UnitTestCase {

	/**
	 * Set up: register the script, same as formscrm_register_analytics_plus_tracking()
	 * does on wp_enqueue_scripts (not fired in isolation here).
	 */
	public function setUp(): void {
		parent::setUp();
		formscrm_register_analytics_plus_tracking();
	}

	/**
	 * Tear down: reset registered selectors and dequeue/deregister the script.
	 */
	public function tearDown(): void {
		remove_all_filters( 'formscrm_analytics_plus_selectors' );
		wp_dequeue_script( 'formscrm-analytics-plus-tracking' );
		wp_deregister_script( 'formscrm-analytics-plus-tracking' );
		parent::tearDown();
	}

	/**
	 * With no form on the page reporting a visitor_key2-mapped field, the
	 * tracking script must not be enqueued — nothing to fill, no reason to load it.
	 */
	public function test_script_not_enqueued_without_selectors() {
		formscrm_enqueue_analytics_plus_tracking();

		$this->assertFalse( wp_script_is( 'formscrm-analytics-plus-tracking', 'enqueued' ) );
	}

	/**
	 * Once an integration reports a mapped field via the selectors filter, the
	 * script must be enqueued and localized with that selector.
	 */
	public function test_script_enqueued_and_localized_with_reported_selector() {
		add_filter(
			'formscrm_analytics_plus_selectors',
			function ( $selectors ) {
				$selectors[] = '#input_1_5';
				return $selectors;
			}
		);

		formscrm_enqueue_analytics_plus_tracking();

		$this->assertTrue( wp_script_is( 'formscrm-analytics-plus-tracking', 'enqueued' ) );

		$data = wp_scripts()->get_data( 'formscrm-analytics-plus-tracking', 'data' );
		$this->assertStringContainsString( '#input_1_5', $data );
		$this->assertStringContainsString( 'analyticsplusdev.clientify.net', $data );
	}

	/**
	 * Duplicate selectors reported by more than one integration must be
	 * collapsed, not sent to the browser twice.
	 */
	public function test_duplicate_selectors_are_deduplicated() {
		add_filter(
			'formscrm_analytics_plus_selectors',
			function ( $selectors ) {
				$selectors[] = '#input_1_5';
				$selectors[] = '#input_1_5';
				return $selectors;
			}
		);

		formscrm_enqueue_analytics_plus_tracking();

		$data = wp_scripts()->get_data( 'formscrm-analytics-plus-tracking', 'data' );
		$this->assertSame( 1, substr_count( $data, '#input_1_5' ) );
	}
}
