<?php 

class SJD_Notifications {

    private const DEBUG_EMAIL = "stephenjohndavison@gmail.com"; // Set to empty string for normal operation
    // private const DEBUG_EMAIL = "";

    public static function send($post_id, $what){
        $post = get_post($post_id);
        echo "<div style='margin:2rem;'>";
        echo "<h1>Sending notifications</h1>";
        echo "<p>Sending $what notification emails for post [$post_id] <strong>$post->post_title</strong> to:</p>";
        // Get all subscribers
        $subscribers = get_posts(array(
            'numberposts' => -1,
            'post_type' => SJD_Subscriber::POST_TYPE,
            'post_status' => 'publish'
        ));
        $i = 1;
        $emails = intval(get_option('subscriber_message_emails')) || 1;
        $delay = intval(get_option('subscriber_message_delay')) || 1;
        $good = 0;
        $bad = 0;
        echo "<p>Sending emails in blocks of $emails emails with a delay of $delay secs between each.</p>";
        echo "<ol>";
        foreach( $subscribers as $subscriber ){
            $first_name = get_post_meta( $subscriber->ID, SJD_Subscriber::POST_PREFIX.'_first_name', $single=true);
            $last_name = get_post_meta( $subscriber->ID, SJD_Subscriber::POST_PREFIX.'_last_name', $single=true);
            if ( self::DEBUG_EMAIL != "" ){
                $email = self::DEBUG_EMAIL;
            } else {
                $email=$subscriber->post_title;
            }
            if ( self::send_notification_email($subscriber->ID, $first_name, $email, $post, $what) ){
                $good ++;
                // if ( $i < 11 ) echo "<li>[$subscriber->ID] $first_name $last_name ($email)</li>";
                if ( $i % $emails == 0 ) sleep($delay);
            } else {
                $bad ++;
                echo "<li style='color:red;'>[$subscriber->ID] $first_name $last_name ($email) - FAILED!</li>";
            }
            $i++;
        }
        echo "</ol>";
        // if ( $i > 10 ){
        //     $i = $i - 10;
        //     echo "<p>and $i others</p>";
        // }
        $i --;
        echo "<p>Tried to send $i emails: $good succeeded, $bad failed.</p>";
        echo "<a href='/wp-admin/post.php?post=$post->ID&action=edit'>Back to post</a>";
        echo "</div>";
    }


    public static function send_subscribe_email( $subscriber_id, $first_name, $email, $validation_key){
        $name = get_bloginfo('name');
        $domain = get_bloginfo('url');
        $url = get_option('subscriber_url');
        $subject = "Confirm your subscription to $name";
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $link = "$url?validate&email=$email&key=$validation_key";
        $img = get_option('subscriber_email_image');
        $message = array();
        $message[] = self::header($name, get_option('subscriber_email_image'));
        $message[] = "<p>Hi $first_name,</p>";
        $message[] = "<p>Please <a href='$link'>click here</a> to validate your subscription to 
                         receive updates from <strong>$name</strong>.</p>";
        $message[] = self::subscription_footer($name,$domain);
        $message = implode("",$message);
        return wp_mail( $email, $subject, $message, $headers);
    }


    public static function send_new_subscriber_email( $subscriber ){
        // print_r($subscriber);
        $post_type = SJD_Subscriber::POST_TYPE;
        $email = get_option('notify_on_subscribe_email');
        if ( $email == '' ){
            $email = get_option('admin_email');
        }
        $name = get_bloginfo('name');
        $domain = get_bloginfo('url');
        $url = get_option('subscriber_url');
        $subject = "New subscriber to $name";
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $img = get_option('subscriber_email_image');
        $message = array();
        $message[] = self::header($name, get_option('subscriber_email_image'));
        $message[] = "<p>You have a new subscriber [$subscriber->ID] $subscriber->first_name $subscriber->last_name ($subscriber->email)</p>";
        $message[] = "<p>View subscribers <a href='$domain/wp-admin/edit.php?post_type=$post_type'>here</a>. You will need to be logged in.</p>";
        $message = implode("",$message);
        return wp_mail( $email, $subject, $message, $headers);
    }


