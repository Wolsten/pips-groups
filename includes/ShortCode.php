<?php

declare(strict_types=1);


class ShortCode {

    private static function admin_functions() {
        $submit = false;
        if ( isset($_POST['SUBMIT']) ) {

            // Check the nonce;
            if ( ! isset( $_POST['_sjd_subscribe_nonce'] ) ||
                wp_verify_nonce( $_POST['_sjd_subscribe_nonce'], 'sjd_subscribe_submit' ) !== 1 ){
                echo "<p>Whoops - something went wrong. Please try again but if this problem persists please let us know.</p>";
                return;
            }
            $submit=$_POST['SUBMIT'];

            // Send notifications
            if ( $submit == 'link' || $submit == 'page' ){

                $post_id = intval($_POST['post_id']);
                Notifications::send($post_id, $submit);

            // Check whether to display import subscribers form
            } else if ( $submit == 'IMPORT_SUBSCRIBERS' ){
                $submit = false;
                self::file_form($submit);
                return;

            // Upload subscribers
            } else if ( $submit == 'CONFIRM_IMPORT_SUBSCRIBERS' ){
                self::file_form($submit);

            } else if ( $submit == 'DELETE_SUBSCRIBERS' ){
                $submit = false;
                self::delete_form();
                return;

            } else if ( $submit == 'CONFIRM_DELETE_SUBSCRIBERS' ){
                Subscriber::delete_subscribers($submit);

            } else if ( $submit == 'EXPORT_SUBSCRIBERS' ){
                Subscriber::export_subscribers();
            }

        } else if ( isset($_REQUEST['notification']) ){

            $post_id = intval($_REQUEST['post_id']);
            self::notifications_form($submit, $post_id);
            return;
        } 
        
        self::admin_form();
    }


    private static function user_functions(){
        $submit = false;
        if ( isset($_POST['SUBMIT']) ) {
            $submit = $_POST['SUBMIT'];
            // Check the nonce;
            if ( ! isset( $_POST['_sjd_subscribe_nonce'] ) ||
                wp_verify_nonce( $_POST['_sjd_subscribe_nonce'], 'sjd_subscribe_submit' ) !== 1 ){
                echo "<p>Whoops - something went wrong. Please try again but if this problem persists please let us know.</p>";
                return;
            }
        // USER VALIDATION
        } else if ( isset($_REQUEST['validate']) && isset($_REQUEST['key']) && isset($_REQUEST['email']) ){
            if( self::validate_subscription($_REQUEST) ){ 
                echo "<p>Your subscription was validated! We will let you know when new content is added to the site.</p>";
                return;
            } else {
                echo "<p>We had a problem validating your subscription.</p>";
            }
        // USER UNSUBSCRIBE
        } else if ( isset($_REQUEST['unsubscribe']) && isset($_REQUEST['id']) && isset($_REQUEST['email']) ){
            if( self::unsubscribe($_REQUEST) ){ 
                echo "<p>Your subscription has been cancelled. You will no longer receive emails notifications when new content is added to the site.</p>";
                return;
            } else {
                echo "<p>We had a problem cancelling your subscription.</p>";
            }
        }
        self::user_form($submit);
    }


    public static function init(){
        // Save the page url where the shortcode is used for using in Notifications
        $domain = get_bloginfo('url');
        global $post;
        $url = "$domain/$post->post_name";
        update_option('subscriber_url', $url);

        // Is user logged in
        if ( is_user_logged_in() ){
            self::admin_functions();
        } else {
            self::user_functions();
        }

        // // Not submitted 
        // } else {

        //     // Check for sending notifications
        //     if ( isset($_REQUEST['notification']) ){
        //         // This requires logged in access
        //         if ( is_user_logged_in() ){
        //             $post_id = intval($_REQUEST['notification']);
        //             if ( isset($_REQUEST['confirm']) ){
        //                 $confirm = sanitize_text_field($_REQUEST['confirm']);
        //                 Notifications::send($post_id, $confirm);
        //             } else {
        //                 Notifications::request_confirmation($post_id);
        //             }
        //         } else {
        //             echo "<h2>File not found</h2>";
        //         }
        //         return;
        //     // Check for importing
        //     } else if (isset($_REQUEST['import']) ) {
        //         if ( is_user_logged_in() ){
        //             Subscriber::import($post_id, $confirm);
        //         } else {
        //             echo "<h2>File not found</h2>";
        //         }
        //         return;
        //     // Check for validation link
        //     } else if ( isset($_REQUEST['validate']) && isset($_REQUEST['key']) && isset($_REQUEST['email']) ){
        //         if( self::validate_subscription($_REQUEST) ){ 
        //             echo "<p>Your subscription was validated! We will let you know when new content is added to the site.</p>";
        //             return;
        //         } else {
        //             echo "<p>We had a problem validating your subscription.</p>";
        //         }
        //     // Check for unsubscribe
        //     } else if ( isset($_REQUEST['unsubscribe']) && isset($_REQUEST['id']) && isset($_REQUEST['email']) ){
        //         if( self::unsubscribe($_REQUEST) ){ 
        //             echo "<p>Your subscription has been cancelled. You will no longer receive emails notifications when new content is added to the site.</p>";
        //             return;
        //         } else {
        //             echo "<p>We had a problem cancelling your subscription.</p>";
        //         }
        //     }
            
        // }

        // // Display new form or partially completed form if errors found
        // self::user_form( $clean, $errors );
    }


