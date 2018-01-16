<?php

class WussHighscores {

	public function FetchScores($gid, $limit = 20, $sort_order ="DESC")
	{
		global $wpdb, $current_user;

		$query = "SELECT ID, display_name, user_email, meta_value FROM $wpdb->users
	    INNER JOIN $wpdb->usermeta
		ON $wpdb->users.ID = {$wpdb->usermeta}.user_id
		WHERE {$wpdb->usermeta}.meta_key = '{$gid}_HighScore'
		ORDER BY meta_value $sort_order
		LIMIT $limit;";

		$results = $wpdb->get_results($query);

		$response = null;
		//if the database is currently empty and no scores were returned, return
		//some default names just so the high scores table isn't empty
		if ($wpdb->num_rows == 0)
		{
			$this->__addEmptyResponses($response, $limit);
			return $response;
		}

		//if there were results, send them back to Unity
		$counted = 0;
		foreach($results as $row)
		{
			//send the user's display name and nickname so the developer can decide
			//inside unity, which name to show in the high scores table.

			//also, prepare the gravatar parameter so the dev can just send it as is
			//once the data reaches Unity
			$nname = get_user_meta($row->ID, "nickname", true);
			$gravatar = md5(strtolower(trim($row->user_email)));
			$this->__addPersonToResponse($response, $row->display_name, $nname, $row->meta_value, $gravatar, (is_user_logged_in() && $row->ID == $current_user->ID) ? 'true' : 'false');
			$counted++;
		}

		//in the event that your table is not filled up, send over some extra names
		$this->__addEmptyResponses($response, $limit, $counted);
		return $response;
	}

	function __addPersonToResponse(&$current, $dname, $nname, $score, $gravatar, $highlight)
	{
		$current[] = array('dname' => $dname, 'nname' => $nname, 'score' => $score, 'gravatar' => $gravatar, 'highlight' => $highlight);
	}

	function __addEmptyResponses(&$current, $limit = 20, $counted = 0)
	{
		$index = 0;

		//make sure to list enough names here to match how many names you want to display on screen
		$default_names = Array("Ace", "Joe", "Jim", "AAA", "A.A.", "BadBoy", "BillyBob", "myBad", "DrDude",
			"MrDude", "TheDude", "Labowski", "Big Al", "Sally", "May", "Sue", "Dolly",
			"Susan", "Pixie", "Master Chief", "Mistake", "MBS", "D.Va");
		while($counted++ < $limit)
		{
			$this->__addPersonToResponse($current, $default_names[$index], $default_names[$index], 0, md5("fake".$index), 'false');
			$index++;
		}
	}

}