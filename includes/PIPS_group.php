<?php

declare(strict_types=1);

class PIPS_group {

    public const POST_TYPE = 'groups'; // Custom post type
    public const POST_PREFIX = 'group';  // Prefix for custom fields


    // Use standard fields for name, description and image
    public const CUSTOM_FIELDS = array(
        array(
            "name"=>"website", 
            "title"=>"Web site", 
            "type"=>"text", 
            "validation"=>"url",
            "required"=>true,
            "prefixed"=>true,
        ),
        array(
            "name"=>"email",
             "title"=>"Email",
             "type"=>"email", 
             "validation"=>"email",
             "required"=>true, 
             "prefixed"=>true,
            ),
        array(
            "name"=>"telephone", 
            "title"=>"Telephone", 
            "type"=>"text", 
            "validation"=>"text",
            "required"=>false, 
            "prefixed"=>true,
        ),
        array(
            "name"=>"location", 
            "title"=>"Location", 
            "type"=>"text", 
            "validation"=>"text",
            "required"=>false, 
            "prefixed"=>true
        ),
    );


    public static function init(){
        register_post_type(self::POST_TYPE, array(
            'label' => ucfirst(self::POST_TYPE),
            'singular_label' => ucfirst(self::POST_PREFIX),
            'public' => true,
            'show_ui' => true, // UI in admin panel
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-share',
            'capability_type' => 'post',
            'hierarchical' => false,
			'has_archive' => true,
            'rewrite' => array("slug" => self::POST_TYPE), // Permalinks format
            'show_in_rest' => false,
            'supports' => array('title', 'editor','excerpt','thumbnail')
        ));
        add_action('add_meta_boxes', 'PIPS_group::add_meta_boxes', 10, 1 );
        add_action('save_post', 'PIPS_group::save_meta_data' );
        add_filter('manage_'.self::POST_TYPE.'_posts_columns', 'PIPS_group::admin_columns', 10, 1 );
    }


    public static function add_meta_boxes($post_type){
        if ( $post_type==self::POST_TYPE ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                add_meta_box(
                    $html_id=self::prefix($field['prefixed']).$field['name'],
                    $title=$field['title'],
                    $display_callback=Array('PIPS_group','display_meta_box'),
                    $screen=null, 
                    $context='normal', 
                    $priority='high',
                    $callback_args=array( $field )
                );
            }
        }
    }



    // Format of json in Travellers' Map:
    //
    // {
    //     "latitude": "53.539",
    //     "longitude": "-2.288",
    //     "markerdata": [
    //         "http://test.local/wp-content/uploads/2022/12/cttm_markers-black.png",
    //         32,
    //         45
    //     ],
    //     "multiplemarkers": false,
    //     "customtitle": "",
    //     "customexcerpt": "",
    //     "customthumbnail": "0",
    //     "customanchor": ""
    // }


    public static function display_meta_box( $post, $args){
        $field = $args['args'][0];
        $id = self::prefix($field['prefixed']).$field['name'];
        $value = esc_attr(get_post_meta( $post->ID, $id, true ));

        echo "&nbsp;<input type='".$field['type']."' id='$id' name='$id' value='$value' size='50' />";
        if ( $field['required'] && $value == '' ){
            echo "<p style='background-color:pink;padding:0.5rem;'>This value is required.</p>";
            
        }
    }


    public static function save_meta_data( $post_id ) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
            return;
        }
        $post_type=get_post_type($post_id);
        if ( $post_type==self::POST_TYPE ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                $id = self::prefix($field['prefixed']).$field['name'];
                // echo "<p>id = $id</p>";
                if ( array_key_exists( $id, $_POST ) ){
                    $data = self::sanitise_field($field['validation'], $_POST[$id]);
                    // echo "<p>Sanitised data = $data</p>";
                    update_post_meta( $post_id, $id, $data );
                }
            }
        }
        // die();
    }


    public static function sanitise_field($validation,$value){
        if ( $validation == 'email' ){
            return sanitize_email( $value );
        } else if ( $validation == 'integer') {
            return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
        } else if ( $validation == 'float') {
            return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        } else if ( $validation == 'url') {
            return filter_var($value, FILTER_SANITIZE_URL);
        }
        return sanitize_text_field( $value );
    }


    public static function admin_columns($columns){
        unset($columns['date']);
        foreach( self::CUSTOM_FIELDS as $field ){
            $columns[self::prefix($field['prefixed']).$field['name']] = $field['name'];
        }
        $columns['date'] = 'Date';
        return $columns;
    }


    // public static function admin_column($column_id, $post_id){
    //     echo get_post_meta( $post_id, $column_id, $single=true);
    // }


    public static function get( $email ){
        $post = get_page_by_title($title=$email,$output='OBJECT',$post_type=self::POST_TYPE);
        if ( $post ){
            // Add meta data to the post object
            $meta = get_post_meta( $post->ID );
            if ( $meta ){
                foreach( $meta as $key=>$value ){
                    $name = str_replace(self::POST_PREFIX.'_', '', $key);
                    $post->$name = $value[0];
                }
                return $post;
            }
        }
        return false;
    }


    public static function create( $fields ){
        $new_subscriber = array(
            'post_title' => $fields['email'],
            'post_status' => 'draft',
            'post_type' => self::POST_TYPE,
        );
        $post_id = wp_insert_post($new_subscriber);
        // echo "<p>post id $post_id</p>";
        $success = true;
        $validation_key = '';
        if ( $post_id > 0 ){
            foreach( self::CUSTOM_FIELDS as $field ){
                $value = $fields[$field['name']];
                $meta_id = update_post_meta($post_id, self::prefix($field['prefixed']).$field['name'], $value, $unique=true);
                if ( $meta_id === false ){
                    $success = false;
                }
            }
        }
        if ( $success ){
            $new_group['ID'] = $post_id;
            $new_group['website'] = $fields['website'];
            $new_group['telephone'] = $fields['telephone'];
            $new_group['email'] = $fields['email'];
            $new_group['location'] = $fields['location'];
            $new_group['latitude'] = $fields['latitude'];
            $new_group['longitude'] = $fields['longitude'];
            return (object) $new_group;
        }
        return false;
    }


    public static function all(){
        $groups = get_posts(array(
            'numberposts' => -1,
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish'
        ));
        foreach( $groups as $group ){
            $group->location = get_post_meta( $group->ID, self::POST_PREFIX.'location', $single=true);
        }
        return $groups;
    }


    public static function prefix($prefixed){
        return $prefixed ? self::POST_PREFIX.'_' : '';
    }


}