<?php
/*  Copyright 2009  Christopher B. Baker (cbaker@myspace.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
  Plugin Name: MySpaceID
  Author: Christopher B. Baker and Steven Ng
  Plugin URI: http://wordpress.org/extend/plugins/wp-myspaceid
  Description: Allows users to login to your site using their MySpace credentials.
  Version: 1.6
*/

define('MYSPACEID_APP_KEY_OPTION', 'myspaceid_app_key_option');
define('MYSPACEID_APP_SECRET_OPTION', 'myspaceid_app_secret_option');

function myspaceid_check_version() {
	return floatval(phpversion()) >= 5.0;
}

require_once('presentation.php');
require_once('login.php');
require_once('comments.php');

if (!myspaceid_check_version())
	return;

add_action('post_user_add', 'myspaceid_update_usermeta', 10, 2);

add_filter('openid_auth_request_extensions', 'myspaceid_add_oauth_extension', 10, 2);
add_filter( 'openid_user_data', 'myspaceid_get_user_data_oauth', 8, 2);

/* add_action('openid_finish_auth', 'myspaceid_finish_login', 10, 2); */
add_action('openid_finish_auth', 'myspaceid_finish_auth', 8, 2);

add_action('parse_request', 'myspaceid_parse_request');
add_action('query_vars', 'myspaceid_query_vars');
add_action('generate_rewrite_rules', 'myspaceid_rewrite_rules');

function myspaceid_require_once() {
	set_include_path( dirname(__FILE__) . '/myspaceid-php-sdk/source' . PATH_SEPARATOR . get_include_path() );
	/* must search openid libs first, otherwise we redefine classes--cbb 5/15/09 */
	set_include_path( dirname(__FILE__) . '/../openid' . PATH_SEPARATOR . get_include_path() );
	$paths = func_get_args();
	foreach ($paths as $path)
		require_once($path);
	restore_include_path();
}

function myspaceid_add_oauth_extension($extensions, $auth_request) {
	debug_log("myspaceid_add_oauth_extension");
	if ( myspaceid_is_myspaceid($auth_request->endpoint->server_url) ) {
		myspaceid_require_once('Auth/OpenID/OAuth.php');

		if ($auth_request->endpoint->usesExtension(Auth_OpenID_OAUTH_NS_URI)) {
			debug_log("myspaceid_add_oauth_extension: adding extension");
			$extensions[] = new Auth_OpenID_OAuthRequest(get_option(MYSPACEID_APP_KEY_OPTION));
		}
	}

	return $extensions;
}

function myspaceid_user_data($new = null) {
	static $data;
	return ($new == null) ? $data : $data = $new;
}

function myspaceid_finish_auth($identity_url, $action) {
	debug_log("myspaceid_finish_auth: entered");

	myspaceid_require_once('Auth/OpenID/OAuth.php', 'MySpaceID/myspace.php');
	$response = openid_response();
	if (!is_a($response, 'Auth_OpenID_SuccessResponse')) {
		dump_var_log($response, "myspaceid_finish_auth got bad response: ");
		return myspaceid_finish_login('', $action);
	}
	$oauth_resp = Auth_OpenID_OAuthResponse::fromSuccessResponse($response);
	$authorized_request_token = $oauth_resp->authorized_request_token;
	if ($authorized_request_token){
		debug_log("myspaceid_finish_auth: got request token");
		$consumer_key = get_option(MYSPACEID_APP_KEY_OPTION);
		$consumer_secret = get_option(MYSPACEID_APP_SECRET_OPTION);
		$ms = new MySpace($consumer_key, $consumer_secret, $authorized_request_token->key, $authorized_request_token->secret);
		$access_token = $ms->getAccessToken();

		$ms = new MySpace($consumer_key, $consumer_secret, $access_token->key, $access_token->secret);
		$userid = $ms->getCurrentUserId();
		$profile_data = $ms->getProfile($userid);
		$name = $profile_data->basicprofile->name;
		$data = array('nickname'        => $name,
					  'user_nicename'   => $name,
					  'display_name'    => $name,
					  'user_url'		=> $identity_url . '?utm_source=Wordpress&utm_medium=MID&utm_campaign=WPPlugIN',
					  'ms_userid'       => $userid,
					  'ms_avatar_url'   => $profile_data->basicprofile->image,
					  'ms_access_token' => $access_token);

		myspaceid_user_data($data);

		$wp_id = get_user_by_openid($auth_request->endpoint->claimed_id);
		if ($wp_id) {
			debug_log("myspaceid_finish_auth: got wordpress id: $wp_id");
			myspaceid_update_usermeta($wp_id, $data);
		}
	}

	myspaceid_finish_login($identity_url, $action);
}

function myspaceid_get_user_data_oauth($data, $identity_url) {
	debug_log("myspaceid_get_user_data_oauth: entered");
	$saved = myspaceid_user_data();
	if ($saved) {
		foreach($saved as $key => $value) {
			$data[$key] = $value;
		}
	}

	return $data;
}

