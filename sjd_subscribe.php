<?php
/**
 * Plugin Name: SJD Subscribe
 * Version: 0.0.1
 * Author: Steve Davison
 * Description: Provide simple subscription solution to register subscribers and manage 
 * email notifications for when new content is added
 * Dependencies Plugin: WP Mail SMTP (installs WP Forms but can be deactivated)
 * 
 */

// Set up wp_mail to use our email server
// Requires SMTP constants to be defiend in wp-config.php
// See top-level script for testing: mailtest.php
REQUIRE_ONCE (plugin_dir_path( __FILE__ ) . 'includes/email.php');

// Required include for using wp_delete_user()
REQUIRE( ABSPATH . 'wp-admin/includes/user.php');


//
// Initialisation
//

add_action( 'init', 'sjd_subscribe_init');

function sjd_subscribe_init(){
    $version = '0.0.1';
    wp_enqueue_style('sjd_subscribe_form', plugins_url("styles.css", __FILE__), [], $version);
    add_shortcode('sjd_subscribe_form', 'sjd_subscribe_form_shortcode');
}


function sjd_subscribe_form_shortcode(){
    echo "<h1>Home folder=".get_bloginfo('url')."</h1>";
    // Testing
    $clean = array( "first"=>"Steve", "last"=>"Gmail", "email"=>"stephenjohndavison@gmail.com" );
    $errors = array( "first"=>"", "last"=>"", "email"=>"" );
    // $clean = array( "first"=>"", "last"=>"", "email"=>"" );
    // $errors = array( "first"=>"", "last"=>"", "email"=>"" );
    // Check if submitted
    if ( isset($_POST['SUBMIT']) ) {
        // Process the form
        $results = sjd_subscribe_process();
        $status = $results['status'];
        $clean = $results['clean'];
        $errors = $results['errors'];
        // Got a valid subscription? If not the errors will be displayed on the form later
        if ( $status == 2 ){
            $username = $clean['first'] . ' ' . $clean['last'];
            $validation_key = random_string(32);
            $user = array(
                'user_pass' => random_string(),
                'user_login' => $clean['email'],
                'user_email' => $clean['email'],
                'first_name' => $clean['first'],
                'last_name' => $clean['last'],
                'description' => 'New subscriber',
                'role' => 'none'
            );
            $user_id = wp_insert_user($user);
            if( is_wp_error( $user_id ) ){
                $errors['email'] = "Whoops something went wrong sending our confirmation email.";
            } else {
                // Register the validation key as user meta data
                if ( add_user_meta($user_id, 'validation_key', $validation_key, $unique=true) !== false ){
                    sjd_subscribe_confirmation($user_id, $user,$validation_key);
                    return;
                } else {
                    $errors['email'] = "Whoops - something went wrong saving your subscription.";
                }
            } 
        }
    // Not submitted 
    } else {
        // Check for validation link
        if ( isset($_REQUEST['validate']) && isset($_REQUEST['key']) && isset($_REQUEST['email']) ){
            if( sjd_subscribe_validate($_REQUEST) ){ 
                echo "<p>Your subscription was validated! We will let you know when new content is added to the site.</p>";
                return;
            } else {
                echo "<p>We had a problem validating your subscription.</p>";
            }
        // Check for unsubscribe
        } else if ( isset($_REQUEST['unsubscribe']) && isset($_REQUEST['id']) && isset($_REQUEST['email']) ){
            if( sjd_subscribe_unsubscribe($_REQUEST) ){ 
                echo "<p>Your subscription has been cancelled. You will no longer receive emails notifications when new content is added to the site.</p>";
                return;
            } else {
                echo "<p>We had a problem cancelling your subscription.</p>";
            }
        }
        
    }
    // Display new form or partially completed form if errors found
    sjd_subscribe_form( $clean, $errors );
}


