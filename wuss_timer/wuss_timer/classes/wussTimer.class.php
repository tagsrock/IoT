<?php
class wussTimer {
	var $uid = 0,
		$gid = 0,
		$fid = 0,
		$table = "",
		$points = 0,
		$pointsmax = 0,
		$timermax = 0,
		$timer = 0;
		
	public function __construct($gid, $uid, $fid, $maxtime=60, $maxvalue=1, $value=1)
	{
		$this->fid	= $fid;
		$this->gid	= $gid;
		$this->uid	= $uid;
		$this->points = $maxvalue;
		$this->pointsmax = $value;
		$this->timermax = $maxtime;
		$this->timer = time();
			
	   	$this->table = wuss_prefix . "timers";

		$this->fetch_details($maxtime, $maxvalue, $value, true);
	}
	
	function fetch_details($maxtime=60, $maxvalue=1, $value=1, $create = false)
	{
		global $wpdb;
		
		$query = "SELECT * FROM $this->table WHERE uid = '$this->uid' AND gid = '$this->gid' AND fid = '$this->fid'";
	
		$result = $wpdb->get_row($query);
		if (null != $result)
		{
			$this->timer = $result->timer;
			$this->timermax = $result->timermax;
			$this->points = $result->points;
			$this->pointsmax = $result->pointsmax;
		} else
		{
			if ($create)
				$this->create_record($maxtime, $maxvalue, $value);
			else return false;
		}
		
		return true;
	}
	
	//determine if enough time has passed since the last check for more points
	//to be owed. If so, add those points before doing anything else...
	//also, update the timers accordingly
	function check_timer_value_update()
	{
		if ($this->points >= $this->pointsmax)
			return;
			
		$lapsed_time = time() - $this->timer;
		if ($lapsed_time >= $this->timermax)
		{
			$remainder = $lapsed_time % $this->timermax;
			$points = (int)(($lapsed_time - $remainder) / $this->timermax);
			$this->points += $points;
			
			if ($this->points >= $this->pointsmax)
			{
				$this->points = $this->pointsmax;
				$this->timer = time();
			} else
			{
				$this->timer = time() - $remainder;
			}
		}
	}
	
	//if the stat is not already maxed out, add some value to it
	function add_points($amt)
	{
		$this->check_timer_value_update();
		$this->points += $amt;
		if ($this->points >= $this->pointsmax)
		{
			$this->points = $this->pointsmax;
			$this->timer = time();			
		}
	}
	
	//decrease the stat. If $force is true, larger values will force the stat to 0
	//if $force is false, values greater than the available points will return false
	//instead of modifying the stat
	function use_points($amt, $force = false)
	{
		$this->check_timer_value_update();
		
		if ($amt < 0)
			return;
		
		if (!$this->has_points($amt) && !$force)
			return false;
			
		if ($this->points == $this->pointsmax)
			$this->timer = time();
			
		if ($amt > $this->points)
			$amt = $this->points;
		
		$this->points -= $amt;
	
		return true;
	}
	
	function update_max_points($amt)
	{
		$this->set_max_points($this->pointsmax + $amt);
	}
	
	function set_max_points($value)
	{
		if ($value < 0) $value = 0;
		if ($this->points > $value) $this->points = $value;
		$this->pointsmax = $value;
	}
	
	
	//I can't see any practical use for this but it is here in case you need it.
	function update_max_timer($amt)
	{
		$this->set_max_timer($this->timermax + $amt);
	}
	
	function set_max_timer($value)
	{
		if ($value < 0) $value = 0;
		$this->timermax = $value;
	}
	
	//test if the stat has a certain amount of points
	function has_points($amt)
	{
		return ($this->points >= $amt);
	}
	
	//called by fetch_details, if the specified stat does not exist, it is created via this function
	function create_record($maxtime, $maxvalue, $value = -1)
	{
		global $wpdb;
		
		//if the last parameter is left out, set the stat to full value
		if ($value < 0)
			$value = $maxvalue;
			
		$query = "INSERT INTO $this->table (uid, gid, fid, timermax, timer, points, pointsmax) VALUES ('$this->uid','$this->gid','$this->fid','$this->timermax','".time()."','$this->points','$this->pointsmax')";
		$wpdb->query($query);
	
		$this->timer = time();
		$this->maxtime = $maxtime;
		$this->value = $value;
		$this->maxvalue = $maxvalue;
	}

	function delete_record()
	{
		global $wpdb;
		$query = "DELETE FROM $this->table WHERE uid='$this->uid' AND gid='$this->gid' AND fid='$this->fid'";
		return $wpdb->query($query);
	}
	
	function commit()
	{
		global $wpdb;
		
		$query = "UPDATE $this->table SET timer='$this->timer', timermax='$this->timermax', points='$this->points', pointsmax='$this->pointsmax'
				  WHERE uid = '$this->uid' AND gid = '$this->gid' AND fid = '$this->fid'";
		$wpdb->query($query);
	}
	
	function ToCML($complete = false)
	{
		if ($this->points < $this->pointsmax)
		{
			$lapsed_time = time() - $this->timer;
			$time_till_update = $this->timermax - $lapsed_time;
		} else
		{
			$time_till_update = 0;
		}
		return "fid=$this->fid;p=$this->points;t=$time_till_update" . ($complete ? ";px=$this->pointsmax;tx=$this->timermax" : "");
	}
}