function myspaceid_update_usermeta($user_id, $data) {
	debug_log("myspaceid_update_usermeta");
	update_usermeta($user_id, 'ms_userid', $data['ms_userid']);
	update_usermeta($user_id, 'ms_avatar_url', $data['ms_avatar_url']);
	update_usermeta($user_id, 'ms_access_key', $data['ms_access_token']->key);
	update_usermeta($user_id, 'ms_access_secret', $data['ms_access_token']->secret);
	debug_log("myspaceid_update_usermeta done");
}

function myspaceid_is_configured() {
	$app_key = get_option(MYSPACEID_APP_KEY_OPTION);
	$app_secret = get_option(MYSPACEID_APP_SECRET_OPTION);
	return !empty($app_key) && !empty($app_secret);
}

function myspaceid_is_myspaceid($server_url) {
	$url = parse_url($server_url);
	$host = $url['host'];
	$match = preg_match('/myspace\.com/', $host);
	return $match;
}

/**
 * Add rewrite rules to WP_Rewrite for the OpenID services.
 */
function myspaceid_rewrite_rules($wp_rewrite) {
	$myspaceid_rules = array(
							 openid_service_url('myspaceid', '(.+)', null, false) => 'index.php?myspaceid=$matches[1]',
							 openid_service_url('myspace_update', '(.+)', null, false) => 'index.php?myspace_update=$matches[1]',
						  );

	$wp_rewrite->rules = $myspaceid_rules + $wp_rewrite->rules;
	dump_var_log($wp_rewrite->rules, "myspaceid_rewrite_rules: ");
}


/**
 * Add valid query vars to WordPress for OpenID.
 */
function myspaceid_query_vars($vars) {
	$vars[] = 'myspaceid';
	$vars[] = 'myspace_update';
	return $vars;
}

function myspaceid_parse_request($wp) {
	$vars = count($wp->query_vars);
	debug_log("myspaceid_parse_request: $vars");

	if (array_key_exists('myspaceid', $wp->query_vars)) {
		debug_log("myspaceid_parse_request: key exists");

		openid_clean_request();

		switch ($wp->query_vars['myspaceid']) {
		case 'consumer':
			debug_log("myspaceid_parse_request: consumer action");
			@session_start();

			$action = 'popup_login';

			// no action, which probably means OP-initiated login.  Set
			// action to 'login', and redirect to home page when finished
			/* if (empty($action)) { */
			/* 	$action = 'popup_login'; */
			/* 	if (empty($_SESSION['openid_finish_url'])) { */
			/* 		$_SESSION['openid_finish_url'] = get_option('home'); */
			/* 	} */
			/* } */

			finish_openid($action);
			break;
		}
	} elseif (array_key_exists('myspace_update', $wp->query_vars)) {
		$updates = urldecode($wp->query_vars['myspace_update']);
		debug_log("myspaceid_parse_request: found $updates");
		$params = explode('&', $updates);
		debug_log("myspaceid_parse_request: $params[0],$params[1]");
		$status = urldecode($params[0]);
		$mood_id = urldecode($params[1]);
		if (myspaceid_update_status($status, $mood_id))
			exit(0);

		exit(1);
	}
}

function myspaceid_update_status($status, $mood_id) {
	myspaceid_require_once('MySpaceID/myspace.php');
	debug_log("myspaceid_update_status: $status, $mood_id");
	$user = wp_get_current_user();
	$userid = get_usermeta($user->id, 'ms_userid');
	$access_key = get_usermeta($user->id, 'ms_access_key');
	$access_secret = get_usermeta($user->id, 'ms_access_secret');
	if ( $access_key && $access_secret ) {
		debug_log("myspaceid_update_status: updating");
		$ms = new MySpace(get_option(MYSPACEID_APP_KEY_OPTION), get_option(MYSPACEID_APP_SECRET_OPTION), $access_key, $access_secret);
		debug_log("myspaceid_update_status: updating $userid status with '$status'");
		return $ms->updateStatusMood($userid, $status, $mood_id);
	}
	return false;
}

function myspaceid_finish_login($identity_url, $action) {
	if ($action != 'popup_login') return;

	debug_log("myspaceid_finish_login");
	$redirect_to = $_SESSION['openid_finish_url'];
		
	if (empty($identity_url)) {
		$url = get_option('siteurl') . '/wp-login.php?openid_error=' . urlencode(openid_message());
		myspaceid_generate_finish_page($url);
		exit;
	}
		
	openid_set_current_user($identity_url);

	if (!is_user_logged_in()) {
		if ( get_option('users_can_register') ) {
			$user_data =& openid_get_user_data($identity_url);
			$user = myspaceid_create_new_user($identity_url, $user_data);
			openid_set_current_user($user->ID);
		} else {
			// TODO - Start a registration loop in WPMU.
			$url = get_option('siteurl') . '/wp-login.php?registration_closed=1';
			myspaceid_generate_finish_page($url);
			exit;
		}

	}
		
	if (empty($redirect_to)) {
		$redirect_to = 'wp-admin/';
	}
	if ($redirect_to == 'wp-admin/') {
		if (!current_user_can('edit_posts')) {
			$redirect_to .= 'profile.php';
		}
	}
	if (!preg_match('#^(http|\/)#', $redirect_to)) {
		$wpp = parse_url(get_option('siteurl'));
		$redirect_to = $wpp['path'] . '/' . $redirect_to;
	}

	myspaceid_generate_finish_page( $redirect_to );
	exit;
}

