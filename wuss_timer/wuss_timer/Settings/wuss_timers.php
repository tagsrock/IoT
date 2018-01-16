<?php

function show_wuss_timers_content()
{
	if ( !current_user_can( manage_options ) )
	{
		return __('You do not have sufficient permissions to access this page.');
		wp_die('');
	}

	global $wpdb;
	$output = '<h2>Timers overview</h2>';

	//See what game we are working with
	$gid = Postedi("gid");
	$uid = Postedi("uid");

	//was this form submitted? Conversely: is there any action to perform?
	if (isset($_POST['wuss_timers_action']))
	{
		switch($_POST['wuss_timers_action'])
		{
			case "Update":
				//make sure new timer doesn't exceed timer limit. Also make sure timer >= 1
				$requested_time = Postedi('timerval');
				$timer_max = Postedi('timermax');
				if ($timer_max <= 0)
					$timer_max = 1;
				if ($requested_time > $timer_max)
					$requested_time = $timer_max;
				if ($requested_time < 0)
					$requested_time = 0;
				$new_timer = time() + $requested_time;

				//make sure max points > 0 and that requested points don't exceed max
				$requested_points = Postedi('pointsval');
				$points_max = Postedi('pointsmax');
				if ($points_max < 1)
					$points_max = 1;
				if ($requested_points > $points_max)
					$requested_points = $points_max;
				if ($requested_points < 0)
					$requested_points = 0;

				$wpdb->update(
					wuss_prefix.'timers',
					array('timer' => $new_timer, 'timermax' => Postedi('timermax'), 'points' => $requested_points, 'pointsmax' =>  $points_max),
					array('uid' => $uid, 'gid' => $gid, 'fid' => Posted('fid'))
				);
				break;

			case "Delete":
				$wpdb->delete(
					wuss_prefix.'timers',
					array('uid' => $uid, 'gid' => $gid, 'fid' => Posted('fid'))
				);
				break;
		}
	}

	$games = new WussGames(true);
	if ( $games->GameCount() == 0)
	{
		$output .= '<div class=error><pre>No game data found...</pre></div>';
		return $output;
	}

	//game id 0 is NOT valid and will NOT be shown
	if ($gid == 0 && $games->GameCount() > 0)
		$gid = $games->games->posts[0]->ID;
	$games_dropdown = '<form method=post>'
	                  . '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
	                  . $games->GamesDropDownList($gid, true, 'gid', "", '','submit')
	                  . '</form><br>';

	$users_obj = new WussUsers($gid);

	//prepare the table row so the user is still auto determined
	//keep as a separate string as this entire row might not be shown
	$wuss_users_list = '<table><tr><td>'
	                   . '<form method="POST">'
	                   . $users_obj->DropDownList($gid, $uid, Posted('ufilter'))
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

	$output .= '<tr><td valign="top" class="wuss_settings_description">'
	           .  'Select Account Holder'
	           .  '</td><td valign="top" class="wuss_settings_values">'
	           .  $wuss_users_list
	           .  '</td></tr>';

	$output .= '<tr><td valign="top" class="wuss_settings_description">'
	           .  'Timers'
	           .  '</td><td valign="top" class="wuss_settings_values">';


	if ($games->GameCount() > 0)
		$output .= wuss_timers_settings($uid, $gid, $games->GameName($gid, "UNKNOWN GAME") );
	$output .=  '</td></tr>';
	$output .=  '</table>';

	return $output;
}

function wuss_timers_settings($uid, $gid, $game_name)
{
	global $wpdb;
	if ($gid == 0)
	{
		$contents_table = "<table class=\"stattable\" width=\"550\">
            <tr><td width=\"550\" class=stattableheader>Select a game to see it's timers</td></tr></table>";
	} else {
		$table_name = wuss_prefix . 'timers';
		$data       = $wpdb->get_results(
			"
        		SELECT fid, timermax, timer, points, pointsmax
        		FROM $table_name
        		WHERE uid = '$uid' 
        			AND gid = '$gid'
        		"
		);
		if ( !$data ) {
			$header = "No timers found";
		} else {
			$header = $gid == 0 ? "Invalid Timers" : "Timers for $game_name";
		}
		$contents_table = "<table class=\"stattable\" width=\"550\">
            <tr><td colspan=7 class=stattableheader width=\"595\">$header</td></tr>";
		if ( $data ) {
			$contents_table .= "<tr><td>Timer name</td><td width=\"80\">Time left</td><td width=\"80\">Duration</td><td width=\"80\">Points</td><td width=\"80\">Max</td><td width=\"70\">&nbsp;</td><td width=\"70\">&nbsp</td></tr>";
			foreach ( $data as $entry ) {
				$contents_table .= "<tr><td>"
				                   . '<form method=post><input type="hidden" name="menu_tab" value="' . MENUTAB . '">'
				                   . "<input type=hidden name=gid value=\"$gid\"><input type=hidden name=uid value=\"$uid\"><input type=hidden name=fid value=\"{$entry->fid}\">";

				$timer_value = $entry->timer - time();
				if ($timer_value < 0)
					$timer_value = 0;
				$contents_table .= "$entry->fid</td>";
				$contents_table .= "<td><input type=text  class=\"inputfieldshort\" name=timerval value=\"" . $timer_value. '"></td>';
				$contents_table .= "<td><input type=text  class=\"inputfieldshort\" name=timermax value=\"" . $entry->timermax . '"></td>';
				$contents_table .= "<td><input type=text  class=\"inputfieldshort\" name=pointsval value=\"" . $entry->points . '"></td>';
				$contents_table .= "<td><input type=text  class=\"inputfieldshort\" name=pointsmax value=\"" . $entry->pointsmax . '"></td>';

				$contents_table .= "<td>
                <input type=submit name=\"wuss_timers_action\" class=\"button-primary inputbutton\" value=\"Update\">
                </td>

                <td>
                <input type=submit name=\"wuss_timers_action\" class=\"button-primary inputbutton\" value=\"Delete\"></form></td>
                </td>
            </tr>";
			}
		}
		$contents_table .= "</table>";
	}
    $output  = '<div>' . $contents_table . '</div>';
    return $output;
}
