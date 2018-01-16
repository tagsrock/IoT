<?php

session_start();

/*
Plugin Name: WUSS Scoring
Plugin URI: http://wuss.mybadstudios.com/
Description: A plugin that allows you to use Wordpress's database to store the high scores of all your Unity games.
Version: 3.1
Author: myBad Studios
Network: true
Author URI: http://www.mybadstudios.com
*/

include_once(dirname(__FILE__) ."/settings.php");
include_once(dirname(__FILE__) ."/classes/WussHighscores.class.php");
include_once(dirname(__FILE__) ."/scoring_web_functions.php");
include_once('Settings/widgets.php');

add_action( 'wp_enqueue_scripts', 'enqueue_wuss_scoring_styles' );

function enqueue_wuss_scoring_styles()
{
	wp_register_style ( 'wuss_scoring_stylesheet', plugins_url('wuss_scoring/style.css'));
	wp_enqueue_style ( 'wuss_scoring_stylesheet' );
}
