<?php

class dDataContainer {
	var $gid = 0,
		$cat = "",
		$fields;
	
	public function __construct($gid, $cat)
	{
		global $wpdb;

		$this->gid	= $gid;
		$this->cat	= $cat;
	}

	public function AddField($name, $val)
	{
		$this->fields[$name] = $val;
	}
	
	public function ToCML()
	{	
		$result = "<_CATEGORY_>category=$this->cat\n";
		foreach($this->fields as $key => $val)
			$result .= SendField($key, $val);
		return $result;
	}
	
	public function commit_fields($uid, $table)
	{
		global $wpdb;
		if (null == $this->fields)
			return;

		foreach($this->fields as $key => $value)
		{
			$query  = "INSERT INTO $table (uid, gid, cat, fid, fval) VALUES ('$uid', '$this->gid', '$this->cat', '$key', '$value') "
					. " ON DUPLICATE KEY UPDATE fval = '$value'";

            $wpdb->query($query);
		}
	}
}
