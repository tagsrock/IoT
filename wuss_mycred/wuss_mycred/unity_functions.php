<?php

//load this first as this makes sure the WP themes are not processed
include_once(dirname(__FILE__) . "/../wuss_login/settings.php");

if (!file_exists("../mycred/index.php") ):
    PrintError("myCreds plugin not found!");
    return;
endif;

include_once("../mycred/includes/mycred-functions.php");

function mycredFetchTypes()
{
    global $current_user;

    $uid = Postedi("uid");
    if ($uid == 0 && is_user_logged_in() )
        $uid =  $current_user->ID;

    if ($uid == 0)
    {
        SendToUnity(PrintError("No user specified"));
        return;
    }

    $credits = new myCRED_Settings();
    $types = mycred_get_types();
    $typenames = array();
    $index = 0;
    $runner = 0;

    $result = SendField("uid", $uid);
    foreach($types as $key => $val)
    {
        $balance = $credits->get_users_balance($uid, $key);
        $result .= SendNode("TYPE", "meta=$key;type=$val;bal=$balance");
    }
    SendToUnity($result);
}

//$action 2
function mycredUpdate() {
    global $current_user, $wpdb;

    $ref = Posted("ref");
    $t	 = Posted("type");
    if (empty($t))
        $t = 'mycred_default';

    $uid = Postedi("uid");
    if ($uid == 0)
        $uid = $current_user->ID;

    if ($uid == 0 || empty($ref) ) :
	SendToUnity('');
    else:
        $amount = Postedf("amt");
        if ($amount == 0) :
            SendToUnity( SendField("message","Value left unchanged") );
        else:
            $credits = new myCRED_Settings();

            $credits->add_creds($ref, $uid, $amount, '','','', $t );
            $balance = $credits->get_users_balance($uid, $t);
	    $result  = SendField("success","true");
	    $result .= SendField("creds",$balance);
	    $result .= SendField("meta", $t);
	    SendToUnity($result);
        endif;
    endif;
}
