<?php
/**
 * comments.php
 */

add_filter( 'get_comment_author_link', 'myspaceid_comment_author_link', 10);
if ( myspaceid_check_version() && get_option('openid_enable_commentform') ) {
	add_action( 'wp_footer', 'myspaceid_comment_profilelink', 10);
	add_action( 'wp_footer', 'myspaceid_comment_form', 10);
}

function myspaceid_comment_profilelink() {
	global $wp_scripts;

	if ( !is_a($wp_scripts, 'WP_Scripts') ) {
		$wp_scripts = new WP_Scripts();
	}

	if ((is_single() || is_comments_popup()) && myspaceid_is_user_myspaceid() && $wp_scripts->query('comments')) {
		echo '<script type="text/javascript">myspaceid_add_name_style()</script>';
	}
}

function myspaceid_comment_form() {
	global $wp_scripts;

	if ( !is_a($wp_scripts, 'WP_Scripts') ) {
		$wp_scripts = new WP_Scripts();
	}

	if (!is_user_logged_in() && (is_single() || is_comments_popup()) && isset($wp_scripts) && $wp_scripts->query('comments')) {
		myspaceid_wp_login_head(true);
		$button_file = 'myspaceid-php-sdk/source/MySpaceID/images/MySpaceID-loginwith-156x28.png';
		$button_path = myspaceid_plugin_url() . '/' . $button_file;

		echo '<script type="text/javascript">add_myspaceid_to_comment_form("' . $button_path . '")</script>';
	}
}

function myspaceid_comment_author_link($html) {
	if( is_comment_openid() ) {
		if (preg_match('/<a href=[^>]*myspace\.com[^>]* class=[^>]+>/', $html)) {
			return preg_replace( '/(<a[^>]* class=[\'"]?)/', '\\1myspaceid_link ' , $html );
		}
	}
	return $html;
}

?>