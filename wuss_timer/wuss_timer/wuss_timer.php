<?php

session_start();

/*
Plugin Name: WUSS Time Delay Framework
Plugin URI: http://wuss.mybadstudios.com/
Description: A plugin that allows you to specify how much time should pass between increments before stats are allocated extra points.
Version: 3.0
Network: true
Author: myBad Studios
Author URI: http://www.mybadstudios.com
*/

function activate_wutimer()
{
	include_once(dirname(__FILE__) . "/../wuss_login/functions.php");

	$timer_table	= "CREATE TABLE IF NOT EXISTS "
					. wuss_prefix  
					."timers (
						  uid integer unsigned NOT NULL DEFAULT '0',
						  gid integer unsigned NOT NULL DEFAULT '0',
						  fid varchar(16) NOT NULL DEFAULT '0',
						  timermax INT UNSIGNED NOT NULL DEFAULT 60,
						  timer INT UNSIGNED NOT NULL DEFAULT 60,
						  points INT UNSIGNED NOT NULL DEFAULT 1,
						  pointsmax INT UNSIGNED NOT NULL DEFAULT 1,
						  PRIMARY KEY  (uid,gid,fid)
						);";
						
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($timer_table);
}

function deactivate_wutimer(){
	//currently don't have anything to do here...
}

function uninstall_wutimer() {
	include_once(dirname(__FILE__) . "/../wuss_login/functions.php");
   	//delete the table created by this kit...
	global $wpdb;
    	$query = "DROP TABLE ". wuss_table_prefix()  ."timers;";
	//Uncomment if you want to remove all stored timers when you uninstall the kit
	//$wpdb->query($query);
}

register_activation_hook( __FILE__,	'activate_wutimer'	);
register_deactivation_hook( __FILE__,	'deactivate_wutimer'	);
register_uninstall_hook( __FILE__,	'uninstall_wutimer'	);

include_once(dirname(__FILE__) ."/settings.php");
