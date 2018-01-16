<?php
function cors() {
 
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
 
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
 
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");        
 
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
	
	exit(0);
    }
}
 
//cors();

if (empty($_POST)) 
{ 
	$rawPost = file_get_contents('php://input'); 
	$_POST = array();
	mb_parse_str($rawPost, $_POST);
	foreach($_POST as $k => $v)
		$_REQUEST[$k] = $v;
} 

//some web browsers and the VITA used to simply NOT populate the $_POST variable and thus no input was ever received from Unity
//the code immediately above used to fix this issue and for years there was no issue.
// I have received word that the WebGL builds might have reintroduced this error and seem to circumvent the above attempt
// to work around the problem. If your browser is one of those causing a problem then at least I can now tell you about it
//rather than failing the tests below and you receiving no response at all from this script
if (empty($_REQUEST))
{
	SendToUnity( PrintError("No input fields found! Consider changing POST to GET in WPServer", "911") );
	die();
}

//required to use WP. Set to false when using from Unity. Set to true when using in WP.
//In unity we will pass in a "Unity" field so let's test for that now...
$unity = isset($_REQUEST['unity']);

//I force WUSSACTION to 'wuss' if no action was specified to prevent the execution of external code
define('WP_USE_THEMES', !isSet($unity) ? true : false );
define('CALLED_FROM_GAME', WP_USE_THEMES == false ? true : false);
define('WUSSACTION', isSet( $_REQUEST['wuss'] ) ? strtolower( trim( $_REQUEST['wuss'] ) ) : 'wuss');
define('ACTION', WUSSACTION . (isset($_REQUEST['action']) ? $_REQUEST['action'] : '') );
if (!CALLED_FROM_GAME || WUSSACTION == ACTION) die();

//this kit is intended to be installed in wp-content/plugins/wuss_login
//so we need to go back 3 folders to find this file. Adjust as necesarry
require_once( dirname(__FILE__) . '/../../../wp-load.php' );

require_once(dirname(__FILE__) . "/functions.php");

function wuss_load_class($class) {
    include_once( 'classes/'. $class . '.class.php' );
}

//if security is implemented validate it
if ($unity)
{
	$query = "SELECT meta_value FROM " . get_option('wuss_meta_table') ." WHERE meta_key = 'security' AND gid='" . Postedi('gid') . "'";
	$local_token = $wpdb->get_var($query);
	if (!$local_token)
		$local_token = '';

	if (!isset($_REQUEST['token']))
		$token = "";
	else
		$token = trim($_REQUEST['token']);
	if ((empty($local_token) && !empty($token)) ||
	    (!empty($local_token) && empty($token))) {
		define( 'AUTHENTICATED', false );
	} else
	{
		if (!empty($local_token) && !empty($token)) {
			unset($_REQUEST['token']);
			foreach ($_REQUEST as $key)
				$local_token .= $key;
			if (md5($local_token) != $token)
				define( 'AUTHENTICATED', false );
			else
				define( 'AUTHENTICATED', true );
		} else
			define('AUTHENTICATED', true);
	}
} else {
	define( 'AUTHENTICATED', true );
}

if (!AUTHENTICATED)
{
	SendToUnity(PrintError('Invalid credentials'));
} else {
	if ( function_exists( ACTION ) ) {
		$logged_out_functions = array(
			'loginDoLogin',
			'loginSubmitRegistration',
			'loginVerifyLogin',
			'loginPasswordReset'
		);

		if ( in_array( ACTION, $logged_out_functions ) && ! is_user_logged_in() ) {
			add_action( "wp_ajax_nopriv_" . ACTION, ACTION, 1 );
			do_action( 'wp_ajax_nopriv_' . ACTION );
		} else {
			if ( function_exists( WUSSACTION . 'LoadClasses' ) ) {
				add_action( "wp_ajax_" . ACTION, WUSSACTION . 'LoadClasses', 1 );
			}
			add_action( "wp_ajax_" . ACTION, ACTION, 2 );
			do_action( 'wp_ajax_' . ACTION . '' );
		}
	}
}
die();
