<?php

declare(strict_types=1);

class Subscriber {

    private const POST_TYPE = 'subscriber'; // Registered as 'subscribers', i.e. plural

    private const CUSTOM_FIELDS = array(
        array("name"=>"first_name", "title"=>"First name", "type"=>"text"),
        array("name"=>"last_name", "title"=>"Last name", "type"=>"text"),
        array("name"=>"email", "title"=>"Email", "type"=>"email"),
        array("name"=>"validation_key", "title"=>"Validation key", "type"=>"text"),
    );

    public static function register_custom(){
        register_post_type(self::POST_TYPE.'s', array(
            'label' => ucfirst(self::POST_TYPE).'s',
            'singular_label' => ucfirst(self::POST_TYPE),
            'public' => true,
            'show_ui' => true, // UI in admin panel
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array("slug" => self::POST_TYPE), // Permalinks format
            'supports' => array('title', 'editor')
        ));
        add_action('add_meta_boxes', 'Subscriber::add_meta_boxes', 10, 1 );
        add_action('save_post', 'Subscriber::save_meta_data' );
        add_filter('manage_'.self::POST_TYPE.'s_posts_columns', 'Subscriber::admin_columns', 10, 1 );
        add_filter('manage_posts_custom_column',  'Subscriber::admin_column', 10, 2);
    }

    public static function add_meta_boxes($post_type){
        if ( $post_type==self::POST_TYPE.'s' ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                add_meta_box(
                    $html_id=self::POST_TYPE.'_'.$field['name'],
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
        $id = self::POST_TYPE.'_'.$field['name'];
        $value = esc_attr(get_post_meta( $post->ID, $id, true ));
        echo "<label for='$id'>".$field['title']."</label>";
        echo "&nbsp;<input type='".$field['type']."' id='$id' name='$id' value='$value' size='50' />";
    }

    public static function save_meta_data( $post_id ) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
            return;
        }
        $post_type=get_post_type($post_id);
        if ( $post_type==self::POST_TYPE.'s' ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                $id = self::POST_TYPE.'_'.$field['name'];
                if ( array_key_exists( $id, $_POST ) ){
                    if ( $field['type'] == 'email' ){
                        $data = sanitize_email( $_POST[$id] );
                    } else {
                        $data = sanitize_text_field( $_POST[$id] );
                    }
                    update_post_meta( $post_id, $id, $data );
                }
            }
        }
    }
    
    public static function admin_columns($columns){
        unset($columns['title']);
        unset($columns['date']);
        foreach( self::CUSTOM_FIELDS as $field ){
            $columns[self::POST_TYPE.'_'.$field['name']] = $field['title'];
        }
        $columns['date'] = 'Date';
        return $columns;
    }

    public static function admin_column($column_id, $post_id){
        echo get_post_meta( $post_id, $column_id, $single=true);
    }
}