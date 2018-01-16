<?php

class WussUsers
{

	var $users;

	var $filter_list;

	public function __construct()
	{
		$this->users	= $this->GetAllUsers();
	}

	public function FilterDropDown($filter)
	{
		$filter = strtoupper($filter);
		$characters = explode(',','A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z');
		$output = '<select name="ufilter" onchange="submit()"><option value=""'
		          .($filter == "" ? ' selected' : '')
		          .'>NONE</option>';

		$output .= '<option value="0"' . ($filter == "0" ? ' selected' : '') .'>NUMERIC</option>';

		foreach($characters as $char)
			$output .= '<option value="'.$char.'"'. ($filter == $char ? ' selected' : '') .">$char</option>";
		$output .= '</select>';

		$this->filter_list = $output;
		return $output;
	}

	public function UserCount($filter = "")
	{
		if (null === $this->users)
			return 0;

		if ($filter == "")
			return count($this->users);

		$result = 0;
		foreach($this->users as $user)
			if (strpos($user->nickname, $filter) == 0)
				$result++;

		return $result;
	}

	function GetAllUsers()
	{
		global $wpdb;
		$query = $wpdb->prepare(
			"
            SELECT key1.ID, key1.user_login, key1.display_name 
            FROM $wpdb->users key1
            INNER JOIN $wpdb->usermeta key2
            ON key1.ID = key2.user_id
            AND key2.meta_key = %s
            ORDER BY key1.user_login
            ",
			'nickname');

		$results = $wpdb->get_results($query);

		if ($results)
			return $results;
		else
			return null;
	}


	//look for specific user in database. If found return his ID else return -1
	public function GetSingleUserByName($key)
	{
		global $wpdb;
		$query =
			"
            SELECT key1.ID, key1.user_login, key1.display_name 
            FROM $wpdb->users key1
            INNER JOIN $wpdb->usermeta key2
            ON key1.ID = key2.user_id
            AND (key2.meta_key = %s
            OR key2.meta_key = %s)
            WHERE (key2.meta_value = '$key'
			OR key1.user_login = '$key')
            ";

		$query = $wpdb->prepare($query,
			array('nickname', 'first_name')
			);
		$user = $wpdb->get_row($query);

		if (!$user)
			return -1;
		return $user->ID;
	}

	public function SearchField($gid)
	{
		$output = "<form method=POST>"
		          . "<input type='text' name='wuss_find_user'>"
		          . "<input type='submit' class='button-secondary inputbutton' name='wuss_user_action' value='Search'>"
		          . '<input type="hidden" name="gid" value="' . $gid . '">'
		          . '<input type="hidden" name="menu_tab" value="' . MENUTAB . '">'
		          . "</form>";
		return $output;
	}

	//in case we don't find the user we are looking for, select the first user from the query
	function __fallbackUser()
	{
		$usercount = $this->UserCount();
		if ($usercount == 0)
			return -1;

		if (null != $this->users[0])
			return $this->users[0]->ID;

		if ( null == $this->users[0] && $usercount > 1 )
				return $this->users[1]->ID;
		return -1;
	}

	public function DropDownList($gid, &$uid, $filter = '', $zero_user = "", $extra_inputs = null) {
		$user_found   = false;
		$new_uid      = - 1;
		$extra_fields = '';
		$opening      = '<select onchange="submit()" name="uid">';
		if ( null != $extra_inputs ):

			foreach ( $extra_inputs as $key => $val ) {
				$extra_fields .= "<input type=hidden name=\"$key\" value=\"$val\">";
			}
		endif;

		$output[] = $opening;
		if ( null != $this->users ) :

			$new_uid = $this->__fallbackUser();

			foreach ( $this->users as $user ) {
				if ( null == $user ) {
					$username = $zero_user;
				} else {
					$username = $user->user_login;
				}
				if ( $username == "" )
					continue;


				if ( $filter != '' && null != $user ) :
					$pass = false;
					if ( is_numeric( $filter ) && is_numeric( $user->user_login [0] )) {
						$pass = true;
					}
					else
					{
						$test1 = substr( strtoupper( $user->user_login ), 0, 1);
						$test2 = substr( strtoupper( $user->display_name ), 0, 1);
						$test3 = substr( strtoupper( $user->nickname ), 0, 1);
						if ($test1  == $filter || $test2 == $filter || $test3 == $filter )
						$pass = true;
					}

					if ( ! $pass )
						continue;

				endif;

				if ( null != $user && $user->ID == $uid ) {
					$user_found = true;
				}

				$output[] = '<option value="'
				            . ( null == $user ? 0 : $user->ID )
				            . '" '
				            . ( ( null != $user && $user->ID == $uid ) ? 'selected' : '' )
				            . ">$username</option>";
			}
		endif;

		if (count($output) == 1) {
			$output = null;
			$output[] = $opening;
			$output[] = '<option value="">Nobody</option>';
			$new_uid = -1;
			$user_found = false;
		}

		if(!$user_found)
			$uid = $new_uid;

		$output[] = '</select>'
		            . '<input type="hidden" name="gid" value="' . $gid . '">'
		            . '<input type="hidden" name="menu_tab" value="' . MENUTAB . '">'
		            . $extra_fields;
		return implode('\n',$output);
	}

