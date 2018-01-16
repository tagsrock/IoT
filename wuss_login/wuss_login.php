<?php
/*
Plugin Name: WUSS Login
Plugin URI: http://wuss.mybadstudios.com/
Description: A plugin that allows you to use Wordpress's user database as your Unity login
Version: 3.3
Network: true
Author: myBad Studios
Author URI: http://www.mybadstudios.com
*/

session_start();

//if these values don't exist, create them. If they do, then skip creating them
add_option("wuss_table_prefix", "wuss_");
add_option("wuss_meta_table", "wuss_meta");

include_once('posttypes/wuss_post_types.php');
include_once('posttypes/game_post_type.php');
include_once('wuss_web_functions.php');
include_once('Settings/wuss_framework.php');
include_once('Settings/WussMenuItem.class.php');
include_once('Settings/widgets.php');

if (is_multisite()) {
	add_action( 'network_admin_menu', 'register_wuss_framework', 5 );
}
else {
	add_action( 'admin_menu', 'register_wuss_framework', 5 );
}

add_action( 'admin_enqueue_scripts', 'enqueue_wuss_styles' );
add_action( 'wp_enqueue_scripts', 'enqueue_wuss_web_styles' );

function register_wuss_framework()
{
	add_menu_page("WORDPRESS UNITY SOCIAL SYSTEM", "WUSS", "manage_wuss", "wuss_framework", "show_wuss_framework",'dashicons-smiley');
	do_action('register_subsystems',12);
}

function enqueue_wuss_web_styles()
{
	wp_register_style ( 'wuss_web_stylesheet', plugins_url('wuss_login/style.css'));
	wp_enqueue_style ( 'wuss_web_stylesheet' );
}

function enqueue_wuss_styles()
{
	wp_register_style ( 'wuss_stylesheet', plugins_url('wuss_login/Settings/style.css'));
//	wp_register_script( 'wuss_game_functions', plugins_url( 'js/wuss_games.js', __FILE__ ) );
	wp_register_script( 'wuss_medialoader', plugins_url( 'js/wuss_medialoader.js', __FILE__ ) );

	wp_enqueue_media();
	wp_enqueue_script( 'custom-header' );
	wp_enqueue_style ( 'wuss_stylesheet' );
//	wp_enqueue_script( 'wuss_game_functions' );
	wp_enqueue_script( 'wuss_medialoader' );
}

function activate_wuss()
{
	$role = get_role( 'administrator' );
	$role->add_cap( 'manage_wuss' );

	//store some information outside of well known WP tables. Just as a precaution
	$query = 'CREATE TABLE IF NOT EXISTS `'.get_option('wuss_meta_table').'` (
  `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `gid` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `meta_key` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY  (meta_id, gid, meta_key)
  ) ENGINE=InnoDB DEFAULT CHARSET=latin1;';

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($query);
}

function deactivate_wuss(){
	$role = get_role( 'administrator' );
	$role->remove_cap( 'manage_wuss' );
}

function uninstall_wuss(){
	global $wpdb;
	$table_name = get_option('wuss_table_prefix') . 'meta';
	$sql = "DROP TABLE IF EXISTS " . get_option('wuss_meta_table');

	//Uncomment if you want to uninstall the table
	//$wpdb->query($sql);
}

register_activation_hook( __FILE__,	'activate_wuss'	);
register_deactivation_hook( __FILE__,	'deactivate_wuss'	);
register_uninstall_hook( __FILE__,	'uninstall_wuss'	);

