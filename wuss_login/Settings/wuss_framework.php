<?php
include_once(dirname(__FILE__) . "/wuss_BASE.class.php");
include_once(dirname(__FILE__) . "/../classes/WussGames.class.php");
include_once(dirname(__FILE__) . "/../classes/WussUsers.class.php");

function show_wuss_framework()
{
	if (isset($_POST['menu_tab']))
		$menu_tab = Posted("menu_tab");
	if (empty($menu_tab))
		$menu_tab = $_POST_['menu_tab'] = $_REQUEST['menu_tab'] = "WUSS";

	define('MENUTAB', $menu_tab);

	if ( !current_user_can( manage_wuss ) )
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );

	?>
    <div class="wrap" class="widefat">
        <div id="dashicons-welcome-view-site" class="icon32"></div><h2>WORDPRESS UNITY SOCIAL SYSTEM</h2>
        <div style="border:solid 1px; background-color:#000"><img class="wuss_setting_banner" src="/wp-content/plugins/wuss_login/images/mbsbanner.jpg"></div>
        <div id="response_area"></div>
		<?php
		$wuss = new wuss_BASE();
		$wuss->create_section_heading($menu_tab);
		?>
    </div>
    <div id="wuss_section_content">
		<?php
		$content = '';
		$content = apply_filters("wuss_section_content", $content, $menu_tab);
		echo $content;
		?>
    </div>
    </div>
	<?php
}

function wuss_game_security_string($gid)
{
	global $wpdb;
	$security_string = $wpdb->get_var("SELECT meta_value FROM " . get_option('wuss_meta_table') ." WHERE meta_key = 'security' AND gid='$gid'");
	if (!$security_string)
		$security_string = '';
	return $security_string;
}

function wuss_perform_updates($gid, &$uid, $users)
{
	global $wpdb;

	//Test for game specific actions to perform
	if (isset($_POST['wuss_game_action']))
	{
		switch ($_POST['wuss_game_action'])
		{
			case "Set Security":
				if (
					isset( $_POST['upd_security_nonce'] )
					&& wp_verify_nonce( $_POST['upd_security_nonce'], 'upd_security_url' )
				) {
					$table_name = get_option('wuss_meta_table');
					$security_string = Posted('wuss_game_security');
					if (0 == $wpdb->update($table_name, array("meta_value" => $security_string), array('gid' => $gid, 'meta_key' => 'security')))
						$wpdb->insert($table_name, array("meta_value" => $security_string, 'gid' => $gid, 'meta_key' => 'security'));
				}
				break;
		}
	}

	//Test for global actions to perform
	if (isset($_POST['wuss_global_action']))
	{
		switch ($_POST['wuss_global_action'])
		{
			case "Update":
				if (
					isset( $_POST['upd_prod_page_nonce'] )
					&& wp_verify_nonce( $_POST['upd_prod_page_nonce'], 'upd_prod_page_url' )
				) {
					update_option('wuss_product_page_url', esc_url_raw(Posted('wuss_security')));
				}

				if (
					isset( $_POST['upd_webplayer_path_nonce'] )
					&& wp_verify_nonce( $_POST['upd_webplayer_path_nonce'], 'upd_webplayer_path' )
				) {
					update_option('wuss_webplayer_path', esc_url_raw(Posted('wuss_webgl_path')));
				}

				if (
					isset( $_POST['upd_webgl_placeholder_nonce'] )
					&& wp_verify_nonce( $_POST['upd_webgl_placeholder_nonce'], 'upd_webgl_placeholder' )
				) {
					update_option('wuss_webgl_placeholder', esc_url_raw(Posted('wuss_webgl_placeholder')));
				}



				break;
		}
	}

	//Test for user specific actions to perform
	if (isset($_POST['wuss_user_action']))
	{
		switch($_POST['wuss_user_action'])
		{
			case "Ban":
				$users->BanUser($uid, $gid);
				break;

			case "Suspend for":
				$m = Postedi("suspend_minutes");
				$h = Postedi("suspend_hours");
				$d = Postedi("suspend_days");
				$users->SuspendUser($uid,$gid,$m,$h,$d);
				break;

			case "Lift ban / suspension":
				$users->SetStatus($uid, $gid, 0);
				break;

			case "Search":
				$user_to_find = Posted("wuss_find_user");
				$search_result = $users->GetSingleUserByName($user_to_find);
				if ($search_result > 0)
					$uid = $search_result;
				break;
		}
	}
}

