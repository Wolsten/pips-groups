<?php
/**
 * Plugin Name: SJD Subscribe
 * Version: 0.0.4
 * Author: Steve Davison
 * Description: Provide simple subscription solution to register subscribers and manage 
 * email notifications for when new content is added
 */

DEFINE( "SJD_SUBSCRIBE_VERSION", '0.0.4');
DEFINE( "SJD_SUBSCRIBE_IMAGE", plugins_url('sjd_subscribe_plugin/images/email.jpg'));

REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/SJD_email.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/SJD_ShortCode.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/SJD_Subscriber.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/SJD_Notifications.php');
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/SJD_Settings.php');



add_action( 'init', 'sjd_subscribe_init');
function sjd_subscribe_init(){
    wp_enqueue_style('sjd_subscribe_form', plugins_url("styles.css", __FILE__), [], SJD_SUBSCRIBE_VERSION);
    add_shortcode('sjd_subscribe_form', 'SJD_ShortCode::init');
    SJD_Subscriber::init();
    SJD_Settings::init();
}



// Add send notifications button to post edit pages
add_action('post_submitbox_start','sjd_post_notify_button');
function sjd_post_notify_button(){
    // Check for subscriber url
    $subscriber_url = get_option('subscriber_url');
    if ( $subscriber_url == '' ){ ?>
        <div style="margin-bottom:0.5rem;padding:0.5rem;background-color:red;color:white;">
            <p>You must add the sjd_subscribe_form shortcode to a post or page and view that page before you can begin sending notifications.</p>
        </div>
        <?php return;
    }
    // Add notification functionality to the meta publish box
    global $post;
    if ( $post->post_status !== 'publish' ) return;
    if ( $post->post_type === SJD_Subscriber::POST_TYPE ) return; ?>
    <div style="margin-bottom:0.5rem;padding:0.5rem;background-color:#689f68;">
        <label for="sjd-notify-subscribers" style="color:white;display:inline-block;padding-bottom:0.3rem;">Notify subscribers on save?</label>
        <select name="sjd-notify-subscribers" id="sjd-notify-subscribers">
            <option value="false">Do not notify</option>
            <option value="LINK">Send links</option>
            <option value="PAGE">Send full page</option>
        </select>
    </div>
<?php }


// On post save check for sending notifications
add_action('save_post', 'sjd_post_notify_subscribers');
function sjd_post_notify_subscribers(){
    global $post;
    if ( $post->post_status !== 'publish' ) return;
    if ( $post->post_type === SJD_Subscriber::POST_TYPE ) return;
    if ( isset($_POST['sjd-notify-subscribers']) == false ) return;
    $what = $_POST['sjd-notify-subscribers'];
    if ( $what == 'LINK' || $what == 'PAGE' ){
        SJD_Notifications::send($post->ID, $what);
        die();
    }
}



?>
