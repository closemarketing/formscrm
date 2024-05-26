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
	function resend_metabox( $args ) {
		$html    = '';
		$action  = 'formscrm_process_feeds';
		$form    = ! empty( $args['form'] ) ? $args['form'] : array();
		$form_id = isset( $form['id'] ) ? (int) $form['id'] : 0;
		$entry   = ! empty( $args['entry'] ) ? $args['entry'] : array();

		$feeds = GFCRM::get_instance()->get_feeds( null, $form_id, 'formscrm', true );

		if ( rgpost( 'action' ) == $action ) {
			check_admin_referer( 'gforms_save_entry', 'gforms_save_entry' );
			$html .= '<p><strong>' . esc_html__( 'Feeds processed:', 'formscrm' ) . '</strong></p>';
			$html .= '<ul>';

			foreach ( $feeds as $feed ) {
				if ( ! $feed['is_active'] || $form_id !== (int) $feed['form_id'] ) {
					continue;
				}
				GFCRM::get_instance()->process_feed( $feed, $entry, $form );
				$html .= '<li>';
				$html .= sprintf(
					__( 'Feed: %s', 'formscrm' ),
					isset( $feed['meta']['feedName'] ) ? $feed['meta']['feedName'] : $feed['id'],
				);
				$html .= '</li>';
			}
			$html .= '</ul>';
		} else {
			$html .= '<p>' . esc_html__( 'This will resend the entry to the CRM.', 'formscrm' ) . '</p>';
			$html .= '<p>' . esc_html__( 'Actual feeds actived:', 'formscrm' ) . '</p>';
			$html .= '<ul>';

			foreach ( $feeds as $feed ) {
				if ( ! $feed['is_active'] || $form_id !== (int) $feed['form_id'] ) {
					continue;
				}
				$html .= '<li>';
				$html .= sprintf(
					__( 'Feed: %s', 'formscrm' ),
					isset( $feed['meta']['feedName'] ) ? $feed['meta']['feedName'] : $feed['id'],
				);
				$html .= '</li>';
			}
			$html .= '</ul>';
			$html .= '</br>';
			// Add the 'Process Feeds' button.
			$html .= sprintf(
				'<input type="submit" value="%s" class="button" onclick="jQuery(\'#action\').val(\'%s\');" />',
				__( 'Resend Entry', 'formscrm' ),
				$action
			);
		}
		echo $html;
	}
}
new FormsCRM_GravityForms_Widget();