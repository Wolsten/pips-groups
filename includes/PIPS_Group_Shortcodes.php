<?php

declare(strict_types=1);


add_shortcode('pips_groups', 'PIPS_Group_ShortCodes::groups');



class PIPS_Group_ShortCodes {

    public static function groups( $atts ){

        $type = PIPS_group::POST_TYPE;

        // Parse shortcode attributes (optional; set default values)
        $atts = shortcode_atts(
            array(
                'posts' => -1, // Default number of posts to display,
                'format' => 'grid' // or 'list'
            ),
            $atts,
            'pips_group_summary' // Shortcode name
        );

        // Create a custom query for the "partner" post type
        $args = array(
            'post_type'   => $type,  // Custom post type
            'numberposts' => intval( $atts['posts'] ), // Number of posts to retrieve
            'post_status' => array('publish')
        );

        $posts = get_posts( $args );

        $html = "<div  class='group-container'>";

        if ( count($posts) === 0 ){
            return $html . "No groups found</div>";
        }

        if ( $atts['format'] === 'list' ){
            $html .= self::list($posts);
        } else {
            $html .= self::grid($posts);
        }

        return $html . "</div>";
    }


    private static function grid($posts){

        $html = "<div class='archive-grid'>";

        foreach( $posts as $post ){

            $permalink = get_permalink($post->ID);
            $title = $post->post_title;
            $image = "";
            $image_url = get_stylesheet_directory_uri() . '/screenshot.jpg';
            if ( has_post_thumbnail($post->ID) ) {
                $image_url = get_the_post_thumbnail_url( $post->ID, 'thumbnail');
            }
            if ( $image_url != "" ){
                $image = "<img src='$image_url'>";
            }
            $excerpt = get_the_excerpt($post->ID);
            $field = PIPS_group::pips_prefix('location');
            $location = esc_attr(get_post_meta( $post->ID, $field, true ));

            $html .= "<article>

                <a href='$permalink'>
                    
                    $image
                    
                    <header>
                        <h2 class='title'>$title</h2>
                        <h3 class='sub-title'>$location</h3>
                    </header>

                    <div class='body'>
                        <p>$excerpt</p>
                    </div>
                </a>

            </article>";

        }

        $html .= "</div>";

        return $html;
    }

    private static function list($posts){

        $html = "<ul class='list-container'>";

        foreach( $posts as $post ){

            $permalink = get_permalink($post->ID);
            $title = $post->post_title;
            $image = "";
            $image_url = get_stylesheet_directory_uri() . '/screenshot.jpg';
            if ( has_post_thumbnail($post->ID) ) {
                $image_url = get_the_post_thumbnail_url( $post->ID, 'thumbnail');
            }
            if ( $image_url != "" ){
                $image = "<img class='image' src='$image_url'>";
            }
            $excerpt = get_the_excerpt($post->ID);
            $field = PIPS_group::pips_prefix('location');
            $location = esc_attr(get_post_meta( $post->ID, $field, true ));

            $html .= "<li class='list-item'>

                <a href='$permalink' title='$excerpt'>
                    <h2 class='title'>$title</h2>
                    <h3 class='sub-title'>$location</h3>
                </a>

            </li>";

        }

        $html .= "</ul>";

        return $html;
    }

}