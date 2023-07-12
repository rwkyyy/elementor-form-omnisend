<?php
/*
Plugin Name:  Omnisend <> Elementor - Forms Integration
Plugin URI:   https://www.uprise.ro
Description:  Basic integration for Elementor Forms to Omnisend
Version:      0.1
Author:       Uprise Team
Author URI:   https://www.uprise.ro
License:      GPL3
Text Domain:  uprise
*/

function register_omnisend_action($form_actions_registrar) {
    require_once( __DIR__ . '/omnisend-action.php' );
    $form_actions_registrar->register( new \Omnisend_Action() );
}
add_action('elementor_pro/forms/actions/register', 'register_omnisend_action');
