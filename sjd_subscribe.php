<?php
/**
 * Plugin Name: SJD Subscribe
 * Version: 0.0.3
 * Author: Steve Davison
 * Description: Provide simple subscription solution to register subscribers and manage 
 * email notifications for when new content is added
 */

DEFINE( "SJD_SUBSCRIBE_VERSION", '0.0.3');
DEFINE( "SJD_SUBSCRIBE_IMAGE", plugins_url('sjd_subscribe_plugin/images/email.jpg'));

REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/email.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/ShortCode.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/Subscriber.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/Notifications.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/Settings.php');

add_action( 'init', 'sjd_subscribe_init');

function sjd_subscribe_init(){
    wp_enqueue_style('sjd_subscribe_form', plugins_url("styles.css", __FILE__), [], SJD_SUBSCRIBE_VERSION);
    add_shortcode('sjd_subscribe_form', 'ShortCode::init');
    register_setting('options', 'subscriber_url', array( 'default' => '','show_in_rest' => false));
    Subscriber::init();
    Notifications::init();
    Settings::init();
}

?>
