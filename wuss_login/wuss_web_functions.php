<?php
function WussDropDownListi($values, $selected = 0, $name = "selection", $first_entry = "", $class = "", $onchange = "")
{
	$first_entry = sanitize_text_field($first_entry);
	$class = sanitize_text_field($class);
	$name = sanitize_text_field($name);

	$suffix =  strpos($onchange, '(') > 0 ? '' : '()';

	$opening = "<SELECT name=$name "
	           . ($onchange == '' ? '' : ("OnChange=\"{$onchange}{$suffix}\""))
	           . ($class != '' ? 'class="'.$class.'"' : '')
	           .">";
	$closing = '</SELECT>';

	if ((null === $values || count($values) == 0) && $first_entry == "")
		return $opening . '<option value="-1">NONE</option>' . $closing;

	if ($selected == 0 && (null !== $values && count($values) > 0))
		$selected = $values[0];

	if ($first_entry != '')
	{
		$result = "<OPTION value=\"0\" "
		          . (0 == $selected ? 'selected' : '')
		          . ">{$first_entry}</OPTION>";
	} else $result = '';

	if (null !== $values)
		foreach ($values as $key => $value)
		{
			$result .= "<OPTION value=\"$key\" "
			           . ($key == $selected ? 'selected' : '')
			           . ">{$value}</OPTION>";
		}

	return $opening . $result . $closing;
}

function WussDropDownChangeActiveGame($games, $gid, $first_value = '', $width=220)
{
	$games_dropdown = '<form method=post style="width:'.$width.'px">'
	                  . '<input type=hidden name="menu_tab" value="' . MENUTAB . '">'
	                  . $games->GamesDropDownList($gid, "gid", $first_value, '','submit')
	                  . '</form>';
	return $games_dropdown;
}

function wuss_error_message($message)
{
	return '<div class="error"><p><strong>'. $message .'</strong></p></div>';
}

function _wuss_banners( $atts ){
	$params = shortcode_atts( array(
		'horizontal' => 'false',
	), $atts );

	$games = new WussGames();
	$content = $games->ListGameBanners($params['horizontal'] == 'true');
	return "<div>$content</div>";
}
add_shortcode( 'wuss_banners', '_wuss_banners' );

/*
   Available Params:
	styles: Add anything you would want inside of the styles="" tag
	gid: either specify a game's ID value manually or else I attempt to fetch it from the URL
	border: set to 'true' or omit to have a black border around the image. Any other value will remove it
*/
function _wuss_game_poster( $atts ){
	$params = shortcode_atts( array(
		'styles' => '',
		'gid' => '-1',
		'border' => 'true',
	), $atts );

	$gid = intval($params['gid']);
	if (null == $gid || $gid < 0)
		$gid = Postedi('gid');

	$games = new WussGames();
	$game = $games->GetSpecificGame($gid);
	if (null === $game)
		return '<img alt="Invalid game selected">';

	$img = wp_get_attachment_image_src(get_post_thumbnail_id($gid), 'original');
	$img_src = ($img ? $img[0] : '');
	if ($img_src == '')
		return '<img alt="Image not found">';

	$class = $params['border'] == 'true' ? '' : '_noframe';
	$style = sanitize_text_field($params['styles']);
	if (!empty($style))
		$style = " style=\"$style\"";
	return '<img src="'.$img_src.'" class="wuss_poster'.$class.'"'. $style .'>';
}
add_shortcode( 'wuss_game_poster', '_wuss_game_poster' );

function _wuss_game_title( $atts ) {
	$params = shortcode_atts( array(
		'gid' => '-1',
	), $atts );

	$gid = intval($params['gid']);
	if (null == $gid || $gid < 0)
		$gid = Postedi('gid');

	$games = new WussGames();
	$game = $games->GetSpecificGame($gid);
	if (null === $game)
		return '';

	return $game->post_title;
}
add_shortcode( 'wuss_game_title', '_wuss_game_title' );

function _wuss_game_summary( $atts ) {
	$params = shortcode_atts( array(
		'gid' => '-1',
	), $atts );

	$gid = intval($params['gid']);
	if (null == $gid || $gid < 0)
		$gid = Postedi('gid');

	$games = new WussGames();
	$game = $games->GetSpecificGame($gid);
	if (null === $game)
		return '';

	return $game->post_excerpt;
}
add_shortcode( 'wuss_game_summary', '_wuss_game_summary' );


function _wuss_game_description( $atts ) {
	$params = shortcode_atts( array(
		'gid' => '-1',
	), $atts );

	$gid = intval($params['gid']);
	if (null == $gid || $gid < 0)
		$gid = Postedi('gid');

	$games = new WussGames();
	$game = $games->GetSpecificGame($gid);
	if (null === $game)
		return '';

	return $game->post_content;
}
add_shortcode( 'wuss_game_description', '_wuss_game_description' );

function _wuss_game_account_standing( $atts , $content) {
	global $current_user;

	if (!is_user_logged_in())
		return '';

	$params = shortcode_atts( array(
		'gid' => '-1',
		'good_color' => "#3c3",
		'bad_color' => "#c33",
		'show_good' => "true",
	), $atts );

	$gid = intval($params['gid']);
	if (null == $gid || $gid < 0)
		$gid = Postedi('gid');

	$users = new WussUsers();
	$status = $users->GetAccountStatus($current_user->ID, $gid);
	$color = $status == 0 ? $params['good_color'] : $params['bad_color'];
	switch($status)
	{
		case 1: return '<font color="'.$color.'">Account suspended</font>' . $content;
		case 2: return '<font color="'.$color.'">Account is banned</font>' . $content;
	}
	if ($params['show_good'] == "false")
		return '';
	return '<font color="'.$color.'">Account in good standing</font>' . $content;
}
add_shortcode( 'wuss_game_account_standing', '_wuss_game_account_standing' );


function _wuss_webgl( $atts )
{
	$params = shortcode_atts( array(
		'gid' => '-1',
		'width' => '100%',
		'height' => '100%',
		'game' => '',
		'autoload' => 'false',
	), $atts );

	$gid = $params['gid'];
	$width = $params['width'];
	$height = $params['height'];
	$autoload = $params['autoload'];
	$game_folder = $params['game'];
	$height2 = strpos($height,'%') >= 0 ? $height : (intval($height) + 10) . 'px';

	if (!strpos($height, '%')  && !strpos($height, 'px')) $height .= "px";
	if (!strpos($height2, '%') && !strpos($height2, 'px')) $height2 .= "px";
	if (!strpos($width, '%') && !strpos($width, 'px')) $width .= "px";

	$iframe = '<iframe src="'
	          . get_option('wuss_webplayer_path')
	          . 'wuss_index.php?game='
	          . $game_folder
	          . '&h='
	          . $height
	          . '&gid='
	          . $gid
	          . '&autoload='
	          . $autoload
	          . '" style="width:'
	          . $width
	          . '; height:'
	          . $height2
	          . '"></iframe>"';
	if (is_ssl())
		$iframe = str_replace("http://", "https://", $iframe);

	return $iframe;
}
add_shortcode('wuss_webgl', '_wuss_webgl');
