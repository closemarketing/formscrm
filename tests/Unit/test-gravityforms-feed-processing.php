<?php
/**
 * Class GravityFormsFeedProcessingTest
 *
 * Tests GFCRM feed processing safeguards: per entry/feed lock against duplicate
 * sends, background queue lock time and payment-feed sync filter scoping.
 *
 * Command: composer test --filter GravityFormsFeedProcessingTest
 *
 * @package Formscrm
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

if ( ! class_exists( 'CRMLIB_Fcrmlocktest' ) ) {
	/**
	 * Fake CRM that counts create_entry() calls and can run a callback mid-request.
	 */
	class CRMLIB_Fcrmlocktest {
		/**
		 * Number of create_entry() calls.
		 *
		 * @var int
		 */
		public static $calls = 0;

		/**
		 * Callback run inside create_entry() to simulate a concurrent runner.
		 *
		 * @var callable|null
		 */
		public static $during_send = null;

		/**
		 * Whether create_entry() should throw.
		 *
		 * @var bool
		 */
		public static $throw = false;

		/**
		 * Sends the entry.
		 *
		 * @param array $settings   Settings.
		 * @param array $merge_vars Merge vars.
		 * @return array
		 * @throws Exception When $throw is set.
		 */
		public function create_entry( $settings, $merge_vars ) {
			++self::$calls;
			if ( self::$during_send ) {
				call_user_func( self::$during_send );
			}
			if ( self::$throw ) {
				throw new Exception( 'CRM exploded' );
			}
			return array(
				'status' => 'ok',
				'id'     => 'contact-1',
			);
		}
	}
}

if ( ! class_exists( 'CRMLIB_Redsys' ) ) {
	/**
	 * Fake Redsys connector so maybe_sync_payment_feed() sees it as loaded.
	 */
	class CRMLIB_Redsys {}
}

/**
 * Test case for GFCRM feed processing.
 */
class GravityFormsFeedProcessingTest extends WP_UnitTestCase {

	/**
	 * GFCRM instance.
	 *
	 * @var GFCRM
	 */
	private $gf;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->gf = GFCRM::get_instance();

		CRMLIB_Fcrmlocktest::$calls       = 0;
		CRMLIB_Fcrmlocktest::$during_send = null;
		CRMLIB_Fcrmlocktest::$throw       = false;

