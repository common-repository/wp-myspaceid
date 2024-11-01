<?php
/**
 * presentation.php
 *
 * UI code for the myspaceid plugin.
 */

if (myspaceid_check_version()) {
	add_filter( 'get_avatar', 'myspaceid_get_avatar', 10, 4 );

	add_action( 'admin_footer', 'myspaceid_render_login_state', 12);
	add_action( 'admin_head', 'myspaceid_style' );
	/* add_action( 'admin_head', 'myspaceid_js' ); */

	add_action( 'wp_footer', 'myspaceid_render_login_state' );
	add_action( 'wp_head', 'myspaceid_style' );
	/* add_action( 'wp_head', 'myspaceid_js' ); */
}

add_action('admin_menu', 'myspaceid_menu');
add_action( 'init', 'myspaceid_init' );
	
function myspaceid_init() {
	wp_enqueue_script('jquery-1.3', '/wp-content/plugins/wp-myspaceid/jquery-1.3.1.min.js', array(), '1.3.1');
	wp_enqueue_script('msjquery', '/wp-content/plugins/wp-myspaceid/msjquery.js', array('jquery-1.3'), '1.3.1');
	wp_enqueue_script('bottombar', '/wp-content/plugins/wp-myspaceid/msid_bottombar.js', array('msjquery'), '1.0');           
	wp_enqueue_script('myspaceid', '/wp-content/plugins/wp-myspaceid/myspaceid.rev.0.js', array(), '1.0');
	wp_enqueue_script('comments', '/wp-content/plugins/wp-myspaceid/comments.js', array('msjquery', 'myspaceid'), '1.0');           
}

function myspaceid_js() {
?>	
	<script type='text/javascript'>
	//<![CDATA[
	/* msJQuery(document).ready(function($) { */
	/* 		$("#btnupdatestatus").click(function() { */
	/* 				$("#btnupdatestatus").val("updating..."); */
	/* 				args = MySpaceID.Util.urlencode($("#txtstatus").val()) + '&' */
	/* 					+ MySpaceID.Util.urlencode($("#selmood").val()); */
	/* 				$.get("/index.php", { myspace_update: MySpaceID.Util.urlencode(args) }, */
	/* 					  function(data, textStatus) { */
	/* 						  $("#btnupdatestatus").val("updated!"); */
	/* 						  setTimeout(function() { */
	/* 								  $("#btnupdatestatus").val("update"); */
	/* 							  }, 3000); */
	/* 					  }); */
	/* 				return false; */
	/* 			}); */
	/* 	}); */
	/* function delayTimer(delay){ */
	/* 	var timer; */
	/* 	return function(fn){ */
	/* 		timer=clearTimeout(timer); */
	/* 		if(fn) */
	/* 			timer=setTimeout(function(){ */
	/* 					fn(); */
	/* 				},delay); */
	/* 		return timer; */
	/* 	} */
	/* } */
	//]]>
	</script>
<?php	
}     

function myspaceid_menu() {
	add_options_page('MySpaceID Options', 'MySpaceID', 8, __FILE__, 'myspaceid_options');
}

function myspaceid_options() {

    // variables for the field and option names 
    /* $opt_name = 'mt_favorite_food'; */
    $hidden_field_name = 'mt_submit_hidden';
    $data_field_name = 'mt_favorite_food';

    // Read in existing option value from database
	$consumer_key = get_option(MYSPACEID_APP_KEY_OPTION);
	$consumer_secret = get_option(MYSPACEID_APP_SECRET_OPTION);
    /* $opt_val = get_option( $opt_name ); */

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read their posted value
		$consumer_key = $_POST[ MYSPACEID_APP_KEY_OPTION ];
		$consumer_secret = $_POST[ MYSPACEID_APP_SECRET_OPTION ];
        /* $opt_val = $_POST[ $data_field_name ]; */

        // Save the posted value in the database
        update_option( MYSPACEID_APP_KEY_OPTION, $consumer_key );
        update_option( MYSPACEID_APP_SECRET_OPTION, $consumer_secret );
        /* update_option( $opt_name, $opt_val ); */

        // Put an options updated message on the screen

?>
<div class="updated"><p><strong><?php _e('Options saved.', 'myspaceid_trans_domain' ); ?></strong></p></div>
<?php

    }

    // Now display the options editing screen

    echo '<div class="wrap">';

    // header

    echo "<h2> " . __( 'MySpaceID Plugin Options', 'myspaceid_trans_domain' ) . "</h2>";

    // options form

	if (myspaceid_check_version()) {
    
    ?>


	
<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
<p><img src="/myspaceid.png"></p>

	<p>Please enter the Application Consumer Key and Secret from your MySpaceID application page.  See <a href='http://wiki.developer.myspace.com/index.php?title=How_to_Set_Up_a_New_Application_for_OpenID'>here</a> for step by step instructions for creating a MySpaceID application.</p>
	<table class="form-table optiontable editform">
		<tbody>
			<tr valign="top">
				<th scope="row"><?php _e("Consumer Key:", 'myspaceid_trans_domain' ); ?> </th>
				<td><input type="text" name="<?php echo MYSPACEID_APP_KEY_OPTION; ?>" value="<?php echo $consumer_key; ?>" size="35"></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e("Consumer Secret:", 'myspaceid_trans_domain' ); ?></th>
				<td><input type="text" name="<?php echo MYSPACEID_APP_SECRET_OPTION; ?>" value="<?php echo $consumer_secret; ?>" size="35"></td>
			</tr>
		</tbody>
	</table>

<p class="submit">
<input class="button-primary" type="submit" name="Submit" value="<?php _e('Update Options', 'myspaceid_trans_domain' ) ?>" />
</p>
		</form>
	<?php
	} else {
		echo 'This plug-in requires PHP version 5 or higher.<br/><hr/>';
		phpinfo();
	}

	echo '</div>';
}

