<?php
    
    function show_wuss_scoring_content()
    {
        if ( !current_user_can( manage_options ) )
        {
            return __('You do not have sufficient permissions to access this page.');
            wp_die('');
        }
        
        global $wpdb;
        $header =
        $output = '<h2>Leaderboards overview</h2>';
        
        //See what game we are working with
        $gid = Postedi("gid");
 
        //was this form submitted? Conversely: is there any action to perform?
        if (isset($_POST['wuss_scoring_action']))
        {
            switch($_POST['wuss_scoring_action'])
            {
                case "Update":
                    update_option('wuss_scoring_results_limit_'.$gid, Postedi('fval') );
                    break;
            }
        }
        
        //first determine if there are more than 1 game id
        $games = new WussGames();
        if ( $games->GameCount() == 0)
        {
            $output .= '<div class=error><pre>No game data found...</pre></div>';
            return $output;
        }

        $games_dropdown = '<form method=post style="width:220px">'
        . '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
        . $games->GamesDropDownList($gid, true, "gid", "Default", '','submit')
        . '</form><br>';


	    $output .= '<table class="wuss_table_striped">'
	               .  '<tr><td valign="top" class="wuss_settings_description">'
	               .  'Select Game'
	               .  '</td><td valign="top" class="wuss_settings_values">'
	               .  (($games->GameCount() > 0) ? $games_dropdown : '')
	               .  '</td></tr>';

	    $output .= '<tr><td valign="top" class="wuss_settings_description">'
	               .  'Entries to return'
	               .  '</td><td valign="top" class="wuss_settings_values">'
	               .  wuss_scoring_settings($gid, $games->GameName($gid, "UNKNOWN GAME") )
	               .  '</td></tr>';

	    $output .=  '</table>';

	    return $output;
    }
    
    function wuss_scoring_settings($gid, $game_name)
    {
        $value = get_option('wuss_scoring_results_limit_'.$gid);
        if ($value == 0 || $value == '') $value = 20;
        $settings = $gid == 0 ? "Global setting" : "$game_name entries ";
        $contents_table ='<center><form method=post><input type="hidden" name="menu_tab" value="' . MENUTAB .
        "\"><input type=hidden name=gid value=\"$gid\"><table class=\"stattable\" width=\"550\">
        <tr><td colspan=3 class=stattableheader>$settings</td></tr>
        <tr><td width=\"200\">Number of entries to return</td>
            <td>
                <input type=text  class=\"inputfield\" name=fval value=\"".$value."\">
            </td>
        
            <td class=\"stattablebuttoncol\">
            <input type=submit name=\"wuss_scoring_action\" class=\"button-primary inputbutton\" value=\"Update\">
            </td>
        </tr>
        </table></form></center>";
        $output  = '<div>' . $contents_table . '</div>';
        return $output;
    }