function wuss_default_content($value)
{
	if (MENUTAB != "WUSS")
		return $value;

	$output = '<h2>Game Settings</h2><table class="wuss_table_striped">';

	$games = new WussGames();
	if ( $games->GameCount() == 0)
	{
		$output .= '<div class=error><pre>No games found...</pre></div>';
		return $output;
	}

	$users = new WussUsers();
	$uid = Postedi('uid');
	$gid = Postedi('gid');
	wuss_perform_updates($gid, $uid, $users);

	if ($gid == 0)
		$gid = $games->GetFirstGameID();
	$games_dropdown = '<form method=post>'
	                  . '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
	                  . $games->GamesDropDownList($gid, true, "gid", "", '','submit')
	                  . " &nbsp; Game ID: $gid "
	                  . '</form><br>';

	$security_string = wuss_game_security_string($gid);
	$security_button_status = $gid == 0 ? ' disabled' : '';

	$user_list = '<table><tr><td>'
	             . '<form method="POST">'
	             . $users->DropDownList($gid, $uid, Posted('ufilter'))
	             . $users->FilterDropDown(Posted('ufilter'))
	             . '</form>'
	             . '</td><td>'
	             . $users->SearchField($gid)
	             . '</td></tr></table>';

	$output .= '<tr><td valign="top" class="wuss_settings_description">'
	           .  'Select Game'
	           .  '</td><td valign="top" class="wuss_settings_values">'
	           .  (($games->GameCount() > 0) ? $games_dropdown : '')
	           .  '</td></tr>'

	           .  '<tr><td valign="top" class="wuss_settings_description">'
	           .  'Game security string (Optional)'
	           .  '</td><td valign="top" class="wuss_settings_values" style="width:500px">'
	           .  '<form method=post>'
	           .  '<input type="input" name="wuss_game_security" value="'. $security_string .'" class="inputfieldlong">'
	           .  '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
	           .  '<input type=hidden name="gid" value="' . $gid . '">'
	           .  '<input type=hidden name="uid" value="' . $uid . '">'
	           .  '<br>'
	           .  '<input type=submit name="wuss_game_action" value="Set Security" class="button-secondary inputbutton" style="width:100px" '.$security_button_status.'>'
	           . wp_nonce_field('upd_security_url', 'upd_security_nonce', false, false)
	           .  " &nbsp;<strong>* Do NOT use:</strong> &nbsp;&#60; &#62;&nbsp;'&nbsp;\"&nbsp;\\"
	           .  '</form>'
	           .  '</td></tr>'

	           .  '</table>';

	if ($gid > 0)
		$output .= '<h2>Player Status</h2><table class="wuss_table_striped">'
		           .  '<tr><td valign="top" class="wuss_settings_description">'
		           .  'Select Account Holder'
		           .  '</td><td valign="top" class="wuss_settings_values">'
		           .  $user_list
		           .  '</td></tr>'

		           .  '<tr><td valign="top" class="wuss_settings_description">'
		           .  'Account standing'
		           .  '</td><td valign="top" class="wuss_settings_values">'
		           .  $users->DisplayAccountActions($gid, $uid)
		           .  '</td></tr>'
		           .  '</table>';

	$output .= '<h2>System paths / pages</h2><table class="wuss_table_striped">'
	           .  '<tr><td valign="top" class="wuss_settings_description">'
	           .  'Product description template'
	           .  '</td><td valign="top" class="wuss_settings_values" style="width:500px">'
	           .  '<form method=post>'
	           .  '<input type="input" name="wuss_security" value="'. get_option("wuss_product_page_url") .'" class="inputfieldlong">'
	           .  '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
	           .  '<input type=hidden name="gid" value="' . $gid . '">'
	           .  '<input type=hidden name="uid" value="' . $uid . '">'
	           .  '<br>'
	           .  '<input type=submit name="wuss_global_action" value="Update" class="button-secondary inputbutton">'
	           . wp_nonce_field('upd_prod_page_url', 'upd_prod_page_nonce', false, false)
	           .  '</form>'
	           .  '</td></tr>'

	           .  '<tr><td valign="top" class="wuss_settings_description">'
	           .  'WebGL folder'
	           .  '</td><td valign="top" class="wuss_settings_values" style="width:500px">'
	           .  '<form method=post>'
	           .  '<input type="input" name="wuss_webgl_path" value="'. get_option("wuss_webplayer_path") .'" class="inputfieldlong">'
	           .  '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
	           .  '<input type=hidden name="gid" value="' . $gid . '">'
	           .  '<input type=hidden name="uid" value="' . $uid . '">'
	           .  '<br>'
	           .  '<input type=submit name="wuss_global_action" value="Update" class="button-secondary inputbutton">'
	           . wp_nonce_field('upd_webplayer_path', 'upd_webplayer_path_nonce', false, false)
	           .  '</form>'
	           .  '</td></tr>'

	           .  '<tr><td valign="top" class="wuss_settings_description">'
	           .  'WebGL placeholder graphic'
	           .  '</td><td valign="top" class="wuss_settings_values" style="width:500px">'
	           .  '<form method=post>'
	           .  '<input type="input" name="wuss_webgl_placeholder" value="'. get_option("wuss_webgl_placeholder") .'" class="inputfieldlong">'
	           .  '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
	           .  '<input type=hidden name="gid" value="' . $gid . '">'
	           .  '<input type=hidden name="uid" value="' . $uid . '">'
	           .  '<br>'
	           .  '<input type=submit name="wuss_global_action" value="Update" class="button-secondary inputbutton">'
	           . wp_nonce_field('upd_webgl_placeholder', 'upd_webgl_placeholder_nonce', false, false)
	           .  '</form>'
	           .  '</td></tr>'
	           .  '</table>';
	return $output;
}