    public static function send_cancelled_subscriber_email( $subscriber ){
        // print_r($subscriber);
        $post_type = SJD_Subscriber::POST_TYPE;
        $email = get_option('notify_on_subscribe_email');
        if ( $email == '' ){
            $email = get_option('admin_email');
        }
        $name = get_bloginfo('name');
        $domain = get_bloginfo('url');
        $url = get_option('subscriber_url');
        $subject = "Cancelled subscription to $name";
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $img = get_option('subscriber_email_image');
        $message = array();
        $message[] = self::header($name, get_option('subscriber_email_image'));
        $message[] = "<p>Subscriber [$subscriber->ID] $subscriber->first_name $subscriber->last_name ($subscriber->email) cancelled their subscription.</p>";
        $message[] = "<p>View subscribers <a href='$domain/wp-admin/edit.php?post_type=$post_type'>here</a>. You will need to be logged in.</p>";
        $message = implode("",$message);
        return wp_mail( $email, $subject, $message, $headers);
    }


    public static function send_notification_email($subscriber_id, $first_name, $email, $post, $what){
        $name = get_bloginfo('name');
        $domain = get_bloginfo('url');
        // Send in html format
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $subject = "New content added to $name";
        $link = "$domain/$post->POST_PREFIX";
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
        
        if ( $what === 'PAGE' ){
            $message[] = self::header($post->post_title, $img);
            $message[] = "<p>Hi $first_name,</p>";
            $message[] = "<p>Here's an update from <strong>$name</strong>.</p>";
            $message[] = "<div class='divider'></div>";
            // Don't add breaks to tagged lines
            $content = str_replace(">".PHP_EOL,"§§§",$post->post_content);
            // Add breaks to none-tagged lines
            $content = str_replace(PHP_EOL,"<br>",$content);
            // Recover tagged lines
            $content = str_replace("§§§",">",$content);
            $message[] = self::pack($content);
        } else {
            $message[] = self::header($name, $img);
            $message[] = "<p>Hi $first_name,</p>";
            $message[] = "<p>Here's an update from <strong>$name</strong>.</p>";
            $message[] = "<h2><a href='$link'>$post->post_title</a></h2>";
        }
        $message[] = self::notification_footer($name,$subscriber_id,$email);
        $message = implode("",$message);
        return wp_mail( $email, $subject, $message, $headers);
    }

    private static function header($name,$img){
        $html = "<!doctype html>
            <html xmlns='http://www.w3.org/1999/xhtml' xmlns:v='urn:schemas-microsoft-com:vml' xmlns:o='urn:schemas-microsoft-com:office:office'>
                <head>
                    <meta charset='UTF-8'>
                    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                    <meta name='viewport' content='width=device-width, initial-scale=1'>
                    <title>{$name}</title>
                    <style>
                        body {
                            max-width:600px;
                            font-size: 12pt;
                        }
                        header img {
                            width:100%;
                            height:200px;
                            object-fit: cover;
                        }
                        footer {
                            margin:60px 0 10px 0;
                            padding: 0rem 1rem;
                            border: 1px solid grey;
                        }
                        h1, h2, h3 {
                            padding: 5px 0;
                            margin: 40px 0 0 0;
                        }
                        div.divider {
                            margin:40px 0;
                            border-bottom:1px solid grey;
                        }
                        p.signature {
                            margin: 40px 0 0 0;
                        }
                    </style>
                </head>
                <body>
                    <header><img src='$img'/><h1>$name</h1></header>";
        return self::pack($html);
    }

    private static function subscription_footer($name,$domain){
        $html = "<p class='signature'>Best wishes from the team at $name</p>
                <footer>
                    <p>
                        You have received this email because your details were used to register your interest 
                        in <a href='$domain'>$name</a>. If this was not you can safely ignore this email.
                    </p>
                </footer>
            </body>
        </html>";
        return self::pack($html);
    }

    private static function notification_footer($name, $subscriber_id,$email){
        $url = get_option('subscriber_url');
        $html = "<p class='signature'>Best wishes from the team at $name</p>
                <footer>
                    <p>
                        To unsubscribe and stop receiving emails from us please 
                        <a href='$url?unsubscribe&id=$subscriber_id&email=$email'>click here</a>.
                    </p>
                </footer>
            </body>
        </html>";
        return self::pack($html);
    }

    private static function pack($html){
        $html = str_replace(PHP_EOL," ",$html);
        $html = str_replace("  ", "§", $html);
        return str_replace("§","",$html);
    }

}