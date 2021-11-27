<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Whatsapp_Action_After_Submit
 * Custom elementor form action after submit to redirect to whatsapp
 * Whatsapp_Action_After_Submit
 */

class Whatsapp_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {
	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */

	public function get_name() {
		return 'whatsapp';
	}

	/**
	 * Get Label
	 *
	 * Returns the action label
	 *
	 * @access public
	 * @return string
	 */

	public function get_label() {
		return __( 'WhatsApp', 'whatsapp-redirect' );
	}

	/**
	 * Register Settings Section
	 *
	 * Registers the Action controls
	 *
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */

	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_whatsapp-redirect',
			[
				'label' => __( 'WhatsApp Redirect', 'whatsapp-redirect' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'whatsapp_to',
			[
				'label' => __( 'WhatsApp Phone', 'whatsapp-redirect' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( '13459999999', 'whatsapp-redirect' ),
				'label_block' => true,
				'render_type' => 'none',
				'classes' => 'elementor-control-whats-phone-direction-ltr',
				'description' => __( 'Phone with country code, like: 5551999999999', 'whatsapp-redirect' ),
			]
		);

		$widget->add_control(
			'whatsapp_message',
			[
				'label' => __( 'WhatsApp Message', 'whatsapp-redirect' ),
				'type' => \Elementor\Controls_Manager::TEXTAREA,
				'placeholder' => __( 'Write yout text or use fields shortcode', 'whatsapp-redirect' ),
				'label_block' => true,
				'render_type' => 'none',
				'classes' => 'elementor-control-whats-direction-ltr',
				'description' => __( 'Use fields shortcodes for send form data os write your custom text.<br>=> For add break line use: %break%', 'whatsapp-redirect' ),
			]
		);

		$widget->end_controls_section();

	}


	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @access Public
	 * @param array $element
	 */

	public function on_export( $element ) {
		unset(
			$element['settings']['whatsapp_to'],
			$element['settings']['whatsapp_message']
		);

		return $element;
	}


	/**
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */

	public function run( $record, $ajax_handler ) {
		$whatsapp_to = $record->get_form_settings( 'whatsapp_to' );
		$whatsapp_message = $record->get_form_settings( 'whatsapp_message' );

		$whatsapp_message = str_replace('%break%', '%0D%0A', $whatsapp_message);

		$whatsapp_to = 'https://wa.me/'.$whatsapp_to.'?text='.$whatsapp_message.'';
		$whatsapp_to = $record->replace_setting_shortcodes( $whatsapp_to, true );

		if ( ! empty( $whatsapp_to ) ) {
			$ajax_handler->add_response_data( 'redirect_url', $whatsapp_to );
		}

	}

}