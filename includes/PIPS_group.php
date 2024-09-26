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
        ),
        array(
            "name"=>"email",
             "title"=>"Email",
             "type"=>"email", 
             "validation"=>"email",
             "required"=>false, 
            ),
        array(
            "name"=>"social",
                "title"=>"Social media id (e.g. Twitter handle)",
                "type"=>"text", 
                "validation"=>"text",
                "required"=>false, 
            ),
        array(
            "name"=>"telephone", 
            "title"=>"Telephone", 
            "type"=>"text", 
            "validation"=>"text",
            "required"=>false, 
        ),
        array(
            "name"=>"location", 
            "title"=>"Location", 
            "type"=>"text", 
            "validation"=>"text",
            "required"=>false, 
        ),
    );


    public static function pips_init(){
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
        add_action('add_meta_boxes', __CLASS__.'::add_meta_boxes', 10, 1 );
        add_action('save_post', __CLASS__.'::pips_save_meta_data' );
        add_filter('manage_'.self::POST_TYPE.'_posts_columns', __CLASS__.'::pips_admin_columns', 10, 1 );
    }


    /**
     * Add meta boxes to custom post edit page
     */
    public static function add_meta_boxes($post_type){
        if ( $post_type==self::POST_TYPE ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                add_meta_box(
                    $html_id=self::pips_prefix($field['name']),
                    $title=$field['title'],
                    $display_callback=Array(__CLASS__,'pips_display_meta_box'),
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


    /**
     * Display the meta data in the custom post edit page
     */
    public static function pips_display_meta_box( $post, $args){
        $field = $args['args'][0];
        $id = self::pips_prefix($field['name']);
        $value = esc_attr(get_post_meta( $post->ID, $id, true ));
        echo "&nbsp;<input type='".$field['type']."' id='$id' name='$id' value='$value' size='50' />";
        if ( $field['required'] && $value == '' ){
            echo "<p style='background-color:pink;padding:0.5rem;'>This value is required.</p>";
        }
    }


    public static function pips_save_meta_data( $post_id ) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
            return;
        }
        $post_type=get_post_type($post_id);
        if ( $post_type==self::POST_TYPE ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                $id = self::pips_prefix($field['name']);
                // echo "<p>id = $id</p>";
                if ( array_key_exists( $id, $_POST ) ){
                    $data = self::pips_sanitise_field($field['validation'], $_POST[$id]);
                    // echo "<p>Sanitised data = $data</p>";
                    update_post_meta( $post_id, $id, $data );
                }
            }
        }
        // die();
    }


    public static function pips_sanitise_field($validation,$value){
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


    /**
     * Define the admin columns displayed in the custom post type list view
     * by setting an associative array of “column name” ⇒ “label”. The “column name” 
     * is passed to callback functions to identify the column. The “label” is shown 
     * as the column header.
     */
    public static function pips_admin_columns($columns){
        // Unset date column and add back to move to last column of list
        unset($columns['date']);
        foreach( self::CUSTOM_FIELDS as $field ){
            $columns[self::pips_prefix($field['name'])] = $field['title'];
        }
        $columns['date'] = 'Date';
        return $columns;
    }


    /**
     * Add prefix for custom fields
     */
    public static function pips_prefix($field_name){
        return self::POST_PREFIX."_$field_name";
    }

}