<?php

session_start();

/*
Plugin Name: WUSS Data
Plugin URI: http://wuss.mybadstudios.com/
Description: A plugin that allows you to store and retrieve any piece of info you want wether it be game specific or global
Version: 3.0
Network: true
Author: myBad Studios
Author URI: http://www.mybadstudios.com
*/

function activate_wudata()
{
	include_once(dirname(__FILE__) . "/../wuss_login/functions.php");

	$data_table	=	"CREATE TABLE IF NOT EXISTS "
					. wuss_table_prefix() 
					."data (
						  uid integer unsigned NOT NULL DEFAULT 0,
						  gid integer UNSIGNED NOT NULL DEFAULT 0,
						  cat varchar(32) NOT NULL DEFAULT '',
						  fid varchar(128) NOT NULL DEFAULT '',
						  fval text NOT NULL DEFAULT '',
						  PRIMARY KEY  (uid,gid,cat,fid)
						);";
						
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($data_table);
}

function deactivate_wudata(){
	//currently don't have anything to do here...
}

function uninstall_wudata() {
	include_once(dirname(__FILE__) . "/../wuss_login/functions.php");
   	//delete the table created by this kit...
	global $wpdb;
    $query = "DROP TABLE ". wuss_table_prefix() ."data;";

//uncomment this if you want to destroy your tables and data upon uninstall
//	$wpdb->query($query);
}

register_activation_hook( __FILE__,	'activate_wudata'	);
register_deactivation_hook( __FILE__,	'deactivate_wudata'	);
register_uninstall_hook( __FILE__,	'uninstall_wudata'	);

include_once(dirname(__FILE__) ."/settings.php");
