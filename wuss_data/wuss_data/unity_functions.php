<?php

	//load this first as this makes sure the WP themes are not processed
	include_once(dirname(__FILE__) . "/../wuss_login/settings.php");

function dataLoadClasses()
{
	wuss_load_class('dDataContainer');
	wuss_load_class('dData');
}

//Fetch only the value of a specific piece of data
function dataFetchSharedField() {
    global $current_user, $wpdb;

    $data = new dData(Postedi("gid"), 0, Posted("cat") );
    $data->fetch_field( Posted("fid") );
    $data->ReturnResults();
}

//Fetch all data stored under a specific category for a specific game
function dataFetchSharedCategory() {
    global $current_user, $wpdb;

    $data = new dData(Postedi("gid"), 0, Posted("cat") );
    $data->fetch_cat();
    $data->ReturnResults();
}

//Fetch the data of all categories for a specific game
function dataFetchAllSharedInfo() {
    global $current_user, $wpdb;

    $data = new dData(Postedi("gid"), 0, Posted("cat") );
    $data->fetch_game();
    $data->ReturnResults(true);
}

//Remove a specific piece of data
function dataRemoveSharedField() {
    global $current_user, $wpdb;

    $data = new dData(Postedi("gid"), 0, Posted("cat") );
    $data->remove_field( Posted("fid") );
}

//Fetch all data stored under a specific category for a specific game
function dataRemoveSharedCategory() {
    global $current_user, $wpdb;

    $data = new dData(Postedi("gid"), 0, Posted("cat") );
    $data->remove_cat();
}

//All data to be saved must be sent over per category.
//Fetch each field and it's value and store it into the database
function dataSaveSharedData() {
    global $current_user, $wpdb;

    //remove the operational stuff from the post data leaving behind only the fields we want to save
    $gid = Postedi("gid");
    $cat = Posted ("cat");

    unset($_REQUEST["gid"]);
    unset($_REQUEST["cat"]);
    unset($_REQUEST["id"]);
    unset($_REQUEST["wuss"]);
    unset($_REQUEST["action"]);
    unset($_REQUEST["unity"]);

    if (count($_REQUEST) == 0)
    {
	$result = SendNode("DATA") . 
        PrintError("No data to save");
	SendToUnity($result);
        return;
    }

    $data = new dDataContainer($gid, $cat);

    foreach ($_REQUEST as $key => $value)
    {
        $data->AddField( sanitize_text_field( strip_tags($key) ), sanitize_text_field( strip_tags( $value ) ) );
    }

    $data->commit_fields(0, wuss_prefix . "data");
    SendToUnity( SendNode("DATA","success=true") );
}

	//Fetch only the value of a specific piece of data
	function dataFetchField() {
		global $current_user, $wpdb;
		
		$data = new dData(Postedi("gid"), $current_user->ID, Posted("cat") );	
		$data->fetch_field( Posted("fid") );
		$data->ReturnResults();
	}
	
	//Fetch all data stored under a specific category for a specific game
	function dataFetchCategory() {
		global $current_user, $wpdb;
		
		$data = new dData(Postedi("gid"), $current_user->ID, Posted("cat") );	
		$data->fetch_cat();
		$data->ReturnResults();
	}
	
	//Fetch the data of all categories for a specific game
	function dataFetchGameInfo() {
		global $current_user, $wpdb;
		
		$data = new dData(Postedi("gid"), $current_user->ID, Posted("cat") );	
		$data->fetch_game();
		$data->ReturnResults(true);
	}
	
	//Fetch fetch all info related to the player but do NOT include data stored
	//for games. Does not include Wordpress standard info, only data the developer stored
	function dataFetchGlobalInfo() {
		global $current_user, $wpdb;
		
		$data = new dData(Postedi("gid"), $current_user->ID, Posted("cat") );	
		$data->fetch_global_info();
		$data->ReturnResults(true);
	}
	
	//Fetch absolutely everything related to the player:
	//1. Data not related to any game as well as
	//2. ...all data from all categories
	//3. ...for all games
	function dataFetchEverything() {
		global $current_user, $wpdb;
		
		$data = new dData(Postedi("gid"), $current_user->ID, Posted("cat") );	
		$data->fetch_all_user_data();
		$data->ReturnResults(true);
	}
	
	//Remove a specific piece of data
	function dataRemoveField() {
		global $current_user, $wpdb;
		
		$data = new dData(Postedi("gid"), $current_user->ID, Posted("cat") );	
		$data->remove_field( Posted("fid") );
	}
	
	//Fetch all data stored under a specific category for a specific game
	function dataRemoveCategory() {
		global $current_user, $wpdb;
		
		$data = new dData(Postedi("gid"), $current_user->ID, Posted("cat") );	
		$data->remove_cat();
	}
	
	//Fetch the data of all categories for a specific game
	function dataRemoveGameInfo() {
		global $current_user, $wpdb;
		
		$data = new dData(Postedi("gid"), $current_user->ID, Posted("cat") );	
		$data->remove_game();
	}
	
	//All data to be saved must be sent over per category.
	//Fetch each field and it's value and store it into the database
	function dataSaveData() {
		global $current_user, $wpdb;
		
		//remove the operational stuff from the post data leaving behind only the fields we want to save
		$gid = Postedi("gid");
		$cat = Posted ("cat");

		unset($_REQUEST["gid"]);
		unset($_REQUEST["cat"]);
		unset($_REQUEST["id"]);
		unset($_REQUEST["wuss"]);
		unset($_REQUEST["action"]);
		unset($_REQUEST["unity"]);
	
		if (count($_REQUEST) == 0)
		{
			$result = PrintError("No data to save");
			SendToUnity($result);
			return;
		}
		
		$data = new dDataContainer($gid, $cat);

		foreach ($_REQUEST as $key => $value)
		{
			$data->AddField( sanitize_text_field( strip_tags($key) ), sanitize_text_field( strip_tags( $value ) ) );
		}
 
		$data->commit_fields($current_user->ID, wuss_prefix . "data");
		SendToUnity( SendField("success", "true") );
	}
	
