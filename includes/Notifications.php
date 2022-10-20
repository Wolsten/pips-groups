<?php 

class Notifications {


    public static function send_subscribe_email($subscriber_id, $first_name, $email, $validation_key){
        $name = get_bloginfo('name');
        $domain = get_bloginfo('url');
        // Send in html format
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $subject = "Confirm your subscription to $name";
        $link = "$domain/subscribe?validate&email=$email&key=$validation_key";
        $message = array();
        $message[] = "<p>Hi $first_name,</p>";
        $message[] = "<p>Please click the link below to validate your subscription to receive updates on new posts from us here at $name:</p>";
        $message[] = "<p><a href='$link'>$link</a></p>";
        $message[] = "<p>Best wishes from the team at " . get_bloginfo('name');
        $message[] = "<br/><br/><p>To unsubscribe and stop receiving emails from use please click <a href='$domain/subscribe?unsubscribe&id=$subscriber_id&email=$email'>here</a>.</p>";
        $message = implode( PHP_EOL.PHP_EOL, $message);
        echo "<div>$message</div>";
        return wp_mail( $email, $subject, $message, $headers);
    }
}