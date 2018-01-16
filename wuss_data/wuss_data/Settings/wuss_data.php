<?php

function show_wuss_data_content()
{
	if ( !current_user_can( manage_options ) )
	{
		return __('You do not have sufficient permissions to access this page.');
		wp_die('');
	}

	global $wpdb;
	$header =
	$output = '<h2>User Data Management</h2>';

	$gid = Postedi("gid");
	$uid = Postedi("uid");

	//was this form submitted? Conversely: is there any action to perform?
	if (isset($_POST['wuss_data_action']))
	{
		switch($_POST['wuss_data_action'])
		{
			case "Update":
				$wpdb->update(wuss_prefix."data", array('fval' => $_POST['fval']), array('uid' => $uid,
				                                                                         'gid' => $gid,
				                                                                         'cat' => $_POST['cat'],
				                                                                         'fid' => $_POST['fid']));
				break;

			case "Delete":
				$wpdb->delete(wuss_prefix."data", array('uid' => $uid, 'gid' => $gid, 'cat' => $_POST['cat'], 'fid' => $_POST['fid']));
				$wpdb->delete(wuss_prefix."data", array('uid' => $uid, 'gid' => $gid, 'cat' => $_POST['fval']));
				break;

			case "Ban":
				$ban_uid = $uid;
				$ban_gid = $gid;
				$field = $ban_gid."_account_status";
				update_user_meta($ban_uid, $field, 2);
				break;

			case "Suspend for":
				$ban_uid = Postedi("uid");
				$ban_gid = $gid;
				$duration = Postedi("suspend_minutes") *  60;
				$duration += Postedi("suspend_hours") * 3600;
				$duration += Postedi("suspend_days") * 86400;
				update_user_meta($ban_uid, "{$ban_gid}_account_status", 1);
				update_user_meta($ban_uid, "{$ban_gid}_suspension_date", time() + $duration);
				break;

			case "Lift ban / suspension":
				$ban_uid = Postedi("uid");
				$ban_gid = $gid;
				$field = $ban_gid."_account_status";
				update_user_meta($ban_uid, $field, 0);
				break;

			case "Find user":
				$a_user = new WussUsers($gid);
				if ($a_user)
				{
					$username_to_find = Posted("find_user_value");
					foreach($a_user->users as $user)
					{
						if ($user->user_login == $username_to_find) {
							$_POST['uid'] = $_REQUEST['uid'] = $user->ID;
							break;
						}
					}
				}
				break;
		}
	}
	//in case it was updated above
	$uid = Postedi('uid');

	//first determine if there are games
	$games = new WussGames();
	if ( $games->GameCount() == 0)
	{
		$output .= '<div class=error><pre>No game data found...</pre></div>';
		return $output;
	}

	$wudata_games_count = 0;
	$games_dropdown = '<form method=post>'
	                  . '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
	                  . GetWUDataGames($gid, $wudata_games_count, "gid", "GLOBAL DATA", '','submit')
	                  . '</form><br>';

	if ($wudata_games_count == 0)
		return $header . 'No games with any stored data found...';

	$query = "SELECT ID,user_login,user_email,user_url,user_registered,display_name FROM $wpdb->users ORDER BY user_login";
	$users = $wpdb->get_results($query);
	array_unshift($users, null);

	$users_obj = new WussUsers($gid);

	//prepare the table row so the user is still auto determined
	//keep as a separate string as this entire row might not be shown
	$wuss_users_list = '<table><tr><td>'
	                   . '<form method="POST">'
	                   . $users_obj->DropDownList($gid, $uid, Posted('ufilter'), "GAME DATA")
	                   . $users_obj->FilterDropDown(Posted('ufilter'))
	                   . '</form>'
	                   . '</td><td>'
	                   . $users_obj->SearchField($gid)
	                   . '</td></tr></table>';

	$output .= '<table class="wuss_table_striped">'

	           .  '<tr><td valign="top" class="wuss_settings_description">'
	           .  'Select Game'
	           .  '</td><td valign="top" class="wuss_settings_values">'
	           .  (($games->GameCount() > 0) ? $games_dropdown : '')
	           .  '</td></tr>';

	if ($gid > 0)
		$output .= '<tr><td valign="top" class="wuss_settings_description">'
		           .  'Select Account Holder'
		           .  '</td><td valign="top" class="wuss_settings_values">'
		           .  $wuss_users_list
		           .  '</td></tr>';

	$output .= '<tr><td valign="top" class="wuss_settings_description">'
	           .  'User data'
	           .  '</td><td valign="top" class="wuss_settings_values">';


	$user = null;
	foreach($users as $_user) {
		if ( $_user->ID == $uid ) {
			$user = $_user;
		}
	}

	$output .= wuss_dataShowUserData($user, $gid)
	           .  '</td></tr>';

	$output .=  '</table>';
	return $output;
}

