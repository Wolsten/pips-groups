<?php 


/* Ionos Mail Limits

The number of emails per hour per contract depends on the age of the mailbox being used:

Days    Per hour
0-7        50
8-14      100
15-30     400
30+     5,000

Therefore, need to plan in advance or consider blind copying to multiple recipients, for
which there is a limit of 200 per email. */

// show wp_mail() errors
add_action( 'wp_mail_failed', 'onMailError', 10, 1 );
function onMailError( $wp_error ) {
    echo "<pre>";
    print_r($wp_error->errors);
    // A typical error looks like this:
    // SMTP Error: The following recipients failed: leaversofburnley@gmail.com: Requested mail action not taken: mailbox unavailable
    // Mail send limit exceeded.
    echo "</pre>";
}  

class SJD_Notifications {
    // private const DEBUG_EMAIL = "stephenjohndavison@gmail.com"; // Set to empty string for normal operation
    private const DEBUG_EMAIL = "";

    public static function send($post_id, $what, $min){
        $post = get_post($post_id);
        echo "<div style='margin:2rem;'>";
        echo "<h1>Sending notifications</h1>";
        echo "<p>Sending $what notification emails for post [$post_id] <strong>$post->post_title</strong></p>";
        // Check for shortcodes in the content
        if ( $what=='PAGE' ){
            $re = '/^\[.{5,}\]/m';
            $str = $post->post_content;
            if ( preg_match($re, $str)==1 ){
                echo "<p>Could not send this content because it looks like it contains at least one shortcode, e.g. [name ....].</p>";
                echo "<p>You cannot send page content with embedded shortcodes as they may generate dynamic content that is not available except via the web page but you can send as a link instead.</p>";
                echo "<a href='/wp-admin/post.php?post=$post->ID&action=edit'>Back to post</a>";
                echo "</div>";
                return;
            }
        }
        // Get all subscribers
        $subscribers = SJD_Subscriber::all();
        $i = 0;
        $skipped = 0;
        $good = 0;
        $bad = 0;
        $stop_on_first_fail = (bool) get_option('subscriber_stop_on_first_fail')=='1';
        echo "<p>Sending emails, skipping those below $min.</p>";
        echo "<ol>";
        foreach( $subscribers as $subscriber ){
            $i++;
            if ( self::DEBUG_EMAIL != "" ){
                $email = self::DEBUG_EMAIL;
            } else {
                $email=$subscriber->post_title;
            }
            $entry = "[$subscriber->ID] $subscriber->first_name $subscriber->last_name ($email)";
            if ( $i < $min ){
                $skipped ++;
                echo "<li>$entry - SKIPPED</li>";
            } else if ($bad==0 || $stop_on_first_fail==false ) {
                $status = self::send_notification_email($subscriber->ID, $subscriber->first_name, $email, $post, $what);
                if ( $status ){
                    $good ++;
                    echo "<li style='color:green;'>$entry - SENT</li>";
                } else {
                    $bad ++;
                    echo "<li style='color:red;'>$entry - FAILED!</li>";
                }
            }
        }
        echo "</ol>";// 
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
        
        if ( $what === 'PAGE' ){
            $message[] = self::header($post->post_title, $img);
            $message[] = "<p>Hi $first_name,</p>";
            $message[] = "<p>Here's an update from <strong>$name</strong>.</p>";
            $message[] = "<div class='divider'></div>";
            $content = $post->post_content;
            // Remove any short codes - \n[xxxx]\n i.e. must start and end on one line
            $regex = '/^\[.+\]$/m';
            $replace = '';
            $content = preg_replace($regex, $replace, $content); 
            // Don't add breaks to tagged lines
            $content = str_replace(">".PHP_EOL,"§§§",$content);
            // Add breaks to none-tagged lines
            $content = str_replace(PHP_EOL,"<br>",$content);
            // Recover tagged lines
            $content = str_replace("§§§",">",$content);
            $message[] = self::pack($content);
        } else {
            $message[] = self::header($name, $img);
            $message[] = "<p>Hi $first_name,</p>";
            $message[] = "<p>We have just added a new post:</p>";
            $message[] = "<div class='main-content'>";
            $message[] = "<h1><a href='$link'>$post->post_title</a></h1>";
            $message[] = "<p>$post->post_excerpt</p>";
            $message[] = "</div>";
        }
        $message[] = self::notification_footer($name,$subscriber_id,$email);
        $message = implode("",$message);
        // echo $message;
        // return true;
        return wp_mail( $email, $subject, $message, $headers);
    }

    private static function header($name,$img){
        $primary_colour = get_option('subscriber_email_primary_colour');
        $html = "<!doctype html>
            <html xmlns='http://www.w3.org/1999/xhtml' xmlns:v='urn:schemas-microsoft-com:vml' xmlns:o='urn:schemas-microsoft-com:office:office'>
                <head>
                    <meta charset='UTF-8'>
                    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
                    <meta name='viewport' content='width=device-width, initial-scale=1'>
                    <title>$name</title>
                    <style>
                        body {
                            font-size: 12pt;
                        }
                        header img {
                            width:100%;
                            height:200px;
                            object-fit: cover;
                        }
                        header .site-name {
                            font-size:24pt;
                            text-align:center;
                            padding: 10px;
                            margin: 10px 0 0 0;
                            border: 1px solid lightgrey;
                            color:$primary_colour;
                        }
                        footer {
                            margin:40px 0 10px 0;
                            padding: 0rem 1rem;
                            border: 1px solid lightgrey;
                        }
                        h1, h2, h3 {
                            padding: 5px 0;
                            margin: 20px 0 0 0;
                            color:$primary_colour;
                        }
                        h1 {
                            text-align:center;

                        }
                        h1 a {
                            text-decoration:none;
                            color:$primary_colour;
                        }
                        div.main-content {
                            margin:40px 10% 40px 10%;
                            padding: 10px;
                            border: 1px solid lightgrey;
                        }
                        div.main-content * {
                            text-align:center;
                        }
                        div.divider {
                            margin:40px 0;
                            border-bottom:1px solid lightgrey;
                        }
                        p.signature {
                            margin: 40px 0 0 0;
                        }
                    </style>
                </head>
                <body>
                    <header>
                        <div class='site-name'>$name</div>
                        <img src='$img'/>
                    </header>";
        return self::pack($html);
    }

    private static function subscription_footer($name,$domain){
        $html = "<p class='signature'>Best wishes from the team at $name.</p>
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
        $html = "<h3>Please share</h3>
                <p>
                    If you enjoy reading this and please do share with others. New visitors can <a href='$url'>click here</a> to subscribe to our newsletter.
                </p>
                <p class='signature'>
                    Best wishes from the team at $name.
                </p>
                <footer>
                    <p>
                        To unsubscribe and stop receiving emails from us please <a href='$url?unsubscribe&id=$subscriber_id&email=$email'>click here</a>.
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