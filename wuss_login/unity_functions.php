<?php
session_start();

	//NOTE ON USAGE:
	//This file should be considered a DLL of sorts.
	//Unity has functions that map directly to these functions and they are designed to provide feedback to those specfic functions.
	//As such, functions have specific names and provide custom responses to satisfy the calling function.
	//If you need to modify anything in here, please be sure not to change the function names and be careful with the data being returned.
	include_once(dirname(__FILE__) . "/../wuss_login/settings.php");

	//log in a non logged in user if valid details are supplied
	function loginDoLogin() {
		global $current_user;
		$result = '';
		$username = (is_user_logged_in()) ? $current_user->user_login :	Posted("username");
		if (empty($username))
		{
			$result .= PrintError("No username provided");
			SendToUnity($result);
			die();
		}

		if (is_user_logged_in())
		{		
			$status = WULAccountStatus();
			switch($status)
			{
				case 0:
					$current_user = wp_get_current_user();
					SendBackFields();
					break;
				
				case 1:
					$result .= PrintError("Your account has been suspended. If you feel this is an error, please contact support");
					SendToUnity($result);
					break;
		
				case 2:
					$result .= PrintError("Your account has been banned. If you feel this is an error, please contact support");	
					SendToUnity($result);
					break;	
			}
			return true;
		}

		$creds = array();
		$creds['user_login'] = $username;
		$creds['user_password'] = Posted("password");
		$creds['remember'] = true;
		$user = wp_signon( $creds, false );
	
		if ( is_wp_error($user) )
		{
			$result .= PrintError( str_replace("_", " ", $user->get_error_code() ) );
			SendToUnity($result);
		}
		else
		{
			wp_set_current_user($user->ID);
			$current_user = wp_get_current_user();
			loginVerifyLogin();
		}
	}
	
	//create an account via the Unity game
	function loginSubmitRegistration()
	{
		if (is_user_logged_in())
		{
			SendToUnity( PrintError("Please log out before creating a new account") );
			return;
		}
	
		$username = Posted("username");
		if (empty($username))
		{
			SendToUnity( PrintError("No username provided") );
			die();
		}
	
		$email = Posted("email");
		$passw = Posted("password");
	
		if ($email == "")
		{
			SendToUnity( PrintError("Email is required") );
			return;
		}
		
		if (!is_email($email))
		{
			SendToUnity( PrintError("Email is not formatted correctly") );
			return;
		}
	
		if ($passw == "")
		{
			SendToUnity( PrintError("Password cannot be empty") );
			return;
		}

		$UID = username_exists( $username );
		if ( !$UID )
		{
			if (validate_username( $username )) {
		      	$UID = wp_create_user( $username, $passw, $email );

				if (is_wp_error($UID))
				{
					$result = PrintError("Failed to create account: ".$UID->get_error_message() );
				} else {
					$result = SendField("success","true");
					//generate the initial nickname from the email address, not the username
					//other people need not know what the player's account login details are...
					update_user_meta($UID,"nickname", substr($email,0,strpos($email,"@")));
				}
			} else {
				$result = PrintError("Username is not valid. Please select another");
			}
		}
		else
		{
			$result = PrintError("Username already in use");
		}
		SendToUnity($result);
	}

	//just make sure the person is in fact logged in
	function loginVerifyLogin()
	{
		global $current_user;
		$result = '';
		if (!is_user_logged_in()) 
		{
			$result .= PrintError("Not logged in");
			SendToUnity($result);
			return false;
		}

		$status = WULAccountStatus();
		switch($status)
		{
			case 0:
				$current_user = wp_get_current_user();
				SendBackFields();
				break;
				
			case 1:
				$result .= PrintError("Your account has been suspended. If you feel this is an error, please contact support");
				SendToUnity($result);
				break;
		
			case 2:
				$result .= PrintError("Your account has been banned. If you feel this is an error, please contact support");	
				SendToUnity($result);
				break;	
		}
	}

	//log out of the wordpress site
	//this basically removes the cookie from the server meaning your saved cookie (if any) will no longer be valid
	function loginLogout()
	{		
		if ( is_user_logged_in() )
		{
			wp_logout();
			wp_set_current_user(0);
		}
		
		SendToUnity( SendField("success", "true") );
		return true;
	}
	
	//Probably the most complicated of all functions in this script. The problem here is that you do not want some
	//random bloke to say he is some other bloke and then request a password change for that user and not give
	//the real account holder a chance to protect himself fro being locked out. So what I do here is I ask Wordpress
	//to first check wether or not this user has authority to request a password reset or wether this functionality
	//has been disabled on the website and if he CAN request a password change, I then send out an email to the 
	//email address on file for the user being request. Thus, if it is the real owner, he will get a password change
	//request via email and he can complete the password reset on the website. If this was some bogus user then
	//the account holder will be made aware of the fact that someone tried to change his password but his password
	//will be left in tact. 
	
	//A password reset can be requested via supplying either an email or a username
	function loginPasswordReset()
	{
		$user_login = sanitize_text_field( Posted('login') );

		if (verifyResetRequest($user_login))
		{
			SendToUnity( SendField("success", "true") );
		} else 
		{
			SendToUnity( SendField("success", "false") );
		}
	}
	
	function verifyResetRequest($user_login) {
	    global $wpdb, $current_site;

	    if ( empty( $user_login) ) {
			SendToUnity( PrintError("No account information provided") );
	        return false;
	    } else if ( strpos( $user_login, '@' ) ) {
	        $user_data = get_user_by( 'email', trim( $user_login ) );
	        if ( empty( $user_data ) )
			{
				SendToUnity( PrintError("No user found matching $user_login") );
				return false;
			}
	    } else {
	        $login = trim($user_login);
	        $user_data = get_user_by('login', $login);
	    }

	    do_action('lostpassword_post');

	    if ( !$user_data )
		{
			SendToUnity( PrintError("No user found with username or email matching $user_login") );
			return false;
		}
	    // redefining user_login ensures we return the right case in the email
	    $user_login = $user_data->user_login;
	    $user_email = $user_data->user_email;

	    do_action('retrieve_password', $user_login);
	
	$allow = apply_filters('allow_password_reset', true, $user_data->ID);

	    if ( ! $allow )
		{
			SendToUnity( PrintError("Password reseting is not allowed for this acount") );
		        return false;
		}
	    else if ( is_wp_error($allow) )
		{
			SendToUnity( PrintError("An error occured while applying the allow_password_reset filter") );
		        return false;
		}
		
	    $key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $user_login));
	    if ( empty($key) ) {
	        // Generate something random for a key...
	        $key = wp_generate_password(20, false);
	        do_action('retrieve_password_key', $user_login, $key);
	        // Now insert the new md5 key into the db
	        $wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $user_login));
	    }
    	$message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
    	$message .= network_home_url( '/' ) . "\r\n\r\n";
    	$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
    	$message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
    	$message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
    	$message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

	    if ( is_multisite() )
	        $blogname = $GLOBALS['current_site']->site_name;
	    else
	        // The blogname option is escaped with esc_html on the way into the database in sanitize_option
	        // we want to reverse this for the plain text arena of emails.
	        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	    $title = sprintf( __('%s Password Reset'), $blogname );

	    $title = apply_filters('retrieve_password_title', $title);
	    $message = apply_filters('retrieve_password_message', $message, $key);

	    if ( $message && !wp_mail($user_email, $title, $message) )
		{
			$response = PrintError("The e-mail could not be sent.\n" . __('Possible reason: your host may have disabled the mail() function...') );
			SendToUnity($response);
			return false;
		}

	    return true;
	}
		
	//This function can only be called while being logged in and can thus only be used on your own account
	//As such, I make sure that you are allowed to change your password (as per the website's own mandate),
	//then I do one final security check to make sure the request isn't coming from a passer by who finds a
	//logged in account unattended. I do this by requesting the current password along with the new password
	//once I have established that you are currently logged in, you are allowed to change the password and
	//you know the correct password for this account, I change your password to what you provided without 
	//first sending you an email to ask you to go do it on the website.
	//This class uses Wordpress's hashing code to verify the password so it needs the class-phpass.php file
	//If you changed the default location of your plugins installations then you will need to update this path...
	function loginPasswordChange()
	{
		//this file is meant to reside in /wp-content/plugins/wuss_unity/ so go back 3 directories
		require_once( dirname(__FILE__) . "/../../../wp-includes/class-phpass.php");

		global $current_user;
		
		$result = '';

		if (!is_user_logged_in())
		{			
			SendToUnity($result . PrintError("User not logged in") );
			return false;
		}
		
		$pass = Posted("password");
		if ($pass == "")
		{
			SendToUnity($result . PrintError("Current password not provided") );
			return;
		}
			
		$newpass = Posted("passnew");
		if ($newpass == "")
		{
			SendToUnity($result . PrintError("New password cannot be empty") );
			return;
		}
		
		$user_data = get_userdata($current_user->ID);
		$hash = $user_data->user_pass;
		$wp_hasher = new PasswordHash(8, TRUE);
		if (!$wp_hasher->CheckPassword($pass, $hash))
		{
			SendToUnity($result . PrintError("Incorrect password") );
			return;
		}
		
	    do_action('lostpassword_post');
	    do_action('retrieve_password', $current_user->user_login);
    	    $allow = apply_filters('allow_password_reset', true, $current_user->ID);

	    if ( ! $allow )
		{
			$result .= PrintError("Password changing is not allowed for this acount. Please contact your web master");
			SendToUnity($result);
		        return false;
		}

		wp_set_password( $newpass, $current_user->ID );
		if ( is_wp_error($result) )
		{
			$result .= PrintError( str_replace("_", " ", $result->get_error_code() ) );
			SendToUnity($result);
			return;
		}

		$result .= SendField("success","true");
		$result .= SendField("logged in", is_user_logged_in() );
		SendToUnity($result);
	}
	
	//This fetches the personal information of the user and sends it back to Unity
	//I kept seeing that you can set/see/use aim, yim and jabber but why exactly those
	//and not others are the defaults I do not know. Either way, they are what is default
	//so they are the ones I provide. ...however... Although the Wordpress documentation
	//does clearly state that when you update a user you can supply those three fields as
	//paramenters, it didn't seem to work... So I add them manually afterwards and
	//fetch them manually here also... Seems it's the default IM entries but they must
	//have depreciated it or something... Either way, I return them still...
	function loginFetchAccountDetails()
	{
		global $current_user;
		
		$result = '';
		if (is_user_logged_in())
		{		
			$result .= SendField("fname", $current_user->user_firstname);		
			$result .= SendField("lname", $current_user->user_lastname);
			$result .= SendField("nname", $current_user->nickname);
			$result .= SendField("dname", $current_user->display_name);
			$result .= SendField("email", $current_user->user_email);		
			$result .= SendField("website", $current_user->user_url);
			$result .= SendField("descr", $current_user->user_description);
			$result .= SendField("aim", get_user_meta( $current_user->ID, 'aim', true ) );
			$result .= SendField("jabber", get_user_meta( $current_user->ID, 'jabber', true ) );
			$result .= SendField("yim", get_user_meta( $current_user->ID, 'yim', true ) );
			SendToUnity($result);
			return true;
		} 
		SendToUnity( PrintError("User not logged in ") );
		return false;
	}
	
	//Here I update your personal details.
	//As per Wordpress rules, you cannot change your username once your account is created
	//but I do allow you to change your nickname and display name. Why exactly Wordpress
	//has a nickname, a displayname and an identity on top of login name and username I do not know
	//I feel it is some serious overkill but in any event, since identity is only available from 
	//WP 3 onwards I am not incuding  it by default and not using it in the Unity project
	//I am only returning and updating display name and nickname while bypassing username alltogether
	function loginUpdateAccountDetails()
	{
		global $current_user;
		
		if (!is_user_logged_in())
		{	
			SendToUnity( PrintError("You are not logged in. Please log in and try again") );
			return;	
		}
		
		if ( !current_user_can( 'edit_user', $current_user->ID ) )
		{
			SendToUnity( PrintError("You are not authorised to update account details on this website. Contact your webmaster for more info") );
			return false;
		}

		
		$fields = Array('ID' => $current_user->ID,
						'description' => base64_decode(Posted("descr")), 'user_url' => Posted("website"),
						'last_name' => Posted("lname"), 'first_name' => Posted("fname"),
						 );
						
		$displayname = Posted("dname");
		$nickname = Posted("nname");
		$email = Posted("email");
		
		if ($email != "" && !is_email($email))
		{
			SendToUnity( PrintError("Email address is incorrectly formatted") );
			return;
		}
				
		if ($displayname != "")
			$fields["display_name"] = $displayname;
			
		if ($nickname != "")
			$fields["nickname"] = $nickname;
			
		if ($email != "")
			$fields["user_email"] = $email;
			
		$result = wp_update_user ($fields );
		if ( is_wp_error($result) )
		{
			$response = PrintError( str_replace("_", " ", $result->get_error_code() ) );
			SendToUnity($response);
			return;
		}
		
		update_user_meta( $current_user->ID, 'aim', Posted("aim") );
		update_user_meta( $current_user->ID, 'yim', Posted("yim") );
		update_user_meta( $current_user->ID, 'jabber', Posted("jabber") );

		SendToUnity( SendField("success", "true") );
	} 
	
	//Fetch the user's avatar string without fetching all the other personal info
	function loginFetchUserEmail()
	{
		global $current_user;
		SendToUnity(	SendField("success", "true") .
				SendField("gravatar", md5(strtolower(trim($current_user->user_email))) ) );
	}

	//The WULogin kit now supports the ability to suspend or ban user accounts
	//This cannot be initiated from the game, though, so this will remain a hidden feature
	//until the games portal package is released... 
	function WULAccountStatus()
	{
		global $current_user;
		$gid = Postedi("gid");
		
		$status = get_user_meta($current_user->ID, $gid."_account_status",true);
		if ($status == "")
			update_user_meta($current_user->ID, $gid."_account_status", "0");
		if ($status == 1)
		{
			if (time() > get_user_meta($current_user->ID, $gid."_suspension_date",true))
			{
				update_user_meta($current_user->ID, $gid."_account_status", "0");
				$status = 0;
			}
		}
		return $status;
	}
	
	//The WULogin kit now supports the ability to fetch any field in the usermeta table
	//simply send it along as a , separated string in the wul_fields variable when loggin in
	function SendBackFields()
	{
		global $current_user;
		$fields = Posted("wul_fields");
		$fields = trim($fields);
		$result = '';

		if (empty($fields))
		{
			$result .= SendField("nickname", $current_user->nickname);
			$result .= SendField("display_name", $current_user->display_name);
		} else 
		{
			$f = explode(',', $fields);
			foreach($f as $field)
			{
				$field_name = trim($field);
				switch($field_name)
				{
					case "user_id": $result .= SendField("uid", $current_user->ID); break;
					case "user_login": $result .= SendField($field_name, $current_user->user_login); break;
					case "display_name": $result .= SendField($field_name, $current_user->display_name); break;
					case "user_email": $result .= SendField($field_name, $current_user->user_email); break;
					case "user_url": $result .= SendField($field_name, $current_user->user_url); break;
					case "user_registered": $result .= SendField($field_name, $current_user->user_registered); break;
					case "roles": $result .= SendField($field_name, implode('|',$current_user->roles)); break;
					default:
						$meta = get_user_meta($current_user->ID, $field_name, true);
						if (is_array($meta)) $meta = implode('|',$meta);
						$result .= SendField($field_name, $meta); break;
				}
			}
			$result .= SendField("status", WULAccountStatus());
		}
		SendToUnity($result);
	}
