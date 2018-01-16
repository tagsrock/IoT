<?php

function _wuss_generate_leaderboard( $atts )
{
	$params = shortcode_atts( array(
		'gid' => '-1',
		'random' => "false",
		'show_name' => "true",
		'limit' => "5",
		'sort_order' => 'DESC',
		'width' => 400,
		'gravatar_size' => 32,
		'widget' => 'false',

	), $atts );

	$games = new WussGames(true);

	$gid = intval($params['gid']);
	if (null == $gid || $gid < 0)
		$gid = Postedi('gid');

	if (strtolower($params['random']) == "true")
	{
		if (null != $games->games)
			$gid = $games->games->posts[ mt_rand(0, $games->GameCount() - 1) ]->ID;
	}

	if ($gid <= 0)
	{
		if (null != $games->games)
			foreach ($games->games->posts as $game)
				if ($game->ID > 0)
				{
					$gid = $game->ID;
					break;
				}
	}

	$game_name = '';
	if (null != $games->games)
		foreach($games->games->posts as $game)
			if ($game->ID == $gid)
				$game_name = $game->post_title;

	$limit = intval($params['limit']);
	if ($limit < 1)
		$limit = 5;

	$sort_order = strtolower(trim($params['sort_order'])) == 'asc' ? 'ASC' : 'DESC';

	$width = intval($params['width']);
	if ($width < 100)
		$width = 100;

	$scores = new WussHighscores();
	$entries = $scores->FetchScores($gid, $limit, $sort_order);

	$gravatar_size = intval($params['gravatar_size']);
	if (null == $gravatar_size || $gravatar_size == 0)
		$gravatar_size = 32;

	if ($params['widget'] == 'false') {
		$output = '<table style="width:' . $width . 'px" class="wuss_highscores_table">';
		if ( $params['show_name'] == 'true' && $game_name != '' ) {
			$output .= "<tr class=\"wuss_highscores_header\"><td colspan=3 align=\"center\" class=\"wuss_highscores_header\">$game_name</td></tr>";
		}
		foreach ( $entries as $entry ) {
			$gravatar_url = "https://www.gravatar.com/avatar/{$entry['gravatar']}.jpg?s={$gravatar_size}&d=retro&r=g";
			$output .= "<tr><td id=\"wuss_highscores_gravatar_col\"><img src=\"{$gravatar_url}\"></td>
						<td>{$entry['nname']}</td><td>{$entry['score']}</td></tr>";
		}
		$output .= "</table>";
	}
	else
	{
		$counter = 1;
		$output = '<table style="width:' . $width . 'px">';
		if ( $params['show_name'] == 'true' && $game_name != '' ) {
			$output .= "<tr colspan=\"3\" class=\"wuss_highscores_header_widget\"><td colspan=3 align=\"center\" class=\"wuss_highscores_header\">$game_name</td></tr>";
		}
		foreach ( $entries as $entry ) {
			$output .= "<tr><td>{$counter}</td><td>{$entry['nname']}</td><td>{$entry['score']}</td></tr>";
			$counter++;
		}
		$output .= '</table>';
	}
	return $output;
}

function _wuss_leaderboard( $atts ) {
	return _wuss_generate_leaderboard( $atts );
}
add_shortcode( 'wuss_leaderboard', '_wuss_leaderboard' );