// subscribe form
function sjd_subscribe_form( $c, $e ){ ?>
    <p>Enter details below and then click Register.</p>
    <form id="subscribe" method="post">
        <?php foreach( $c as $key => $value) { 
            $label = $key=='email' ? 'Email' : ucfirst($key) . ' name' ;
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


// process submitted form
function sjd_subscribe_process(){
    $clean = array(
        'first' => sanitize_text_field($_POST['first']),
        'last'  => sanitize_text_field($_POST['last']),
        'email' => sanitize_email($_POST['email'])
    );
    $errors = array( "first"=>"", "last"=>"", "email"=>"" );
    $status = 2;
    if ( ! isset( $_POST['_sjd_subscribe_nonce'] ) ||
           wp_verify_nonce( $_POST['_sjd_subscribe_nonce'], 'sjd_subscribe_submit' ) !== 1 ){
        $errors['email'] = "Whoops - something went wrong. Please try again but if this problem persists please let us know.";
        $status = 0;
    }
    if ( $clean['first'] == '' ){
        $errors['first'] = "This value is required";
        $status = 1;
    }
    if ( $clean['first'] == '' ){
        $errors['first'] = "This value is required";
        $status = 1;
    }
    if ( $clean['email'] == '' ){
        $errors['email'] = "This value is required";
        $status = 1;
    }
    // Do we already have a user?
    $existing_user = get_user_by('email', $clean['email']);
    if ( $existing_user ){
        // If the record fully matched let them know that already subscribed
        if ( $clean['first'] === $existing_user->first_name && $clean['last'] === $existing_user->last_name){
            $errors['email'] = "You are already subscribed";
        // Otherwise may be someone phishing to find out who is registered
        } else {
            $errors['email'] = "There was a problem processing your request.";
        }
        $status = 1;
    }
    return array( 'status' => $status, 'clean'=>$clean, 'errors'=>$errors );
}


function sjd_subscribe_email($user_id,$user,$validation_key){
    $name = get_bloginfo('name');
    $domain = get_bloginfo('url');
    // Send in html format
    $headers = array("Content-Type: text/html; charset=UTF-8");
    $subject = "Confirm your subscription to $name";
    $email = $user['user_email'];
    $link = "$domain/subscribe?validate&email=$email&key=$validation_key";
    $message = array();
    $message[] = "<p>Hi ".$user['first_name'].",</p>";
    $message[] = "<p>Please click the link below to validate your subscription to receive updates on new posts from us here at $name:</p>";
    $message[] = "<p><a href='$link'>$link</a></p>";
    $message[] = "<p>Best wishes from the team at " . get_bloginfo('name');
    $message[] = "<br/><br/><p>To unsubscribe and stop receiving emails from use please click <a href='$domain/subscribe?unsubscribe&id=$user_id&email=$email'>here</a>.</p>";
    $message = implode( PHP_EOL.PHP_EOL, $message);
    echo "<div>$message</div>";
    return wp_mail( $email, $subject, $message, $headers);
}


function sjd_subscribe_confirmation($user_id, $user,$validation_key){ 
    $status = sjd_subscribe_email($user_id, $user,$validation_key); 
    print_r($status);
    if ( !is_wp_error( $status) ){
        echo "<h2>Nearly there " . $user['first_name'] ."!</h2>";
        echo "<p>We've sent you an email - please click on the link inside to confirm your subscription.</p>";
    }
}


function sjd_subscribe_validate($request){
    $clean = array(
        'key' => $request['key'],
        'email' => sanitize_email($request['email'])
    );
    // If have values then check against registered subscriber
    if ( $clean['email'] && $clean['key'] ){
        $existing_user = get_user_by('email', $clean['email']);
        if ( $existing_user ){
            $key = get_user_meta($existing_user->ID, 'validation_key',$single=true);
            // Get the validation key form the user meta data
            // If match then set the user as validated by setting role to subscriber
            echo "<p>User Key = $key</p>";
            echo "<p>Email Key = ". $clean['key'] ."</p>";
            if ( $key == $clean['key']){
                echo "Keys matched";
                delete_user_meta($existing_user->ID, 'validation_key');
                $existing_user->role = 'subscriber';
                $status = wp_update_user($existing_user);
                if ( !is_wp_error( $status) ){
                    return true;
                }
            }
        }
    }
    return false;
}   


function sjd_subscribe_unsubscribe($request){
    $clean = array(
        'user_id' => $request['id'],
        'email' => sanitize_email($request['email'])
    );
    // If have values then check against registered subscriber
    if ( $clean['email'] && $clean['user_id'] ){
        $existing_user = get_user_by('email', $clean['email']);
        if ( $existing_user ){
            $role = count($existing_user->roles) == 1 ? $existing_user->roles[1] : '';
            echo "<p>Found user [$existing_user->ID] with role [$role]</p>";
            print_r($existing_user->roles);
            // If match then delete the user (could bit don't reassign posts since checking that
            // the user only has a subscribe role)
            if ( $existing_user->ID == $clean['user_id'] && $role == 'subscriber'){
                echo "<p>deleting user</p>";
                $status = wp_delete_user($existing_user->ID);
                if ( !is_wp_error( $status) ){
                    echo "<p>deleted user</p>";
                    return true;
                }
            }
        }
    }
    return false;
}



// https://hughlashbrooke.com/2012/04/23/simple-way-to-generate-a-random-password-in-php/
function random_string( $length = 16 ) {
    // Need to be careful with choice of characters so that all are valid for urls
    // i.e. no ? or #
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@()_";
    $random = substr( str_shuffle( $chars ), 0, $length );
    return $random;
}




?>