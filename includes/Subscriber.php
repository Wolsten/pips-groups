<?php

declare(strict_types=1);

class Subscriber {

    public const POST_TYPE = 'subscribers'; // Custom post type
    public const POST_NAME = 'subscriber';  // Custom post name used as the prefix for custom fields

    public const CUSTOM_FIELDS = array(
        array("name"=>"first_name", "title"=>"First name", "type"=>"text", "required"=>true),
        array("name"=>"last_name", "title"=>"Last name", "type"=>"text", "required"=>true),
        array("name"=>"email", "title"=>"Email", "type"=>"email", "required"=>true),
        array("name"=>"validation_key", "title"=>"Validation key", "type"=>"text", "required"=>false),
    );

    public static function init(){
        register_post_type(self::POST_TYPE, array(
            'label' => ucfirst(self::POST_TYPE),
            'singular_label' => ucfirst(self::POST_NAME),
            'public' => true,
            'show_ui' => true, // UI in admin panel
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-share',
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array("slug" => self::POST_NAME), // Permalinks format
            'supports' => array('title', 'editor')
        ));
        add_action('add_meta_boxes', 'Subscriber::add_meta_boxes', 10, 1 );
        add_action('save_post', 'Subscriber::save_meta_data' );
        add_filter('manage_'.self::POST_TYPE.'_posts_columns', 'Subscriber::admin_columns', 10, 1 );
        add_filter('manage_posts_custom_column',  'Subscriber::admin_column', 10, 2);
    }

    public static function add_meta_boxes($post_type){
        if ( $post_type==self::POST_TYPE ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                add_meta_box(
                    $html_id=self::POST_NAME.'_'.$field['name'],
                    $title=$field['title'],
                    $display_callback=Array('Subscriber','display_meta_box'),
                    $screen=null, 
                    $context='normal', 
                    $priority='high',
                    $callback_args=array( $field )
                );
            }
        }
    }

    public static function display_meta_box( $post, $args){
        $field = $args['args'][0];
        $id = self::POST_NAME.'_'.$field['name'];
        $value = esc_attr(get_post_meta( $post->ID, $id, true ));
        echo "<label for='$id'>".$field['title']."</label>";
        echo "&nbsp;<input type='".$field['type']."' id='$id' name='$id' value='$value' size='50' />";
    }

    public static function save_meta_data( $post_id ) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
            return;
        }
        $post_type=get_post_type($post_id);
        if ( $post_type==self::POST_TYPE ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                $id = self::POST_NAME.'_'.$field['name'];
                if ( array_key_exists( $id, $_POST ) ){
                    $data = self::sanitise_field($field['type'], $_POST[$id]);
                    update_post_meta( $post_id, $id, $data );
                }
            }
        }
    }

    public static function sanitise_field($name,$value){
        if ( $name == 'email' ){
            return sanitize_email( $value );
        }
        return sanitize_text_field( $value );
    }

    public static function validate_fields($inputs){
        $clean = array();
        $errors = array();
        $success = true;
        // print_r($inputs);
        foreach( self::CUSTOM_FIELDS as $field ){
            if ( isset($inputs[$field['name']])){
                $clean[$field['name']] = self::sanitise_field($field,$inputs[$field['name']]);
                $errors[$field['name']] = '';
                if ( $field['required'] && $clean[$field['name']] == ''){
                    $errors[$field['name']] = "This value is required";
                    $success = false;
                }
            }
        }
        return array('clean'=>$clean, 'errors'=>$errors, 'success'=>$success);
    }
    
    public static function admin_columns($columns){
        unset($columns['date']);
        foreach( self::CUSTOM_FIELDS as $field ){
            if ( $field['name'] !== 'email'){
                $columns[self::POST_NAME.'_'.$field['name']] = $field['title'];
            }
        }
        $columns['date'] = 'Date';
        return $columns;
    }

    public static function admin_column($column_id, $post_id){
        echo get_post_meta( $post_id, $column_id, $single=true);
    }

    public static function get( $email ){
        $post = get_page_by_title($title=$email,$output='OBJECT',$post_type=self::POST_TYPE);
        if ( $post ){
            // Add meta data to the post object
            $meta = get_post_meta( $post->ID );
            // print_r('<p>got meta</p>');
            // print_r($meta);
            if ( $meta ){
                foreach( $meta as $key=>$value ){
                    $name = str_replace(self::POST_NAME.'_', '', $key);
                    $post->$name = $value[0];
                }
                return $post;
            }
        }
        return false;
    }

    public static function create( $fields ){
        // print_r('<p>Fields</p>');
        // print_r($fields);
        $newSubscriber = array(
            'post_title' => $fields['email'],
            'post_status' => 'draft',
            'post_type' => self::POST_TYPE,
        );
        $post_id = wp_insert_post($newSubscriber);
        // echo "<p>post id $post_id</p>";
        $success = true;
        $validation_key = '';
        if ( $post_id > 0 ){
            foreach( self::CUSTOM_FIELDS as $field ){
                if ( $field['name'] == 'validation_key' ){
                    $validation_key = self::random_string(32);
                    $value = $validation_key;
                } else {
                    $value = $fields[$field['name']];
                }
                $meta_id = update_post_meta($post_id, self::POST_NAME.'_'.$field['name'], $value, $unique=true);
                if ( $meta_id === false ){
                    $success = false;
                }
            }
        }
        if ( $success ){
            return array(
                'post_id' => $post_id,
                'validation_key' => $validation_key
            );
        }
        return false;
    }

    public static function validate( $post_id ){
        // Unset validation key
        $status = update_post_meta($post_id, self::POST_NAME.'_validation_key', $value='');
        // echo "<p>update_post_meta status for post id $post_id [";
        // print_r($status);
        // echo "]</p>";
        // Update post status
        if ( $status ){
            // echo "<p>wp_update_post for post id $post_id:</p>";
            $status = wp_update_post( array(
                'ID'=>$post_id, 
                'post_status'=>'publish'
            ));
            if ( is_wp_error($status) ){
                $status = false;
            }
        }
        return $status;
    }

    // https://hughlashbrooke.com/2012/04/23/simple-way-to-generate-a-random-password-in-php/
    private static function random_string( $length = 64) {
        // Need to be careful with choice of characters so that all are valid for urls
        // i.e. no ? or #
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@()_";
        return substr( str_shuffle( $chars ), 0, $length );
    }

}