    private static function user_form( $submitted ){ 
        $clean = array( "first_name"=>"Steve", "last_name"=>"Davison", "email"=>"stephenjohndavison@gmail.com" );
        $errors = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        // $clean = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        // $errors = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        if ( $submitted ){
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
        } ?>
        <p>Enter details below and then click Register.</p>
        <form id="subscribe" method="post">
            <?php foreach( $clean as $key => $value) { 
                $label = str_replace('_',' ',$key);
                $type = $key=='email' ? 'email' : 'text'; ?>
                <label for="<?= $key ?>" > 
                    <span><?= $label ?></span>
                    <input type="<?=$type?>" name="<?=$key?>" value="<?=$value?>" class="<?=$errors[$key]?'error':'';?>"/>
                </label>
                <?php if ( $errors[$key] ) { ?>
                    <div class="error"><?= $errors[$key] ?></div>
                <?php } ?>
            <?php } ?>
            <label for="submit"> 
                <button type="submit" name="SUBMIT" id="SUBMIT" value="REGISTER">Register</button>
            </label>           
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?>      
        </form>
    <?php }


    private static function admin_form(){ ?>
        <h2>Admin functions</h2>
        <form id="admin" method="post">
            <button type="submit" name="SUBMIT" id="SUBMIT" value="IMPORT_SUBSCRIBERS">Import Subscribers</button>
            <button type="submit" name="SUBMIT" id="SUBMIT" value="DELETE_SUBSCRIBERS">Delete Subscribers</button>
            <button type="submit" name="SUBMIT" id="SUBMIT" value="EXPORT_SUBSCRIBERS">Export Subscribers</button>
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?> 
        </form>
    <?php }

    private static function delete_form(){ ?>
        <h2>Delete subscribers</h2>
        <p>Click the button below to delete all subscribers. WARNING: This operation cannot be reversed other than by re-importing.</p>
        <form id="admin" method="post">
            <button type="submit" name="SUBMIT" id="SUBMIT" value="CONFIRM_DELETE_SUBSCRIBERS">Delete Subscribers</button>
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?> 
        </form>
    <?php }


    private static function file_form($submit){ 
        $file = '';
        $file_error = '';
        if ( $submit ){
            if ( isset($_FILES['csv']) == false ){
                $file_error = "No file selected";
            } 
            if ( $file_error == '' ){
                $file_name = $_FILES['csv']['name'];
                $parts = explode('.',$file_name);
                $extension = $parts[count($parts)-1];
                if ( $extension !== 'csv'){
                    $file_error = "You must choose a csv file";
                }
            }
            if ( $file_error == '' ){
                $file = $_FILES['csv']['tmp_name'];
                Subscriber::import_subscribers($file);
            }
        }
        if ( $submit==false || $file_error != '' ){ ?>
            <p>Choose file and then press Upload.</p>
            <form id="import" method="post" enctype="multipart/form-data">
                <label for="csv" > 
                    <span>CSV file</span>
                    <input type="file" name="csv" value="<?=$file?>" class="<?=$file_error?'error':'';?>"/>
                    <?php if ( $file_error ) { ?>
                    <div class="error"><?= $file_error ?></div>
                <?php } ?>
                </label>
                <label for="submit"> 
                    <button type="submit" name="SUBMIT" id="SUBMIT" value="CONFIRM_IMPORT_SUBSCRIBERS">Upload</button>
                </label>           
                <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?>      
            </form>
        <?php }
    }


    private static function notifications_form($submit, $post_id){ 
        $post = get_post($post_id) ?>
        <p>Notify subscribers about the following post:</p>
        <p><?=$post_post_title?></p>
        <p>Click one of the buttons below to send notifications to all subscribers.</p>
        <form id="notify" method="post">
            <input type="hidden" name="post_id" value="<?=$post_id?>"/>
            <p><button type="submit" name="SUBMIT" id="SUBMIT" value="LINK">Send link</button></p>     
            <p><button type="submit" name="SUBMIT" id="SUBMIT" value="PAGE">Send full page</button></p>   
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?>      
        </form>
        <p>Sending a full page is designed for newsletter style posts.</p>
        <p>There will be a delay whilst the emails are sent - do not move away from this page until the list of subscribers emailed have been returned.</p>
    <?php }


    private static function clean_inputs(){
        $results = Subscriber::validate_fields($_POST);
        $clean = $results['clean'];
        $errors = $results['errors'];
        $status = 2;
        if ( $results['success'] == false ){
            $status = 1;
        }
        // Already subscribed?
        $subscriber = Subscriber::get($clean['email']);
        if ( $subscriber ){
            // If the record fully matched let them know that already subscribed
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