function myspaceid_generate_finish_page($redirect_url) {
	echo <<< FINISH_PAGE
		<script type="text/javascript">
	function closeWin() {
		window.opener.success('$redirect_url');
		self.close();
	}
	closeWin();
	</script>
FINISH_PAGE;
}

/**
 * Get myspaceid plugin URL, keeping in mind that for WordPress MU, it may be in either the normal
 * plugins directory or mu-plugins.
 */
function myspaceid_plugin_url() {
	static $myspaceid_plugin_url;

	if (!$myspaceid_plugin_url) {
		if (defined('MUPLUGINDIR') && file_exists(ABSPATH . MUPLUGINDIR . '/wp-myspaceid')) {
			$myspaceid_plugin_url =  trailingslashit(get_option('siteurl')) . MUPLUGINDIR . '/wp-myspaceid';
		} else {
			$myspaceid_plugin_url =  plugins_url('wp-myspaceid');
		}
	}

	return $myspaceid_plugin_url;
}

function myspaceid_get_user_myspaceid($id_or_name) {
	$openids = get_user_openids($id_or_name);
	foreach ($openids as $openid) {
		if (myspaceid_is_myspaceid($openid))
			return $openid;
	}
}

function myspaceid_is_user_myspaceid() {
	$openids = get_user_openids($id_or_name);
	foreach ($openids as $openid) {
		if (myspaceid_is_myspaceid($openid))
			return true;
	}

	return false;
}

function myspaceid_get_link_url($id_or_name) {
	$openid = myspaceid_get_user_myspaceid($id_or_name);
	return $openid . '?utm_source=Wordpress&utm_medium=MID&utm_campaign=WPPlugIN';
}

function myspaceid_create_new_user($identity_url, &$user_data) {
	global $wpdb;

	// Identity URL is new, so create a user
	@include_once( ABSPATH . 'wp-admin/upgrade-functions.php');	// 2.1
	@include_once( ABSPATH . WPINC . '/registration-functions.php'); // 2.0.4

	// use email address for username if URL is from emailtoid.net
	if (null != $_SESSION['openid_login_email'] and strpos($identity_url, 'http://emailtoid.net/') === 0) {
		if (empty($user_data['user_email'])) {
			$user_data['user_email'] = $_SESSION['openid_login_email'];
		}
		$username = openid_generate_new_username($_SESSION['openid_login_email']);
		unset($_SESSION['openid_login_email']);
	}

	// otherwise, try to use preferred username
	if (empty($username) && $user_data['nickname']) {
		$username = openid_generate_new_username($user_data['nickname'], false);
	}

	// finally, build username from OpenID URL
	if (empty($username)) {
		$username = openid_generate_new_username($identity_url);
	}

	$user_data['user_login'] = $username;
	$user_data['user_pass'] = substr( md5( uniqid( microtime() ) ), 0, 7);
	$user_id = wp_insert_user( $user_data );

	if( $user_id ) { // created ok

		$user_data['ID'] = $user_id;
		// XXX this all looks redundant, see openid_set_current_user

		$user = new WP_User( $user_id );

		if( ! wp_login( $user->user_login, $user_data['user_pass'] ) ) {
			openid_message(__('User was created fine, but wp_login() for the new user failed. This is probably a bug.', 'openid'));
			openid_status('error');
			openid_error(openid_message());
			return;
		}

		// notify of user creation
		wp_new_user_notification( $user->user_login );

		wp_clearcookie();
		wp_setcookie( $user->user_login, md5($user->user_pass), true, '', '', true );

		// Bind the provided identity to the just-created user
		openid_add_user_identity($user_id, $identity_url);

		do_action('post_user_add', $user_id, $user_data);

		openid_status('redirect');

		if ( !$user->has_cap('edit_posts') ) $redirect_to = '/wp-admin/profile.php';

	} else {
		// failed to create user for some reason.
		openid_message(__('OpenID authentication successful, but failed to create WordPress user. This is probably a bug.', 'openid'));
		openid_status('error');
		openid_error(openid_message());
	}

}

function debug_log($text) {
	$log_path = dirname(__FILE__) . '/debug.log';
	/* file_put_contents($log_path, $text . "\n", FILE_APPEND); */
}

function dump_var_log($var, $text = "") {
	debug_log($text . var_export($var, true));
}


?>