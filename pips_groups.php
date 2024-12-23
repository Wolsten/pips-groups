<?php
/**
 * Plugin Name: PiPs Groups
 * Version: 0.0.4
 * Author: Steve Davison
 * Description: Provide simple method to add custom posts for Pips Groups
 */

DEFINE( 'PIPS_GROUPS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

REQUIRE_ONCE (PIPS_GROUPS_PLUGIN_PATH . 'includes/PIPS_group.php');
REQUIRE_ONCE (PIPS_GROUPS_PLUGIN_PATH . 'includes/PIPS_Group_Shortcodes.php');


add_action( 'wp_enqueue_scripts', 'pips_groups_enqueue_style' );

function pips_groups_enqueue_style(){
    $pips_plugin_version = get_file_data(__FILE__, array('Version'), 'plugin');
    if ( is_array($pips_plugin_version)){
        $pips_plugin_version = $pips_plugin_version[0];
    } else {
        $pips_plugin_version = "unknown";
    }
    // echo "<p>Pips plugin version = ".$pips_plugin_version."</p>";
    wp_enqueue_style('PIPS_GROUPS_form', plugins_url("styles.css", __FILE__), ['parent-style'], $pips_plugin_version);
}


add_action('init', 'PIPS_group::pips_init');







?>
