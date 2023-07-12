<?php
class Omnisend_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {
    public function get_name() {
        return 'omnisend';
    }

    public function get_label() {
        return esc_html__('Omnisend', 'uprise');
    }

    public function register_settings_section($widget) {
        $widget->start_controls_section(
            'omnisend_section',
            [
                'label' => esc_html__('Omnisend Settings', 'uprise'),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $widget->add_control(
            'omnisend_api_key',
            [
                'label' => esc_html__('API Key', 'uprise'),
                'type' => \Elementor\Controls_Manager::TEXT,
            ]
        );

        $widget->end_controls_section();
    }

    public function run($record, $ajax_handler) {
        $settings = $record->get('form_settings');

        $api_key = $settings['omnisend_api_key'];
        $email = $record->get('email'); // Get the email address from the form submission

        // Prepare the data payload for Omnisend
        $payload = [
            'identifiers' => [
                [
                    'type' => 'email',
                    'channels' => [
                        'sms' => [
                            'status' => 'unsubscribed',
                        ],
                        'email' => [
                            'status' => 'subscribed',
                            'statusDate' => date('Y-m-d\TH:i:s\Z'), // Use current date and time
                        ],
                    ],
                    'id' => $email,
                    'sendWelcomeMessage' => false,
                ],
            ],
        ];

        // Send the payload to Omnisend using the API key
        $api_url = 'https://api.omnisend.com/v3/contacts';
        $headers = [
            'X-API-KEY' => $api_key,
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ];

        $response = wp_remote_post(
            $api_url,
            [
                'headers' => $headers,
                'body' => json_encode($payload),
            ]
        );

        if (is_wp_error($response)) {
            // Handle error response
            $error_message = $response->get_error_message();
            // ...
        } else {
            // Handle success response
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            // ...
        }
    }

    public function on_export($element) {}
}