function GetWUDataGames($selected = 0, &$count, $name = "gid", $first_entry = "", $class = "", $onchange = "")
{
	$first_entry = sanitize_text_field($first_entry);
	$class = sanitize_text_field($class);
	$name = sanitize_text_field($name);

	global $wpdb;
	$games = new WussGames();
	$new_fomat_games = $games->GetListOfGames();
	$gids = $wpdb->get_col('SELECT DISTINCT gid FROM '. wuss_prefix. 'data');
	$values = null;
	$count = (null == $gids) ? 0 : count($gids);
	if (null != $gids) {
		foreach ( $gids as $gid)
		{
			if (isset($new_fomat_games[$gid]))
			{
				$values[$gid.''] = $new_fomat_games[$gid];
			}
			else {
				$values[ $gid . '' ] = $gid == 0 ? $first_entry : "Legacy: Game with id $gid";
			}
		}
	}
	if (null === $values)
		$result = 'No games with any stored data found...';
	else
		$result = WussDropDownListi($values, $selected, $name, '', $class, $onchange);

	return $result;
}

function wuss_dataShowUserData($user, $gid)
{
	global $wpdb;
	$data = null;
	if($user) {
		$data_query = "SELECT cat,gid,fid,fval FROM " . wuss_prefix
		              . "data WHERE uid='$user->ID' AND gid='$gid' ORDER BY gid,cat";
		$data       = $wpdb->get_results( $data_query );
	}
	if (!$data)
	{
		$output = 'No data found for the selected user or no user selected<br>';
		return $output;
	}
	$last_cat = "nothing";
	$contents_table = "<table class=\"stattable\" >";

	foreach($data as $entry)
	{
		if ($entry->cat != $last_cat)
		{
			$last_cat = $entry->cat;
			$contents_table .= "<tr><td colspan=4 class=stattableheader>".($last_cat == "" ? "Global settings" : $last_cat).'</td></tr>';
		}
		$table = "<tr><td width=120>$entry->fid</td>
            <td width=\"*\">
            <form method=post>
            <input type=text  class=\"inputfield\" name=fval value=\"{$entry->fval}\">
            </td>
        
            <td class=\"stattablebuttoncol\">
            <input type=hidden name=fid value=\"{$entry->fid}\">
            <input type=hidden name=cat value=\"{$last_cat}\">
            <input type=hidden name=menu_tab value=\"".MENUTAB."\">
            <input type=hidden name=gid value=\"{$entry->gid}\">
            <input type=hidden name=uid value=\"{$user->ID}\">
            <input type=submit name=\"wuss_data_action\" class=\"button-primary inputbutton\" value=\"Update\">
            </td>
        
            <td class=\"stattablebuttoncol\">
            <input type=submit name=\"wuss_data_action\" class=\"button-primary inputbutton\" value=\"Delete\">
            </form>
        
            </td></tr>";
		$contents_table .= $table;
	}
	$table = "</td></tr></table>";
	$contents_table .= $table;
	$output  = '<div>' . $contents_table . '</div>';
	return $output;
}