		delete_option( $this->lock_key() );
	}

	/**
	 * Tear down test environment.
	 */
	public function tearDown(): void {
		delete_option( $this->lock_key() );
		if ( property_exists( $this->gf, 'test_plugin_settings' ) ) {
			$this->gf->test_plugin_settings = array();
		}
		parent::tearDown();
	}

	/**
	 * Lock option name for the fixtures below.
	 *
	 * @return string
	 */
	private function lock_key() {
		return 'formscrm_feed_lock_101_7';
	}

	/**
	 * Feed using the fake CRM.
	 *
	 * @param string $addon_slug Add-on slug.
	 * @return array
	 */
	private function feed( $addon_slug = 'formscrm' ) {
		return array(
			'id'         => 7,
			'addon_slug' => $addon_slug,
			'meta'       => array( 'fc_crm_custom_type' => 'fcrmlocktest' ),
		);
	}

	/**
	 * Entry fixture.
	 *
	 * @return array
	 */
	private function entry() {
		return array(
			'id'      => 101,
			'form_id' => 3,
		);
	}

	/**
	 * Form fixture.
	 *
	 * @return array
	 */
	private function form() {
		return array(
			'id'     => 3,
			'title'  => 'Test form',
			'fields' => array(),
		);
	}

	// -------------------------------------------------------------------------
	// Per entry/feed lock
	// -------------------------------------------------------------------------

	/**
	 * A normal run sends once and releases the lock.
	 */
	public function test_process_feed_sends_once_and_releases_lock() {
		$this->gf->process_feed( $this->feed(), $this->entry(), $this->form() );

		$this->assertSame( 1, CRMLIB_Fcrmlocktest::$calls );
		$this->assertFalse( get_option( $this->lock_key() ), 'Lock must be released after sending.' );
	}

	/**
	 * A second runner arriving while the first is still sending is skipped.
	 */
	public function test_concurrent_run_for_same_entry_is_skipped() {
		$gf    = $this->gf;
		$feed  = $this->feed();
		$entry = $this->entry();
		$form  = $this->form();

		// Simulate the background queue handing the same task to another runner mid-request.
		CRMLIB_Fcrmlocktest::$during_send = function () use ( $gf, $feed, $entry, $form ) {
			CRMLIB_Fcrmlocktest::$during_send = null;
			$gf->process_feed( $feed, $entry, $form );
		};

		$this->gf->process_feed( $feed, $entry, $form );

		$this->assertSame( 1, CRMLIB_Fcrmlocktest::$calls, 'The entry must reach the CRM only once.' );
	}

	/**
	 * A fresh lock held by another runner prevents sending and is left untouched.
	 */
	public function test_fresh_lock_skips_processing() {
		$locked_at = time() - 30;
		add_option( $this->lock_key(), $locked_at, '', false );

		$this->gf->process_feed( $this->feed(), $this->entry(), $this->form() );

		$this->assertSame( 0, CRMLIB_Fcrmlocktest::$calls );
		$this->assertSame( $locked_at, (int) get_option( $this->lock_key() ), 'Another runner owns the lock.' );
	}

	/**
	 * A stale lock left by a crashed run is taken over.
	 */
	public function test_stale_lock_is_taken_over() {
		add_option( $this->lock_key(), time() - HOUR_IN_SECONDS, '', false );

		$this->gf->process_feed( $this->feed(), $this->entry(), $this->form() );

		$this->assertSame( 1, CRMLIB_Fcrmlocktest::$calls );
		$this->assertFalse( get_option( $this->lock_key() ) );
	}

	/**
	 * The lock is released even when the CRM call throws.
	 */
	public function test_lock_released_when_crm_throws() {
		CRMLIB_Fcrmlocktest::$throw = true;

		try {
			$this->gf->process_feed( $this->feed(), $this->entry(), $this->form() );
			$this->fail( 'Expected exception was not thrown.' );
		} catch ( Exception $e ) {
			$this->assertSame( 'CRM exploded', $e->getMessage() );
		}

		$this->assertFalse( get_option( $this->lock_key() ), 'Lock must not be left behind after a failure.' );
	}

	/**
	 * Different feeds of the same entry do not block each other.
	 */
	public function test_lock_is_scoped_per_feed() {
		add_option( 'formscrm_feed_lock_101_8', time(), '', false );

		$this->gf->process_feed( $this->feed(), $this->entry(), $this->form() );

		$this->assertSame( 1, CRMLIB_Fcrmlocktest::$calls );
		delete_option( 'formscrm_feed_lock_101_8' );
	}

	// -------------------------------------------------------------------------
	// Background queue lock time
	// -------------------------------------------------------------------------

	/**
	 * The queue lock outlasts the slowest CRM request (120s timeout).
	 */
	public function test_queue_lock_time_exceeds_crm_timeout() {
		$this->gf->init();

		$lock = apply_filters( 'wp_gf_formscrm_feed_processor_queue_lock_time', 60 );

		$this->assertGreaterThanOrEqual( 5 * MINUTE_IN_SECONDS, $lock );
	}

	/**
	 * A longer lock configured elsewhere is kept.
	 */
	public function test_queue_lock_time_keeps_longer_value() {
		$this->assertSame( 900, $this->gf->feed_processor_lock_time( 900 ) );
	}

	// -------------------------------------------------------------------------
	// Payment feed sync filter
	// -------------------------------------------------------------------------

	/**
	 * Feeds from other add-ons (e.g. Stripe) are never altered.
	 */
	public function test_sync_filter_ignores_other_addon_feeds() {
		$this->gf->test_plugin_settings = array( 'fc_crm_type' => 'redsys' );

		$stripe_feed = array(
			'id'         => 44,
			'addon_slug' => 'gravityformsstripe',
			'meta'       => array(),
		);

		$this->assertTrue( $this->gf->maybe_sync_payment_feed( true, $stripe_feed, $this->entry(), $this->form() ) );
	}

	/**
	 * Own Redsys feeds are forced to run synchronously.
	 */
	public function test_sync_filter_forces_own_redsys_feed_sync() {
		$feed = array(
			'id'         => 9,
			'addon_slug' => 'formscrm',
			'meta'       => array( 'fc_crm_custom_type' => 'redsys' ),
		);

		$this->assertFalse( $this->gf->maybe_sync_payment_feed( true, $feed, $this->entry(), $this->form() ) );
	}

	/**
	 * Own non-payment feeds keep their asynchronous setting.
	 */
	public function test_sync_filter_keeps_own_crm_feed_async() {
		$this->assertTrue( $this->gf->maybe_sync_payment_feed( true, $this->feed(), $this->entry(), $this->form() ) );
	}
}
