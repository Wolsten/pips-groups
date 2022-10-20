<?php

declare(strict_types=1);


class UserSubscription {


    public static function shortcode(){
        // echo "<h1>Home folder=".get_bloginfo('url')."</h1>";
        // Testing
        $clean = array( "first_name"=>"Steve", "last_name"=>"Davison", "email"=>"stephenjohndavison@gmail.com" );
        $errors = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        // $clean = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        // $errors = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        // Check if submitted
        if ( isset($_POST['SUBMIT']) ) {
            // Process the form - including checking for existing subscriber
            $results = self::clean_inputs();
            $status = $results['status'];
            $clean = $results['clean'];
            $errors = $results['errors'];
            // Got a valid subscription - if so create a subscription
            // If not the errors will be displayed on the form later
            if ( $status == 2 ){
                $subscriber = Subscriber::create($clean);
                if ( $subscriber === false ){
                    $errors['email'] = "Whoops something went wrong sending our confirmation email.";
                } else {
                    self::confirmation( $subscriber['post_id'], $subscriber['validation_key'], $clean );
                    return;
                }
            }
        // Not submitted 
        } else {
            // Check for sending notifications
            if ( isset($_REQUEST['notification']) ){
                $post_id = intval($_REQUEST['notification']);
                if ( isset($_REQUEST['confirm']) ){
                    Notifications::send($post_id);
                } else {
                    Notifications::request_confirmation($post_id);
                }
                return;
            // Check for validation link
            } else if ( isset($_REQUEST['validate']) && isset($_REQUEST['key']) && isset($_REQUEST['email']) ){
                if( self::validate_subscription($_REQUEST) ){ 
                    echo "<p>Your subscription was validated! We will let you know when new content is added to the site.</p>";
                    return;
                } else {
                    echo "<p>We had a problem validating your subscription.</p>";
                }
            // Check for unsubscribe
            } else if ( isset($_REQUEST['unsubscribe']) && isset($_REQUEST['id']) && isset($_REQUEST['email']) ){
                if( self::unsubscribe($_REQUEST) ){ 
                    echo "<p>Your subscription has been cancelled. You will no longer receive emails notifications when new content is added to the site.</p>";
                    return;
                } else {
                    echo "<p>We had a problem cancelling your subscription.</p>";
                }
            }
            
        }
        // Display new form or partially completed form if errors found
        self::display_form( $clean, $errors );
    }


    private static function display_form( $c, $e ){ 
        // echo "Custom post type=".Subscriber::POST_TYPE?>
        <p>Enter details below and then click Register.</p>
        <form id="subscribe" method="post">
            <?php foreach( $c as $key => $value) { 
                $label = str_replace('_',' ',$key);
                $type = $key=='email' ? 'email' : 'text'; ?>
                <label for="<?= $key ?>" > 
                    <span><?= $label ?></span>
                    <input type="<?=$type?>" name="<?=$key?>" value="<?=$value?>" class="<?=$e[$key]?'error':'';?>"/>
                </label>
                <?php if ( $e[$key] ) { ?>
                    <div class="error"><?= $e[$key] ?></div>
                <?php } ?>
            <?php } ?>
            <label for="submit"> 
                <button type="submit" name="SUBMIT" id="SUBMIT" value="SUBMIT">Register</button>
            </label>           
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?>      
        </form>
    <?php }


    private static function clean_inputs(){
        $results = Subscriber::validate_fields($_POST);
        $clean = $results['clean'];
        $errors = $results['errors'];
        $status = 2;
        if ( $results['success'] == false ){
            $status = 1;
        }
        if ( ! isset( $_POST['_sjd_subscribe_nonce'] ) ||
            wp_verify_nonce( $_POST['_sjd_subscribe_nonce'], 'sjd_subscribe_submit' ) !== 1 ){
            $errors['email'] = "Whoops - something went wrong. Please try again but if this problem persists please let us know.";
            $status = 0;
        }
        // Already subscribed?
        $subscriber = Subscriber::get($clean['email']);
        if ( $subscriber ){
            // If the record fully matched let them know that already subscribed
            // print_r('<p>Subscriber</p>');
            // print_r($subscriber);
            if ( $clean['first_name'] === $subscriber->first_name && 
                 $clean['last_name']  === $subscriber->last_name ){
                $errors['email'] = "You are already subscribed";
            // Otherwise may be someone phishing to find out who is registered
            } else {
                $errors['email'] = "There was a problem processing your request.";
            }
            $status = 1;
        }
        return array( 'status' => $status, 'clean'=>$clean, 'errors'=>$errors );
    }


    static function confirmation($subscriber_id, $validation_key, $subscriber){ 
        $status = Notifications::send_subscribe_email($subscriber_id, $subscriber['first_name'], $subscriber['email'], $validation_key);
        if ( !is_wp_error( $status) ){
            echo "<h2>Nearly there " . $subscriber['first_name'] ."!</h2>";
            echo "<p>We've sent you an email - please click on the link inside to confirm your subscription.</p>";
        }
    }

    static function validate_subscription($request){
        $clean = array(
            'key' => $request['key'],
            'email' => sanitize_email($request['email'])
        );
        // If have values then check against registered subscriber
        if ( $clean['email'] && $clean['key'] ){
            $subscriber = Subscriber::get($clean['email']);
            // print_r('<p>Validating subscription - existing subscriber?</p>');
            // print_r($subscriber);
            if ( $subscriber ){
                // Get the validation key form the user meta data
                // If match then set the user as validated by setting role to subscriber
                // echo "<p>User Key = $subscriber->validation_key</p>";
                // echo "<p>Email Key = ". $clean['key'] ."</p>";
                if ( $subscriber->validation_key == $clean['key']){
                    // echo "Keys matched";
                    return Subscriber::validate($subscriber->ID);
                }
            }
        }
        return false;
    }   


    static function unsubscribe($request){
        $clean = array(
            'user_id' => $request['id'],
            'email' => sanitize_email($request['email'])
        );
        // If have values then check against registered subscriber
        if ( $clean['email'] && $clean['user_id'] ){
            $subscriber = Subscriber::get($clean['email']);
            if ( $subscriber ){
                print_r($subscriber);
                if ( $subscriber ){
                    $status = wp_delete_post($subscriber->ID, $force_delete=true);
                    if ( !$status ) return false;
                    return true;
                }
            }
        }
        return false;
    }




}