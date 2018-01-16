<?php

	function SendNode($node_name, $fields="")
	{
		return "<$node_name>$fields\n";
	}
		
	function SendField($field_name, $field_value)
	{
		return "[$field_name]$field_value\n";
	}
	
	function PrintError($message, $err_no='')
	{
		$result = SendNode("status", "success=false; message=$message");
	
		if ('' != $err_no)
			$result .= SendField("error_code", $err_no);

		return $result;
	}

	function SendToUnity($result)
	{
		$result = SendNode(strtoupper(WUSSACTION)) . $result;
		echo '<CML>'.base64_encode($result).'</CML>';
	}
	
	function wuss_table_prefix($force_local = false)
	{
		global $wpdb;
		
		if (function_exists('is_multisite') && is_multisite() && !$force_local)
			return get_option("wuss_table_prefix");
		else
			return $wpdb->prefix . get_option("wuss_table_prefix");
	}
	define ('wuss_prefix', wuss_table_prefix());

	function Postedi($field)
	{
		return (int)Postedf($field);
	}

	function Postedf($field)
	{
		return isset($_REQUEST[$field]) ? sanitize_text_field( strip_tags($_REQUEST[$field] ) ) : 0;
	}

	function Posted($field)
	{
		return isset($_REQUEST[$field]) ? sanitize_text_field( strip_tags($_REQUEST[$field] ) ) : "";
	}
