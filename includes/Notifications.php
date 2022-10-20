<?php 

class Notifications {

    public static function init(){
        add_action( 'post_submitbox_start', 'Notifications::add_update_button');
    }

    public static function add_update_button(){
        global $post;
        $domain = get_bloginfo('url');
        echo "<style>";
        echo ".subscriber-button {display:block;margin:1rem;padding:0.5rem;text-decoration:none;background-color:green;color:white;}";
        echo ".subscriber-button:hover {background-color:darkgreen;color:white;}";
        echo "</style>";
        echo "<a class='subscriber-button' href='$domain/subscribe?notification=$post->ID'>Email subscribers about this post.</a>";
        
    }

    public static function request_confirmation($post_id){
        $post = get_post($post_id);
        $domain = get_bloginfo('url');
        echo "<p>You are about to send notifications about this post:</p>";
        echo "<p style='font-weight:bold;padding-left:1rem;'>$post->post_title</p>";
        echo "<p>to all subscribers. Click the button below to confirm.</p>";
        echo "<button><a href='$domain/subscribe?notification=$post_id&confirm=true'>Confirm</a></button>";
        echo "<p>There will be a delay whilst the emails are sent - do not move away from this page until the list of subscribers emailed have been returned.</p>";
    }

    public static function send($post_id){
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
        echo "<OL>";
        foreach( $subscribers as $subscriber ){
            $first_name = get_post_meta( $subscriber->ID, Subscriber::POST_NAME.'_first_name', $single=true);
            $last_name = get_post_meta( $subscriber->ID, Subscriber::POST_NAME.'_last_name', $single=true);
            $email=$subscriber->post_title;
            if ( self::send_new_content_email($subscriber->ID, $first_name, $email, $post) ){
                echo "<li>$first_name $last_name ($email)</li>";
                if ( $i % $emails == 0 ){
                    sleep($delay);
                }
                $i++;
            }
        }
        echo "</OL>";
    }

    public static function send_subscribe_email($subscriber_id, $first_name, $email, $validation_key){
        $name = get_bloginfo('name');
        $domain = get_bloginfo('url');
        // Send in html format
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $subject = "Confirm your subscription to $name";
        $link = "$domain/subscribe?validate&email=$email&key=$validation_key";
        $message = array();
        $message[] = self::header($name);
        $message[] = "<p>Hi $first_name,</p>";
        $message[] = "<p>Please <a href='$link'>click here</a> to validate your subscription to receive updates from <strong>$name</strong>:</p>";
        $message[] = "<p>Best wishes from the team</p>";
        $message[] = self::footer($subscriber_id,$email);
        $message = Notifications::subscribe_page(implode($message));
        echo "<div>$message</div>";
        return wp_mail( $email, $subject, $message, $headers);
        // return true;
    }

    public static function send_new_content_email($subscriber_id, $first_name, $email, $post){
        // print_r($post);
        $name = get_bloginfo('name');
        $domain = get_bloginfo('url');
        // Send in html format
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $subject = "New content added to $name";
        $link = "$domain/$post->post_name";
        $message = array();
        $message[] = self::header($name);
        $message[] = "<p>Hi $first_name,</p>";
        $message[] = "<p>Just to let you know that we have added new content to our site:</p>";
        $message[] = "<p><a href='$link'>$post->post_title</a></p>";
        $message[] = "<p>Best wishes from the team at $name</p>";
        $message[] = self::footer($subscriber_id,$email);
        $message = implode( PHP_EOL.PHP_EOL, $message);
        // echo "<div>$message</div>";
        // return wp_mail( $email, $subject, $message, $headers);
        return true;
    }

    private static function header($name){
        $src = SJD_SUBSCRIBE_IMAGE;
        return "<header><img src='$src'/><h1>$name</h1></header>";
    }

    private static function footer($subscriber_id,$email){
        $domain = get_bloginfo('url');
        return "<footer>
            <p>To unsubscribe and stop receiving emails from us please 
                <a href='$domain/subscribe?unsubscribe&id=$subscriber_id&email=$email'>click here</a>.
            </p>
        </footer>";
    }

    private static function subscribe_page( $content ){ 
        return "
            <style>
            .notification-email {
                max-width:400px;
            }
            .notification-email header img {
                max-width:100%;
                height:auto;
            }
            .notification-email footer {
                    margin:2rem 0;
                    padding: 0.5rem 1rem;
                    border: 1px solid grey;
                }
            </style>
            <div class='notification-email'>$content</div>";

    }

}