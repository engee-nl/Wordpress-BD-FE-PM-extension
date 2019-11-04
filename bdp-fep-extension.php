<?php
/**
* Plugin Name: BD & FE PM extension
* Plugin URI: 
* Description: Extending and customizing Business Directory plugin and Front End PM plugin.
* Version: 1.0
* Author: Zen
* Author URI: 
**/

// Require WPS_Extend_Plugin class
require_once( 'WPS_Extend_Plugin.php' );
require_once( 'functions.php' );

// Extend Business Directory Plugin
new WPS_Extend_Plugin( 'business-directory-plugin/business-directory-plugin.php', __FILE__, '5.0.0', 'my-plugin-text-domain' );
new WPS_Extend_Plugin( 'front-end-pm/front-end-pm.php', __FILE__, '11.0.0', 'my-plugin-text-domain' );

define("OPTION_NAME_ROLELEVEL", 'BDPFEP_user_role_level');

// Admin panel
add_action( 'restrict_manage_posts', 'BDPFEP_add_roles_filter' );
add_action( 'admin_init', 'BDPFEP_handle_actions' );

// Admin panel - change user role in directory listing / edit listing
add_action( 'wp_ajax_bdpfep_changerole', 'BDPFEP_changerole' );
add_action( 'wp_ajax_nopriv_bdpfep_changerole', 'BDPFEP_changerole_noadmin' );

// Admin panel - add connect input / checkbox 
add_action( 'wp_ajax_bdpfep_connectinput', 'BDPFEP_connectinput' );
add_action( 'wp_ajax_nopriv_bdpfep_connectinput', 'BDPFEP_connectinput_noadmin' );

// Admin panel - add sub page under Users - Managing user roles level
add_action('admin_menu', 'BDPFEP_add_custom_user_submenu');

// Fep add fields in search
add_action( 'wp_ajax_bdpfep_get_dir_search_fields', 'BDPFEP_bdpfep_get_dir_search_fields' );
add_action( 'wp_ajax_nopriv_bdpfep_get_dir_search_fields', 'BDPFEP_bdpfep_get_dir_search_fields_noadmin' );

// Business directory listing add start chat button
add_action( 'wpbdp_user_can', 'BDPFEP_extend_listing', 10 , 4 );

add_action( 'wp_enqueue_scripts', 'so_enqueue_scripts' );
add_action( 'admin_enqueue_scripts', 'so_enqueue_scripts' );

function so_enqueue_scripts(){
    wp_register_script( 'ajaxHandle', WP_PLUGIN_URL  . '/bdp-fep-extension/assets/js/BDPFEP_script.js', array(), false, true );
    wp_enqueue_style( 'customstyles', WP_PLUGIN_URL . '/bdp-fep-extension/assets/css/BDPFEP_style.css' );
    wp_enqueue_script( 'ajaxHandle' );
    wp_localize_script( 'ajaxHandle', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

if( isset($_SERVER['REQUEST_URI']) ){
    if(strpos($_SERVER['REQUEST_URI'], 'edit.php') != false || strpos($_SERVER['REQUEST_URI'], 'post.php') != false){
        add_action( 'admin_init', 'change_role_meta_boxes' );
    }
}

// Fep query users
add_filter( 'fep_directory_arguments', 'BDPFEP_fep_directory_arguments', 1 , 1 );

// Fep add column in search
add_filter( 'fep_directory_table_columns', 'BDPFEP_fep_directory_table_columns', 10 , 1 );
add_filter( 'fep_directory_table_column_content', 'BDPFEP_fep_directory_table_column_content', 1 , 2 );