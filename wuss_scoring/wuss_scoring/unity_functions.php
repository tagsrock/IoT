<?php

//load this first as this makes sure the WP themes are not processed
include_once(dirname(__FILE__) . "/../wuss_login/settings.php");

function __SubmitScore($uid)
{
	//this function requires that the user be logged in so if not, quit!
	if (!is_user_logged_in())
	{
		SendToUnity(PrintError("You are not logged in. Action Failed"));
		return;
	}

	$gid = Posted("gid");
	$newscore = Postedi("score");

	$current_score = get_user_meta($uid, $gid."_HighScore", true);
	if(empty($current_score)) $current_score = 0;

	$result = SendField("success","true");

	//If the new score is lower than the last one, do not update it
	if ($current_score <= $newscore)
	{
		update_user_meta($uid, $gid."_HighScore", $newscore);
		$result .= SendField("updated", "1");
	}
	SendToUnity($result);
}

function scoringSubmitScoreForUser()
{
	global $wpdb;

	if(isset($_REQUEST['uid']))
	{
		__SubmitScore( Postedi( "uid" ) );
		return;
	}

	$username = Posted("username");
	$uid = $wpdb->get_var("SELECT ID FROM $wpdb->users WHERE user_email = '$username' OR user_login = '$username'");
	if (null === $uid)
	{
		SendToUnity(PrintError("Username {$username} was not found"));
		return;
	}
	__SubmitScore($uid);
}

function scoringSubmitScore()
{
    global $current_user;
 	__SubmitScore($current_user->ID);
}

function scoringFetchScores()
{
    global $wpdb, $current_user;

    //If you set the limit as an option in Wordpress, then default to that
    //but if a limit is passed from the game, consider it an override and use
    //THAT instead. If no limit is found, then hard code it to 20 if no global limit wa specified
    $gid = Posted("gid");
    $limit = get_option('wuss_scoring_results_limit_'.$gid);
    if ($limit == 0 || $limit == '')
        $limit = get_option('wuss_scoring_results_limit_0');
    
    $posted_limit = Postedi("limit");
    if ($posted_limit > 0)
        $limit = $posted_limit;

    if ($limit == 0 || $limit == "")
        $limit = 20;

    $sort_order = (strtolower(Posted('order')) == "asc") ? 'ASC' : 'DESC';

    //now, let's fetch the 20 (or however many you specified) highest scores
    //in order of highest first...
    $query = "SELECT ID, display_name, user_email, meta_value FROM $wpdb->users
	    INNER JOIN $wpdb->usermeta
		ON $wpdb->users.ID = {$wpdb->usermeta}.user_id
		WHERE {$wpdb->usermeta}.meta_key = '{$gid}_HighScore'
		ORDER BY meta_value $sort_order
		LIMIT $limit;";

    $results = $wpdb->get_results($query);

    //if the database is currently empty and no scores were returned, return
    //some default names just so the high scores table isn't empty
    if ($wpdb->num_rows == 0)
    {
        SendEmptyResponse('', $limit);
        return;
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
        $response .= SendNode("person","dname=$row->display_name;nname=$nname;score=$row->meta_value;gravatar=$gravatar");
        if (is_user_logged_in() && $row->ID == $current_user->ID)
            $response .= SendField("highlight", "1");
        $counted++;
    }

    //in the event that your table is not filled up, send over some extra names
    SendEmptyResponse($response, $limit, $counted);
}

function SendEmptyResponse($current, $limit = 20, $counted = 0)
{
    $index = 0;

    //make sure to list enough names here to match how many names you want to display on screen
    $default_names = Array("Ace", "Joe", "Jim", "AAA", "A.A.", "BadBoy", "BillyBob", "myBad", "DrDude",
        "MrDude", "TheDude", "Labowski", "Big Al", "Sally", "May", "Sue", "Dolly",
        "Susan", "Pixie", "Master Chief", "Mistake", "MBS", "D.Va");
    while($counted++ < $limit)
    {
        //since this person does not exist, I created a bogus gravatar value
        //as Gravatar will just return a default image when an email is not found
        $current .= SendNode("person","dname=".$default_names[$index].";nname=".$default_names[$index].";score=0;gravatar=".md5("fake".$index));
        $index++;
    }
    SendToUnity($current);
}