	public function BanUser($uid, $gid)
	{
		$this->SetStatus($uid, $gid, 2);
	}

	public function SuspendUser($uid, $gid, $minutes, $hours, $days)
	{
		$duration  = $minutes * 60;
		$duration += $hours * 3600;
		$duration += $days * 86400;
		$this->SetStatus($uid, $gid, 1);
		update_user_meta($uid, "{$gid}_suspension_date", time() + $duration);
	}

	public function SetStatus($uid, $gid, $status = 0)
	{
		$field = $gid."_account_status";
		update_user_meta($uid, $field, $status);
	}

	public function GetAccountStatus($uid,$gid)
	{
		$status = get_user_meta($uid, $gid."_account_status",true);
		if ($status == "")
			$this->SetStatus($uid,$gid, 0);
		if ($status == 1)
		{
			if (time() > get_user_meta($uid, $gid."_suspension_date",true))
			{
				$this->SetStatus($uid,$gid, 0);
				$status = 0;
			}
		}
		return $status;
	}

	public function DisplayAccountActions($gid, $uid)
	{
		global $wpdb;
		$query = "SELECT ID,user_login,user_email,user_url,user_registered,display_name FROM $wpdb->users WHERE ID = $uid";
		$user = $wpdb->get_row($query);

		$uid = $user->ID;
		if ($uid == 0) return '';

		$status = $this->GetAccountStatus($uid,$gid);

		$output = '<div class="actionslist"><form method="post">'
		          . '<a href="user-edit.php?user_id=' . $uid . '">View user account... </a>'
		          . "&nbsp;<strong>User:</strong> <em>\"$user->user_login\"</em> <strong>Name:</strong> <em>\"".get_user_meta($uid, 'first_name',true)."\"</em><br>"
		          . '<input type="hidden" name="menu_tab" value="' . MENUTAB . '">'
		          . '<input type="hidden" name="gid" value="' . $gid . '">'
		          . '<input type="hidden" name="uid" value="' . $uid . '">';
		if ($status == 0):

			$output .= '<input class="button-primary" type="submit" name="wuss_user_action" value="Ban"> '
			           .  '<input class="button-primary" type="submit" name="wuss_user_action" value="Suspend for"> '
			           .  '<input type="number" name="suspend_days" min=0 max=356 value=0> days '
			           .  '<input type="number" name="suspend_hours" min=0 max=23 value=0> hours '
			           .  '<input type="number" name="suspend_minutes" min=0 max=59 value=0> minutes ';

		else:
			$output .= '<input class="button-primary" type="submit" name="wuss_user_action" value="Lift ban / suspension"> ';

			if ($status == 1)
			{
				$final_time = get_user_meta($user->ID, $gid."_suspension_date",true);
				$remainder = $final_time - time();

				if ($remainder > 0)
				{
					$days = floor($remainder / (24*60*60));
					$hours = floor(($remainder - ($days*24*60*60)) / (60*60));
					$minutes = floor(($remainder - ($days*24*60*60)-($hours*60*60)) / 60);
					$seconds = ($remainder - ($days*24*60*60) - ($hours*60*60) - ($minutes*60)) % 60;

					if ($days > 0) $output .= "$days days ";
					if ($hours > 0) $output .= "$hours hours ";
					if ($minutes > 0) $output .= "$minutes minutes ";
					$output .= "$seconds seconds left";
				}
			}
		endif;

		$output .= '</form></div>';
		return $output;
	}

}