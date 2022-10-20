<?php
/**
 * Plugin Name: SJD Subscribe
 * Version: 0.0.3
 * Author: Steve Davison
 * Description: Provide simple subscription solution to register subscribers and manage 
 * email notifications for when new content is added
 */

DEFINE( "SJD_SUBSCRIBE_VERSION", '0.0.3');

// Set up wp_mail to use our email server
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/email.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/UserSubscription.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/Subscriber.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/Notifications.php');



add_action( 'init', 'sjd_subscribe_init');

function sjd_subscribe_init(){
    wp_enqueue_style('sjd_subscribe_form', plugins_url("styles.css", __FILE__), [], SJD_SUBSCRIBE_VERSION);
    add_shortcode('sjd_subscribe_form', 'UserSubscription::shortcode');
    Subscriber::register_custom();
}

?>