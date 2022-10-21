<?php 

class Notifications {

    private const DEBUG_EMAIL = "stephenjohndavison@gmail.com"; // Set to empty string for normal operation

    public static function init(){
        add_action( 'post_submitbox_start', 'Notifications::add_update_button');
    }

    // Add send notifications button to post edit pages
    public static function add_update_button(){
        global $post;
        $url = get_option('subscriber_url');
        if ( $url ){ ?>
            <style>
                .subscriber-button {display:block;margin:1rem;padding:0.5rem;text-decoration:none;background-color:green;color:white;}
                .subscriber-button:hover {background-color:darkgreen;color:white;}
            </style>
            <a class="subscriber-button" href="<?=$url?>?notification&post_id=<?=$post->ID?>">Email subscribers about this post.</a>
        <?php } else { ?>
            <p style="color:red;">You have the sjd_subscriber_plugin installed but have not set a page for handling notifications. Add the shortcode [sjd_subscribe_form] as the only content to a page or post.</p>
        <?php }
    }

    // public static function request_confirmation($post_id){
    //     $post = get_post($post_id);
    //     $url = get_option('subscriber_url');
    //     echo "<p>You are about to send notifications about this post:</p>";
    //     echo "<p style='font-weight:bold;padding-left:1rem;'>$post->post_title</p>";
    //     echo "<p>to all subscribers. Click one of the button below to confirm.</p>";
    //     echo "<button><a href='$url?notification=$post_id&confirm=link'>Send link</a></button>";
    //     echo "&nbsp;&nbsp;";
    //     echo "<button><a href='$url?notification=$post_id&confirm=page'>Send full page</a></button>";
    //     echo "<p>Sending a full page is designed for newsletter style posts.</p>";
    //     echo "<p>There will be a delay whilst the emails are sent - do not move away from this page until the list of subscribers emailed have been returned.</p>";
    // }

    public static function send($post_id, $what){
        $post = get_post($post_id);
        // Get all subscribers
        $subscribers = get_posts(array(
            'numberposts' => -1,
            'post_type' => Subscriber::POST_TYPE,
            'post_status' => 'publish'
        ));
        $i = 1;
        $emails = get_option('subscriber_message_emails');
        if ( !$emails ) $emails = 1;
        $delay = get_option('subscriber_message_delay');
        if ( !$delay ) $delay = 1;
        echo "<p>Sending notifications to:</p>";
        echo "<ol>";
        foreach( $subscribers as $subscriber ){
            $first_name = get_post_meta( $subscriber->ID, Subscriber::POST_NAME.'_first_name', $single=true);
            $last_name = get_post_meta( $subscriber->ID, Subscriber::POST_NAME.'_last_name', $single=true);
            if ( self::DEBUG_EMAIL != "" ){
                $email = self::DEBUG_EMAIL;
            } else {
                $email=$subscriber->post_title;
            }
            if ( self::DEBUG_EMAIL == "" || $i==1 ){
                if ( self::send_new_content_email($subscriber->ID, $first_name, $email, $post, $what) ){
                    echo "<li>$first_name $last_name ($email)</li>";
                    if ( $i % $emails == 0 ){
                        sleep($delay);
                    }
                    $i++;
                }
            }
        }
        echo "</ol>";
    }

    public static function send_subscribe_email($subscriber_id, $first_name, $email, $validation_key){
        $name = get_bloginfo('name');
        $url = get_option('subscriber_url');
        // Send in html format
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $subject = "Confirm your subscription to $name";
        $link = "$url?validate&email=$email&key=$validation_key";
        $message = array();
        $message[] = self::header($name);
        $message[] = "<p>Hi $first_name,</p>";
        $message[] = "<p>Please <a href='$link'>click here</a> to validate your subscription to receive updates from <strong>$name</strong>:</p>";
        $message[] = "<p>Best wishes from the team</p>";
        $message[] = self::footer($subscriber_id,$email);
        $message = Notifications::subscribe_page(implode($message));
        // echo "<div>$message</div>";
        return wp_mail( $email, $subject, $message, $headers);
        return true;
    }

    public static function send_new_content_email($subscriber_id, $first_name, $email, $post, $what){
        // print_r($post);
        $name = get_bloginfo('name');
        $domain = get_bloginfo('url');
        // Send in html format
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $subject = "New content added to $name";
        $link = "$domain/$post->post_name";
        // Default image
        $img = SJD_SUBSCRIBE_IMAGE;
        $debug = "";
        // Use post thumbnail of has one
        if ( has_post_thumbnail($post) ){
            $img = get_the_post_thumbnail_url($post->ID,$size="large");
        // Fall back to image from plugin settings
        } else if ( get_option('subscriber_email_image') ) {
            $img = get_option('subscriber_email_image');
        }

        $message = array();

        // $message[] = "<h1>$debug</h1>";

        $message[] = "<p>Hi $first_name,</p>";
        $message[] = "<p>Here's an update from <strong>$name</strong>.</p>";
        
        if ( $what === 'page' ){
            $message[] = self::header($post->post_title, $img);
            $message[] = str_replace(PHP_EOL,"<br>",$post->post_content);
        } else {
            $message[] = self::header($name, $img);
            $message[] = "<h2><a href='$link'>$post->post_title</a></h2>";
            $message[] = "<p>Best wishes from the team at $name</p>";
        }
        $message[] = self::footer($subscriber_id,$email);
        $message = Notifications::subscribe_page(implode($message));
        //echo "<div>$message</div>";
        return wp_mail( $email, $subject, $message, $headers);
        return true;
    }

    private static function header($name,$img){
        return "<header><img src='$img'/><h1>$name</h1></header>";
    }

    private static function footer($subscriber_id,$email){
        $url = get_option('subscriber_url');
        return "<footer>
            <p>To unsubscribe and stop receiving emails from us please 
                <a href='$url?unsubscribe&id=$subscriber_id&email=$email'>click here</a>.
            </p>
        </footer>";
    }

    private static function subscribe_page( $content ){ 
        return "
            <style>
            .notification-email {
                max-width:600px;
                font-size: 12pt;
            }
            .notification-email header img {
                width:100%;
                height:200px;
                object-fit: cover;
            }
            .notification-email footer {
                    margin:2rem 0;
                    padding: 0rem 1rem;
                    border: 1px solid grey;
                }
            </style>
            <div class='notification-email'>$content</div>";

    }

}