<?php

	//load this first as this makes sure the WP themes are not processed
	include_once(dirname(__FILE__) . "/../wuss_login/settings.php");

	function timerLoadClasses()
	{
		wuss_load_class('wussTimer');
	}
	
	//$action 1
	//Fetch all stats returns existing stats only so does not create any new ones
	function timerFetchAllStats() {
		global $current_user, $wpdb;
		
	   	$table = wuss_prefix . "timers";

		$gid = Postedi("gid");
		$query = "SELECT fid FROM $table WHERE gid='$gid' AND uid='$current_user->ID'";
		$results = $wpdb->get_results($query);
		
		if (null == $results || $results->num_rows == 0)
		{
			SendToUnity(PrintError("No results found according to specifications"));
			return;
		}
		
		$response = SendField("succss", "true");
		foreach($results as $entry)
		{
			$timer = new wussTimer($gid, $current_user->ID, $entry);
			$timer->check_timer_value_update();
			$response .= SendNode("timer", $timer->ToCML(true) );
		}
		SendToUnity($response);
	}
	
	//$action 2
	//Fetch a single stat and create it if not found. Return all info on the stat
	function timerFetchStat()
	{
		global $current_user;
		
		$gid = Postedi("gid");
		$fid = Posted ("fid");
		
		$maxtime	= Postedi("tx");
		$maxvalue	= Postedi("vx");
		$value		= Postedi("v");
		
		$timer = new wussTimer($gid, $current_user->ID, $fid, $maxtime, $maxvalue, $value);
		$timer->check_timer_value_update();
		$response  = SendField("success", "true");
		$response .= SendNode("timer",$timer->ToCML(true));
		SendToUnity($response);
	}
	
	//$action 3
	//Fetch a single stat and create it if not found. Return only a portion of the details
	function timerGetStatStatus()
	{
		global $current_user;
		
		$gid = Postedi("gid");
		$fid = Posted ("fid");
		
		if (Postedi("x") == 0)
		{
			$maxtime	= Postedi("tx");
			$maxvalue	= Postedi("vx");
			$value		= Postedi("v");
			$timer = new wussTimer($gid, $current_user->ID, $fid, $maxtime, $maxvalue, $value);
		} else
		{
			$timer = new wussTimer($gid, $current_user->ID, $fid);
		}
		$timer->check_timer_value_update();
		
		$response  = SendField("success", "true");
		$response .= SendNode("timer",$timer->ToCML());
		SendToUnity($response);
	}
	
	//$action 4
	//This function assumes the stat does exist so if it doesn't it wil be created with default values
	function timerSpendPoints()
	{
		global $current_user;
		
		$gid	= Postedi("gid");
		$fid	= Posted ("fid");
		$amount	= Postedi("amt");
		$force	= Posted("force");
		$force_bool = !( strtolower($force) == "false" || $force == "0");
		
		$timer = new wussTimer($gid, $current_user->ID, $fid, $maxtime, $maxvalue, $value);
		$result = $timer->use_points($amount, $force_bool);
		
		if ($result)
		{
			$timer->commit();
			$response  = SendField("success", "true");
			$response .= SendNode("timer",$timer->ToCML());
		} else
		{
			$response  = SendNode("timer",$timer->ToCML());
			$response .= PrintError("Insufficient \"$fid\" value");
		}
		SendToUnity($response);
	}

	//$action 5
	//This function assumes the stat does exist so if it doesn't it will be created with default values
	function timerGetPoints()
	{
		global $current_user;
		
		$gid	= Postedi("gid");
		$fid	= Posted ("fid");
		$amount	= Postedi("amt");
		
		$timer = new wussTimer($gid, $current_user->ID, $fid);
		$timer->add_points($amount);
		$timer->commit();
		
		$response  = SendField("success", "true");
		$response .= SendNode("timer",$timer->ToCML());
		SendToUnity($response);
	}
	
	function timerUpdateMaxPoints()
	{
		global $current_user;
		
		$gid	= Postedi("gid");
		$fid	= Posted ("fid");
		$amount	= Postedi("amt");
		
		$timer = new wussTimer($gid, $current_user->ID, $fid);
		$timer->update_max_points($amount);
		$timer->commit();
		
		$timer->check_timer_value_update();
		$response  = SendField("success", "true");
		$response .= SendNode("timer",$timer->ToCML(true));
		SendToUnity($response);
	}
	
	
	function timerUpdateMaxTimer()
	{
		global $current_user;
		
		$gid	= Postedi("gid");
		$fid	= Posted ("fid");
		$amount	= Postedi("amt");
		
		$timer = new wussTimer($gid, $current_user->ID, $fid);
		$timer->update_max_timer($amount);
		$timer->commit();
		
		$timer->check_timer_value_update();
		$response  = SendField("success", "true");
		$response .= SendNode("timer",$timer->ToCML(true));
		SendToUnity($response);
	}
	
	
	function timerSetMaxPoints()
	{
		global $current_user;
		
		$gid	= Postedi("gid");
		$fid	= Posted ("fid");
		$amount	= Postedi("amt");
		if ($amount < 0) $amount = 0;
		
		$timer = new wussTimer($gid, $current_user->ID, $fid);
		$timer->set_max_points($amount);
		$timer->commit();
		
		$timer->check_timer_value_update();
		$response  = SendField("success", "true");
		$response .= SendNode("timer",$timer->ToCML(true));
		SendToUnity($response);
	}
	
	
	function timerSetMaxTimer()
	{
		global $current_user;
		
		$gid	= Postedi("gid");
		$fid	= Posted ("fid");
		$amount	= Postedi("amt");
		if ($amount < 1) $amount = 1;
		
		$timer = new wussTimer($gid, $current_user->ID, $fid);
		$timer->set_max_points($amount);
		$timer->commit();
		
		$timer->check_timer_value_update();
		$response  = SendField("success", "true");
		$response .= SendNode("timer",$timer->ToCML(true));
		SendToUnity($response);
	}

	function timerDeleteTimer()
	{
		global $current_user;

		$gid	= Postedi("gid");
		$fid	= Posted ("fid");
		$response = "";

		$timer = new wussTimer($gid, $current_user->ID, $fid);
		$result = $timer->delete_record();
		if ($result === false)
		{
			$response .= PrintError("Unable to delete timer");
		}
		else {
			$response .= SendNode("timer","fid={$fid};p=0;t=0;px=0;tx=0");
		}
		SendToUnity($response);
	}
