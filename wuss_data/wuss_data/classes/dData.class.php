<?php
/*
	dData function list
	===================
	public function ReturnResults($print_gid = false)

	public function fetch_field($fid)
	public function fetch_cat($cat = "")
	public function fetch_game($gid = -1)
	public function fetch_global_info()
	public function fetch_all_user_data()	
*/

class dData {
	var $uid = 0,
		$gid = 0,
		$cat = "",
		$table;

	var $data;
		
	public function __construct($gid, $uid, $cat)
	{
		global $wpdb;

		$this->uid	= $uid;
		$this->gid	= $gid;
		$this->cat	= $cat;			
	   	$this->table	= wuss_prefix . "data";
	}
	
	public function ReturnResults($print_gid = false)
	{
		if (null == $this->data)
		{
			SendToUnity( '' );
			return;
		}
			
		$result = SendField("success","true");
		$old_id = -1; 
		foreach($this->data as $category)
		{
			if ($category->gid != $old_id && $print_gid)
			{
				$gid = $category->gid;
				if ($gid == 0)
					$gid = "Global";
				$result .= SendNode("_GAME_", "gid=$gid");
				$old_id = $category->gid;
			}
			$result .= $category->ToCML();
		}
		SendToUnity($result);
	}

	public function fetch_field($fid)
	{
		global $wpdb;
		
		$query = "SELECT fval FROM $this->table WHERE uid = '$this->uid' AND gid = '$this->gid' AND cat = '$this->cat' AND fid = '$fid'";
		$result = $wpdb->get_var($query);
		if (null != $result)
		{
			$temp = new dDataContainer($this->gid, $this->cat);
			$temp->AddField($fid, $result);
			$this->data[] = $temp;
		}
	}
	
	public function fetch_cat($cat = "")
	{
		if ($cat == "")
			$cat = $this->cat;

		global $wpdb;
		
		$query = "SELECT fid, fval FROM $this->table WHERE uid = '$this->uid' AND gid = '$this->gid' AND cat = '$cat'";
	
		$result = $wpdb->get_results($query);
		if (null != $result)
		{
			$temp = new dDataContainer($this->gid, $cat);
			
			foreach ( $result as $data )
				$temp->AddField($data->fid, $data->fval);
				
			$this->data[] = $temp;
		}
	}
	
	public function fetch_game($gid = -1)
	{
		if ($gid == -1)
			$gid = $this->gid;

		global $wpdb;
		
		$query = "SELECT cat, fid, fval FROM $this->table WHERE uid = '$this->uid' AND gid = '$gid' ORDER BY cat";
	
		$result = $wpdb->get_results($query);
		if ($result)
		{			
			$last_cat = time();
			$temp = null;
		
			foreach ($result as $data )
			{
				if ($data->cat != $last_cat)
				{
					if (null != $temp)
						$this->data[] = $temp;
					$temp = new dDataContainer($gid, $data->cat);
					$last_cat = $data->cat;
				}
				$temp->AddField($data->fid, $data->fval);		
			}

			if (null != $temp)
				$this->data[] = $temp;
		}
	}
	
	public function fetch_all_user_data()
	{
		global $wpdb;
		
		$games = $wpdb->get_results("SELECT DISTINCT gid FROM $this->table WHERE uid = '$this->uid'", ARRAY_N);
		if ($games)
		{
			$counter=0;
			foreach($games as $game)
			{
				$val = $game[0];
				$result .= $this->fetch_game($val);
			}
		}
	}
	
	public function remove_field($fid)
	{
		global $wpdb;

		if (false === $wpdb->delete( $this->table, array( 'uid' => $this->uid, 'gid' => $this->gid, 'cat' => $this->cat, 'fid' => $fid ) ))
			SendToUnity( '' ); 
		else
			SendToUnity( SendField("success", "true") ); 
	}
	
	public function remove_cat($cat = "")
	{
		if ($cat == "")
			$cat = $this->cat;

		global $wpdb;
		
		if (false === $wpdb->delete( $this->table, array( 'uid' => $this->uid, 'gid' => $this->gid, 'cat' => $this->cat) ))
			SendToUnity( '' ); 
		else
			SendToUnity( SendField("success", "true") ); 
	}
	
	public function remove_game($gid = -1)
	{
		if ($gid == -1)
			$gid = $this->gid;
			
		global $wpdb;
		
		if (false === $wpdb->delete( $this->table, array( 'uid' => $this->uid, 'gid' => $this->gid ) ))
			SendToUnity( '' ); 
		else
			SendToUnity( SendField("success","true") ); 
	}
	
	public function fetch_global_info()
	{
		$this->fetch_game(0);
	}
		
}
