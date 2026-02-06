<?php
/**
 * Widget for Gravity Forms
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget.
 *
 * @since 3.14
 */
class FormsCRM_GravityForms_Widget {
	/**
	 * Construct of Class
	 */
	public function __construct() {
		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'widget_resend_entries' ), 10, 3 );
		add_action( 'gform_post_add_feed', array( $this, 'clear_feeds_cache' ), 10, 2 );
		add_action( 'gform_post_update_feed', array( $this, 'clear_feeds_cache' ), 10, 2 );
		add_action( 'gform_post_delete_feed', array( $this, 'clear_feeds_cache' ), 10, 2 );
	}

	/**
	 * Get feeds with caching and error handling to improve performance.
	 *
	 * @param int $form_id Form ID.
	 * @return array Array of feeds.
	 */
	private function get_feeds_cached( $form_id ) {
		$cache_key = 'formscrm_feeds_' . $form_id;
		$feeds     = get_transient( $cache_key );

		if ( false === $feeds ) {
			try {
				// Increase max execution time temporarily for this operation.
				$original_time_limit = ini_get( 'max_execution_time' );
				if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
					set_time_limit( 60 );
				}

				$feeds = GFCRM::get_instance()->get_feeds( null, $form_id, 'formscrm', true );

				// Restore original time limit.
				if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
					set_time_limit( (int) $original_time_limit );
				}

				// Only cache if we got valid data.
				if ( is_array( $feeds ) ) {
					set_transient( $cache_key, $feeds, 5 * MINUTE_IN_SECONDS );
				} else {
					$feeds = array();
				}
			} catch ( Exception $e ) {
				$feeds = array();
			}
		}

		return is_array( $feeds ) ? $feeds : array();
	}

	/**
	 * Clear feeds cache for a form.
	 *
	 * @param int   $feed_id Feed ID.
	 * @param array $form_id Form ID.
	 * @return void
	 */
	public function clear_feeds_cache( $feed_id, $form_id ) {
		if ( ! empty( $form_id ) ) {
			$cache_key = 'formscrm_feeds_' . $form_id;
			delete_transient( $cache_key );
		}
	}

	/**
	 * Adds a meta box to the entry detail page to resend entries to CRM.
	 *
	 * @param array $meta_boxes The meta boxes currently displayed.
	 * @param array $entry      The entry being displayed.
	 * @param array $form       The form object.
	 * @return array Updated meta boxes array.
	 */
	public function widget_resend_entries( $meta_boxes, $entry, $form ) {
		$meta_boxes['formscrm'] = array(
			'title'         => esc_html__( 'FormsCRM: Resend', 'formscrm' ),
			'callback'      => array( $this, 'resend_metabox' ),
			'context'       => 'side',
			'callback_args' => array( $entry, $form ),
		);

		return $meta_boxes;
	}

	/**
	 * The callback used to echo the content to the meta box.
	 *
	 * @param array $args An array containing the form and entry objects.
	 */
	public function resend_metabox( $args ) {
		$html     = '';
		$action   = 'formscrm_process_feeds';
		$form     = ! empty( $args['form'] ) ? $args['form'] : array();
		$form_id  = isset( $form['id'] ) ? (int) $form['id'] : 0;
		$entry    = ! empty( $args['entry'] ) ? $args['entry'] : array();
		$entry_id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;

		// Use cached version with error handling.
		$feeds = $this->get_feeds_cached( $form_id );

		// Check if action was triggered.
		$resend_action = isset( $_POST['formscrm_action'] ) ? sanitize_text_field( wp_unslash( $_POST['formscrm_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below.

		if ( $action === $resend_action ) {
			// Verify nonce for security.
			if ( ! isset( $_POST['formscrm_resend_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['formscrm_resend_nonce'] ) ), 'formscrm_resend_entry_' . $entry_id ) ) {
				$html .= '<p style="color:red;">' . esc_html__( 'Security check failed. Please try again.', 'formscrm' ) . '</p>';
			} else {
				// Get selected feed(s).
				$selected_feeds = isset( $_POST['formscrm_selected_feeds'] ) && is_array( $_POST['formscrm_selected_feeds'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['formscrm_selected_feeds'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above.
				$process_all    = in_array( 'all', $selected_feeds, true );

				$html .= '<p><strong>' . esc_html__( 'Feeds processed:', 'formscrm' ) . '</strong></p>';
				$html .= '<ul>';

				foreach ( $feeds as $feed ) {
					if ( ! $feed['is_active'] || $form_id !== (int) $feed['form_id'] ) {
						continue;
					}

					// Process feed if "all" is selected or if this specific feed is selected.
					if ( $process_all || in_array( (string) $feed['id'], $selected_feeds, true ) ) {
						GFCRM::get_instance()->process_feed( $feed, $entry, $form );
						$html .= '<li>';
						$html .= sprintf(
							// translators: %s is the name of the feed.
							__( 'Feed: %s', 'formscrm' ),
							isset( $feed['meta']['feedName'] ) ? $feed['meta']['feedName'] : $feed['id'],
						);
						$html .= '</li>';
					}
				}
				$html .= '</ul>';
			}
		}

		// Always show the form with available feeds.
		$html .= '<p>' . esc_html__( 'This will resend the entry to the CRM.', 'formscrm' ) . '</p>';
		$html .= '<p>' . esc_html__( 'Actual feeds actived:', 'formscrm' ) . '</p>';
		$html .= '<ul>';

		$active_feeds = 0;
		foreach ( $feeds as $feed ) {
			if ( ! $feed['is_active'] || $form_id !== (int) $feed['form_id'] ) {
				continue;
			}
			++$active_feeds;
			$html .= '<li>';
			$html .= sprintf(
				// translators: %s is the name of the feed.
				__( 'Feed: %s', 'formscrm' ),
				isset( $feed['meta']['feedName'] ) ? $feed['meta']['feedName'] : $feed['id'],
			);
			$html .= '</li>';
		}
		$html .= '</ul>';

		if ( $active_feeds > 0 ) {
			$html .= '<br/>';
			$html .= '<form method="post" style="display:inline;">';
			$html .= wp_nonce_field( 'formscrm_resend_entry_' . $entry_id, 'formscrm_resend_nonce', true, false );
			$html .= '<input type="hidden" name="formscrm_action" value="' . esc_attr( $action ) . '" />';
			$html .= '<label for="formscrm_feed_select">' . esc_html__( 'Select Feeds to Resend', 'formscrm' ) . ':</label> ';
			$html .= '<select id="formscrm_feed_select" name="formscrm_selected_feeds[]" style="min-width:200px;">';
			$html .= '<option value="all">' . esc_html__( 'All feeds', 'formscrm' ) . '</option>';
			foreach ( $feeds as $feed ) {
				if ( ! $feed['is_active'] || $form_id !== (int) $feed['form_id'] ) {
					continue;
				}
				$feed_name = isset( $feed['meta']['feedName'] ) ? $feed['meta']['feedName'] : $feed['id'];
				$html     .= sprintf(
					'<option value="%s">%s</option>',
					esc_attr( $feed['id'] ),
					esc_html( $feed_name )
				);
			}
			$html .= '</select><br/><br/>';
			$html .= sprintf(
				'<input type="submit" value="%s" class="button button-primary" />',
				esc_attr__( 'Resend Entry', 'formscrm' )
			);
			$html .= '</form>';
		} else {
			$html .= '<p><em>' . esc_html__( 'No active feeds found for this form.', 'formscrm' ) . '</em></p>';
		}

		echo wp_kses(
			$html,
			array(
				'p'      => array( 'style' => array() ),
				'strong' => array(),
				'ul'     => array(),
				'li'     => array(),
				'br'     => array(),
				'form'   => array(
					'method' => array(),
					'style'  => array(),
				),
				'input'  => array(
					'type'  => array(),
					'name'  => array(),
					'value' => array(),
					'class' => array(),
					'id'    => array(),
				),
				'select' => array(
					'name'     => array(),
					'id'       => array(),
					'multiple' => array(),
					'style'    => array(),
				),
				'option' => array(
					'value' => array(),
				),
				'label'  => array(
					'for' => array(),
				),
				'em'     => array(),
			)
		);
	}
}
new FormsCRM_GravityForms_Widget();