function myspaceid_get_avatar($avatar, $id_or_email, $size, $default) {
	if (!is_object($id_or_email)) {
		return $avatar;
	}

	$userid = $id_or_email->user_id;
	$avatar_url = get_usermeta($userid, 'ms_avatar_url');
	$link_url = myspaceid_get_link_url($userid);
	if ( $avatar_url && $link_url ) {
		return "<a href='{$link_url}' class='gravatar'><img class='avatar avatar-32 photo avatar-default' src='{$avatar_url}' height=32></img></a>";
	} else {
		return $avatar;
	}
}

function myspaceid_style() {
	$css_file = 'profile.css';
	$css_path = myspaceid_plugin_url() . '/' . $css_file;

	echo '
		<link rel="stylesheet" type="text/css" href="'.clean_url($css_path).'" />
		<!-- load jquery here -->';
}

function myspaceid_render_login_state() {
	myspaceid_require_once('MySpaceID/myspace.php');

	$user = wp_get_current_user();
	if ( !$user ) {
		echo '<!-- no current user -->';
		return;
	}
	$userid = get_usermeta($user->id, 'ms_userid');
	if ( !$userid ) {
		echo '<!-- no myspace userid -->';
		return;
	}

	$avatar_url = get_usermeta($user->id, 'ms_avatar_url');
	$access_key = get_usermeta($user->id, 'ms_access_key');
	$access_secret = get_usermeta($user->id, 'ms_access_secret');
	$status = "";
	$mood_url = "";
	$name = $user->display_name;
	if ( $access_key && $access_secret ) {
		$ms = new MySpace(get_option(MYSPACEID_APP_KEY_OPTION), get_option(MYSPACEID_APP_SECRET_OPTION), $access_key, $access_secret);
		$stat_obj = $ms->getStatus($userid);
		$cur_status = $stat_obj->status;
		$cur_mood = $stat_obj->mood;
		$mood_url = $stat_obj->moodImageUrl;
		$name = $stat_obj->user->name;
		$moods_obj = $ms->getMoods($userid);
		$moods = $moods_obj->moods;
		$title = single_post_title('', false);
		$link = get_permalink();
		if ($title == '') {
			$title = get_bloginfo('name');
			$link = get_bloginfo('wpurl');
		}
		$status = "Now reading \"$title\" at $link";
		debug_log($status);
		
		echo '<link type="text/css" rel="stylesheet" href="/wp-content/plugins/wp-myspaceid/msid_bottombar.css" />';

?>
	<div id="msid-bottombar">
	<form action="#">
		<div id="msid-formarea">
		<?php
		echo "<input id='txtstatus' type='text' size='70' value='$status'></input>";
		?>
			<label>Mood:</label> 
			<select id='selmood'>
	<?php
	foreach ($moods as $mood) {
		$name = $mood->moodName;
		$mood_id = $mood->moodId;
		if ($name == $cur_mood) 
			echo "<option selected value='$mood_id'>$name</option>";
		else
			echo "<option value='$mood_id'>$name</option>";
	}
	?>
			</select> &nbsp;
			<input id="btnupdatestatus" type="button" value="update" />
		</div>
		
		<div id="msid-icons">
			<a id="msid-mdpicon" href="#"></a>
		</div>
		<div id="msid-controls">
			<a id="msid-arrowtoggle" class="msid-rightarrows" href="#"></a>
		</div>
		
		<div id="msid-roundedge"> <div>
	</form>
	</div>
	<!-- Useful Variables: $avatar_url, $name, $status, $mood_url -->
<?php
  }
}

?>