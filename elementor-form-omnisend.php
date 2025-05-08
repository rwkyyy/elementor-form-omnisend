<?php
/*
Plugin Name:  Omnisend <> Elementor - Forms Integration
Plugin URI:   https://www.uprise.ro
Description:  Basic integration for Elementor Forms to Omnisend
Version:      1.0
Author:       Uprise Team
Author URI:   https://www.uprise.ro
License:      GPL3
Text Domain:  uprise
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'elementor_pro/forms/actions/register', function ( $form_actions_registrar ) {
	$form_actions_registrar->register( new Omnisend_Action() );
} );

class Omnisend_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {

	public function get_name() {
		return 'omnisend';
	}

	public function get_label() {
		return esc_html__( 'Omnisend', 'textdomain' );
	}

	/**
	 * Register the settings section for the Omnisend action in the form widget
	 *
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'omnisend_section',
			[
				'label'     => esc_html__( 'Omnisend Settings', 'textdomain' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'omnisend_api_key',
			[
				'label' => esc_html__( 'API Key', 'textdomain' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			]
		);

		$widget->end_controls_section();
	}

	/**
	 * Runs the Omnisend action when the form is submitted
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		if ( empty( $settings['omnisend_api_key'] ) ) {
			return; // API key is required
		}

		$api_key = sanitize_text_field( $settings['omnisend_api_key'] );

		// Attempt to get the email from the form submission
		$raw_fields = $record->get( 'fields' );
		$email      = '';
		foreach ( $raw_fields as $field ) {
			if ( ! empty( $field['type'] ) && $field['type'] === 'email' && ! empty( $field['value'] ) ) {
				$email = sanitize_email( $field['value'] );
				break;
			}
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			return; // Valid email is required to proceed
		}

		// Prepare the data payload for Omnisend
		$payload = [
			'identifiers' => [
				[
					'type'               => 'email',
					'id'                 => $email,
					'channels'           => [
						'email' => [
							'status'     => 'subscribed',
							'statusDate' => gmdate( 'Y-m-d\TH:i:s\Z' ), // UTC time in ISO 8601 format
						],
					],
					'sendWelcomeMessage' => false,
				],
			],
		];

		// Send the payload to Omnisend using the API key
		$api_url = 'https://api.omnisend.com/v3/contacts';
		$headers = [
			'X-API-KEY'    => $api_key,
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		];

		$response = wp_remote_post(
			$api_url,
			[
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			// Log or handle the error as needed
			error_log( 'Omnisend API request error: ' . $response->get_error_message() );
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );

			// Optional: handle non-success response codes or log response
			if ( $response_code < 200 || $response_code >= 300 ) {
				error_log( "Omnisend API returned HTTP code $response_code: $response_body" );
			}
		}
	}

	// No export functionality needed currently
	public function on_export( $element ) {
	}
}
