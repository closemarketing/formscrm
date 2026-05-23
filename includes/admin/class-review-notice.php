<?php
/**
 * Admin Review Notice
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2024 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FORMSCRM_Review_Notice' ) ) {
	/**
	 * Class FORMSCRM_Review_Notice
	 *
	 * Shows a dismissible admin notice asking users to review the plugin.
	 */
	class FORMSCRM_Review_Notice {
		/**
		 * Days to wait after activation before showing the notice.
		 *
		 * @var int
		 */
		const DAYS_UNTIL_NOTICE = 14;

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'activated_plugin', array( $this, 'set_activation_date' ) );
			add_action( 'admin_notices', array( $this, 'review_notice' ) );
			add_action( 'wp_ajax_formscrm_dismiss_review_notice', array( $this, 'dismiss_review_notice' ) );
		}

		/**
		 * Store plugin activation date on first activation.
		 *
		 * @param string $plugin Plugin basename being activated.
		 * @return void
		 */
		public function set_activation_date( $plugin ) {
			if ( plugin_basename( FORMSCRM_PLUGIN ) !== $plugin ) {
				return;
			}

			if ( ! get_option( 'formscrm_activation_date' ) ) {
				update_option( 'formscrm_activation_date', time(), false );
			}
		}

		/**
		 * Display admin notice to remind users to leave a review on WordPress.org.
		 *
		 * @return void
		 */
		public function review_notice() {
			$debug = defined( 'FORMSCRM_DEBUG_NOTICES' ) && FORMSCRM_DEBUG_NOTICES;

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( ! $screen ) {
				return;
			}

			if ( ! $debug ) {
				$allowed_screens = array( 'settings_page_formscrm', 'dashboard' );
				if ( ! in_array( $screen->id, $allowed_screens, true ) && false === strpos( $screen->id, 'formscrm' ) ) {
					return;
				}
			}

			$dismissed = get_user_meta( get_current_user_id(), 'formscrm_review_notice_dismissed', true );
			if ( ! $debug && $dismissed ) {
				return;
			}

			$activation_date = get_option( 'formscrm_activation_date' );
			if ( ! $activation_date ) {
				update_option( 'formscrm_activation_date', time(), false );
				if ( ! $debug ) {
					return;
				}
			}

			if ( ! $debug ) {
				$days_active = ( time() - (int) $activation_date ) / DAY_IN_SECONDS;
				if ( $days_active < self::DAYS_UNTIL_NOTICE ) {
					return;
				}
			}

			$review_url = 'https://wordpress.org/support/plugin/formscrm/reviews/#new-post';

			$notice_title = esc_html__( 'Enjoying FormsCRM?', 'formscrm' );

			$notice_message = sprintf(
				/* translators: %s is the review URL */
				__( 'Thank you for using FormsCRM! If you find it helpful, please take a moment to <a href="%s" target="_blank" rel="noopener noreferrer">leave a review on WordPress.org</a>. It really helps the plugin grow!', 'formscrm' ),
				esc_url( $review_url )
			);

			echo '<div id="formscrm-review-notice" class="notice notice-info is-dismissible">';
			echo '<p><strong>' . esc_html( $notice_title ) . '</strong></p>';
			$allowed_tags = array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
					'rel'    => array(),
				),
			);
			echo '<p>' . wp_kses( $notice_message, $allowed_tags ) . '</p>';
			echo '<p>';
			echo '<a href="' . esc_url( $review_url ) . '" target="_blank" rel="noopener noreferrer" class="button button-primary">' . esc_html__( 'Leave a Review', 'formscrm' ) . '</a>';
			echo '&nbsp;&nbsp;';
			echo '<a href="#" id="formscrm-dismiss-review" class="button button-secondary">' . esc_html__( 'No thanks', 'formscrm' ) . '</a>';
			echo '</p>';
			echo '</div>';

			$this->enqueue_script();
		}

		/**
		 * Enqueue the JS needed to handle dismissal.
		 *
		 * @return void
		 */
		private function enqueue_script() {
			wp_enqueue_script(
				'formscrm-review-notice',
				FORMSCRM_PLUGIN_URL . 'includes/admin/js/review-notice.js',
				array(),
				FORMSCRM_VERSION,
				true
			);

			wp_localize_script(
				'formscrm-review-notice',
				'formscrmReviewNotice',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'formscrm_dismiss_review' ),
				)
			);
		}

		/**
		 * AJAX handler to persist the notice dismissal for the current user.
		 *
		 * @return void
		 */
		public function dismiss_review_notice() {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'formscrm_dismiss_review' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'formscrm' ) );
			}

			update_user_meta( get_current_user_id(), 'formscrm_review_notice_dismissed', true );
			wp_die();
		}
	}
}
