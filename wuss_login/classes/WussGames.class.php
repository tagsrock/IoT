<?php
class WussGames {
	var $games;
	var $active_gid;
	var $legacy_games;

	public function __construct($poll_legacy = false)
	{
		$this->games	= $this->GetAllGames();
		if ($poll_legacy)
			$this->GetLegacyIDs();
	}

	public function GameCount()
	{
		return null === $this->games ? 0 : count($this->games->posts);
	}

	public function GameName($gid, $not_found)
	{
		if (null === $this->games)
			return $not_found;

		foreach($this->games->posts as $post)
			if ($post->ID == $gid)
				return $post->post_title;

		return $not_found;
	}

	function GetLegacyIDs()
	{
		global $wpdb;
		$this->legacy_games = null;
		$query = "SELECT DISTINCT gid FROM ". wuss_prefix ."data WHERE gid > 0";
		$legacy = $wpdb->get_col($query);
		if ($legacy)
		{
			foreach ($legacy as $gid)
			{
				foreach($this->games->posts as $game)
				{
					if ($game->ID == $gid)
						continue;
				}
				$this->legacy_games[] = $gid;
			}
		}
	}

	public function GetAllGames()
	{
		$query = new WP_Query( array( 'post_type' => 'wuss_game') );
		if ($query->have_posts())
			return $query;
		else
			return null;
	}

	public function GetFirstGameID()
	{
		return null == $this->games ? 0 : $this->games->posts[0]->ID;
	}
	public function GetSpecificGame($gid)
	{
		$query = new WP_Query( array( 'post_type' => 'wuss_game', 'p' => $gid) );
		if ($query->have_posts())
			return $query->posts[0];
		else
			return null;
	}

	public function GetListOfGames($include_legacy = false)
	{
		$result = null;
		if (null === $this->games && null === $this->legacy_games)
		{
			return $result;
		}

		foreach ($this->games->posts as $post)
			$result[$post->ID .''] = $post->post_title;

		if ($include_legacy && null !== $this->legacy_games && count($this->legacy_games) > 0)
			foreach($this->legacy_games as $gid)
				$result[$gid .''] = "Legacy: Game with ID $gid";

		return $result;
	}

	public function DoesUserHaveData($uid, $gid)
	{
		global $wpdb;
		return (null != $wpdb->get_row("SELECT DISTINCT uid FROM ".wuss_prefix."data WHERE uid='$uid' AND gid='$gid'"));
	}

	public function GamesDropDownList($selected = 0, $include_legacy = true, $name = "gid", $first_entry = "", $class = "", $onchange = "")
	{
		$first_entry = sanitize_text_field($first_entry);
		$class = sanitize_text_field($class);
		$name = sanitize_text_field($name);

		$result = WussDropDownListi($this->GetListOfGames($include_legacy), $selected, $name, $first_entry, $class, $onchange);

		return $result;
	}

	public function GetGameIcon($gid, $size="thumbnail", $styles="", $onmouseover="")
	{
		if (!$this->games)
			return wuss_error_message('No games were found...');
		$result = "";
		$size = sanitize_text_field(strtolower($size));
		switch ($size)
		{
			case "medium":
			case "full":
			case "original":
				break;

			default:
				$size = "thumbnail";
		}


		foreach ($this->games->posts as $post)
		{
			if ($post->ID != $gid)
				continue;
			$img = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $size);
			$result = '<img src="'.$img[0]. '"';
			if ($styles != '')
				$result .= " style=\"" . $styles .'"';
			if ($onmouseover != '')
				$result .= ' onmouseover="' . $onmouseover."()\"";
			$result .= '>';
		}
		return $result;
	}

	public function ListGameBanners($horizontal = true)
	{
		if (!$this->games)
			return wuss_error_message('No games were found...');
		$result = "";

		$style = $horizontal ? '_h' : '_v';

		$target_url = get_option("wuss_product_page_url");
		$has_question = strpos($target_url, '?') > 0;

		foreach ($this->games->posts as $post)
		{
			$img_id = get_post_meta($post->ID,($horizontal ? '_wuss_wide_banner_value_key' : '_wuss_tall_banner_value_key'),true);
			if (is_numeric($img_id))
				$img_src = wp_get_attachment_url($img_id);
			else
				$img_src = '';
			$result .= '<div class="imgbanner'.$style.'" style="background-image: url('.$img_src.')">'
			           . '<a href="'.$target_url.($has_question ? '&' : '?')."gid={$post->ID}".'">'
			           . '<div class="imgbannerlayout'.$style.'"></div>'
			           . '<div class="imgbannertext'.$style.'">'

			           . '<p>'.$post->post_title.'</p>'
			           . '</div></a>'
			           . '</div>';
		}
		return $result;
	}

}
