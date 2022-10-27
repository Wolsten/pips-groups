<?php 

class SJD_Notifications {

    private const DEBUG_EMAIL = "stephenjohndavison@gmail.com"; // Set to empty string for normal operation


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
        $emails = get_option('subscriber_message_emails') || 1;
        $delay = get_option('subscriber_message_delay') || 1;
        echo "<ol>";
        foreach( $subscribers as $subscriber ){
            $first_name = get_post_meta( $subscriber->ID, SJD_Subscriber::POST_PREFIX.'_first_name', $single=true);
            $last_name = get_post_meta( $subscriber->ID, SJD_Subscriber::POST_PREFIX.'_last_name', $single=true);
            if ( self::DEBUG_EMAIL != "" ){
                $email = self::DEBUG_EMAIL;
            } else {
                $email=$subscriber->post_title;
            }
            if ( self::DEBUG_EMAIL == "" || $i==1 ){
                if ( self::send_notification_email($subscriber->ID, $first_name, $email, $post, $what) ){
                    echo "<li>$first_name $last_name ($email)</li>";
                    if ( $i % $emails == 0 ){
                        sleep($delay);
                    }
                    $i++;
                }
            }
        }
        echo "</ol>";
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
        $message = implode($message);
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
            $message[] = str_replace(PHP_EOL,"<br>",$post->post_content);
        } else {
            $message[] = self::header($name, $img);
            $message[] = "<p>Hi $first_name,</p>";
            $message[] = "<p>Here's an update from <strong>$name</strong>.</p>";
            $message[] = "<h2><a href='$link'>$post->post_title</a></h2>";
        }
        $message[] = self::notification_footer($name,$subscriber_id,$email);
        $message = implode($message);
        return wp_mail( $email, $subject, $message, $headers);
    }

    private static function header($name,$img){
        return "<!doctype html>
            <html xmlns='http://www.w3.org/1999/xhtml' 
                  xmlns:v='urn:schemas-microsoft-com:vml' 
                  xmlns:o='urn:schemas-microsoft-com:office:office'>
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
                        body > header img {
                            width:100%;
                            height:200px;
                            object-fit: cover;
                        }
                        body > footer {
                            margin:2rem 0;
                            padding: 0rem 1rem;
                            border: 1px solid grey;
                        }
                        .divider {
                            margin:2rem 0;
                            border-bottom:1px solid grey;
                        }
                    </style>
                </head>
                <body>
                    <header><img src='$img'/><h1>$name</h1></header>";
    }

    private static function subscription_footer($name,$domain){
        return "<p>Best wishes from the team at $name</p>
                <footer>
                    <p>
                        You have received this email because your details were used to register your interest 
                        in <a href='$domain'>$name</a>. If this was not you can safely ignore this email.
                    </p>
                </footer>
            </body>
        </html>";
    }

    private static function notification_footer($name, $subscriber_id,$email){
        $url = get_option('subscriber_url');
        return "<p>Best wishes from the team at $name</p>
                <footer>
                    <p>
                        To unsubscribe and stop receiving emails from us please 
                        <a href='$url?unsubscribe&id=$subscriber_id&email=$email'>click here</a>.
                    </p>
                </footer>
            </body>
        </html>";
    }

}