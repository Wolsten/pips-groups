<?php
/**
 * Plugin Name: SJD Subscribe
 * Version: 0.0.1
 * Author: Steve Davison
 */


//
// Initialisation
//

add_action( 'init', 'sjd_subscribe_init');

function sjd_subscribe_init(){
    $version = '0.0.1';
    wp_enqueue_style('sjd_subscribe_form', plugins_url("styles.css", __FILE__), [], $version);
    // wp_enqueue_script('timeline', plugins_url("assets/timeline.js", __FILE__), [], $version, $in_footer=true );
    add_shortcode('sjd_subscribe_form', 'sjd_subscribe_form_shortcode');
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
    
    if ( check_admin_referer('sjd_subscribe_submit','_sjd_subscribe_nonce') !== 1 ){
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


function sjd_subscribe_email($user){
    $name = get_bloginfo('name');
    $domain = get_bloginfo('url');
    // Send in html format
    $headers = array(
        "Content-Type: text/html; charset=UTF-8", 
        "From: $name <$domain>");
    $subject = "Confirm your subscription to $name";
    $key = $user['user_activation_key'];
    $email = $user['user_email'];
    $link = "$domain/subscribe?validate&email=$email&key=$key";
    
    $message = array();

    //$message[] = "<h1>TEST PLEASE IGNORE</h1>";
    $message[] = "Hi ".$user['first_name'].",";
    $message[] = "<p>Please click the link below to validate your subscription to receive updates on new posts from us here at $name:</p>";
    $message[] = "<p><a href='$link'>$link</a></p>";
    $message = implode( PHP_EOL.PHP_EOL, $message);

    echo "<div>$message</div>";
}


function sjd_subscribe_confirmation($user){ 
    
    sjd_subscribe_email($user);
    
    
    ?>
    <h2>Nearly there <?= $user['first_name'] ?>!</h2>
    <p>We've sent you an email - please click on the link inside to confirm your subscription.</p>
    
    <?php 

    
}


function sjd_subscribe_validate(){

}


// https://hughlashbrooke.com/2012/04/23/simple-way-to-generate-a-random-password-in-php/
function random_string( $length = 16 ) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    $password = substr( str_shuffle( $chars ), 0, $length );
    return $password;
}



function sjd_subscribe_form_shortcode(){

    $status = -1;

    // Testing
    $clean = array( "first"=>"Sharon", "last"=>"Taylor", "email"=>"sharon.taylor@email.com" );
    $errors = array( "first"=>"", "last"=>"", "email"=>"" );
    
    // $clean = array( "first"=>"", "last"=>"", "email"=>"" );
    // $errors = array( "first"=>"", "last"=>"", "email"=>"" );

    if ( isset($_POST['SUBMIT']) ) {
        $results = sjd_subscribe_process();
        $status = $results['status'];
        $clean = $results['clean'];
        $errors = $results['errors'];
    }

    if ( $status == 2 ){

        $username = $clean['first'] . ' ' . $clean['last'];
        $activation_key = random_string(64);

        $user = array(
            'user_pass' => random_string(),
            'user_login' => $clean['email'],
            'user_email' => $clean['email'],
            'first_name' => $clean['first'],
            'last_name' => $clean['last'] . ' (unvalidated)',
            'description' => 'New subscriber',
            'role' => 'subscriber',
            'user_activation_key' => $activation_key
        );

        $user_id = wp_insert_user($user);

        if( ! is_wp_error( $user_id ) ){
            sjd_subscribe_confirmation($user);
            return;
        } else {
            $errors['email'] = "Whoops something went wrong sending our confirmation email.";
        }
    }

    sjd_subscribe_form( $clean, $errors );
}

?>