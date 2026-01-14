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
			'title'         => esc_html__( 'Resend Entry to CRM', 'formscrm' ),
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
		$html    = '';
		$action  = 'formscrm_process_feeds';
		$form    = ! empty( $args['form'] ) ? $args['form'] : array();
		$form_id = isset( $form['id'] ) ? (int) $form['id'] : 0;
		$entry   = ! empty( $args['entry'] ) ? $args['entry'] : array();
		$entry_id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;

		$feeds = GFCRM::get_instance()->get_feeds( null, $form_id, 'formscrm', true );

		// Check if action was triggered.
		$resend_action = isset( $_POST['formscrm_action'] ) ? sanitize_text_field( wp_unslash( $_POST['formscrm_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below.

		if ( $action === $resend_action ) {
			// Verify nonce for security.
			if ( ! isset( $_POST['formscrm_resend_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['formscrm_resend_nonce'] ) ), 'formscrm_resend_entry_' . $entry_id ) ) {
				$html .= '<p style="color:red;">' . esc_html__( 'Security check failed. Please try again.', 'formscrm' ) . '</p>';
			} else {
				$html .= '<p><strong>' . esc_html__( 'Feeds processed:', 'formscrm' ) . '</strong></p>';
				$html .= '<ul>';

				foreach ( $feeds as $feed ) {
					if ( ! $feed['is_active'] || $form_id !== (int) $feed['form_id'] ) {
						continue;
					}
					GFCRM::get_instance()->process_feed( $feed, $entry, $form );
					$html .= '<li>';
					$html .= sprintf(
						// translators: %s is the name of the feed.
						__( 'Feed: %s', 'formscrm' ),
						isset( $feed['meta']['feedName'] ) ? $feed['meta']['feedName'] : $feed['id'],
					);
					$html .= '</li>';
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
				'em'     => array(),
			)
		);
	}
}
new FormsCRM_GravityForms_Widget();
