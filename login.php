<?php
/**
 * login.php
 *
 * UI code for the login popup
 */

if (myspaceid_check_version()) {
	add_action( 'login_head', 'myspaceid_style');
	add_action( 'login_head', 'myspaceid_wp_login_head');
	add_action( 'login_form', 'myspaceid_wp_login_form');
	add_action( 'register_form', 'myspaceid_wp_register_form', 12);
}

function myspaceid_wp_login_head($no_redirect = null) {
	@session_start();
	/* need better way to do this */
	if (!$no_redirect)
		wp_print_scripts(array('myspaceid', 'msjquery'));
	/* $script_path = myspaceid_plugin_url() . '/myspaceid.rev.0.js'; */
	/* echo "<script type='text/javascript' src='$script_path'></script>"; */

	$return_to = openid_service_url('myspaceid', 'consumer', 'login_post');
	$_SESSION['openid_return_to'] = $return_to;
	$realm = openid_trust_root($return_to);
	$consumer_key = get_option(MYSPACEID_APP_KEY_OPTION);
	/* $redirect_to = get_option('home'); */

?>	
	<script type="text/javascript">
	//<![CDATA[
	msJQuery(document).ready(function($) {
			$('#myspaceid_login_button').click(function () {
					msOptions.realm = '<?php echo $realm; ?>';
					msOptions.returnTo = '<?php echo $return_to; ?>';
					msOptions.consumer = '<?php echo $consumer_key; ?>';
					var ms = new MySpaceID(msOptions);
					ms.logIn();
				});
		})

	function success(redirect_to) {
		//alert(rand);
		<?php
		if ($no_redirect) {
			/* FIXME--save and restore comment! */
			echo 'window.location.reload(true);';
		} else {
			echo 'window.location.href = redirect_to;';
		}
		?>
	}

	function failed(rand) {
		/* $('#login div.logout span').addClass("logout_isSignedout"); */
		/* $('#login div.logout span').removeClass("logout_isSignedin"); */
	}
	//]]>
	</script>
	<?php
}

function myspaceid_wp_login_form() {
	$button_file = 'myspaceid-php-sdk/source/MySpaceID/images/MySpaceID-loginwith-156x28.png';
	$button_path = myspaceid_plugin_url() . '/' . $button_file;
	
	echo <<< LOGIN_FORM_END
		<hr id="openid_split" style="clear: both; margin-bottom: 1.0em; border: 0; border-top: 1px solid #999; height: 1px;" />
		<p class='myspaceid_login_button_p'>
		<a href='#login' id='myspaceid_login_button'>
		  <img border='0' src='$button_path' alt='Login with MySpaceID' />
		</a>
		</p>
		<hr id="openid_split" style="clear: both; margin-bottom: 1.0em; border: 0; border-top: 1px solid #999; height: 1px;" />
LOGIN_FORM_END;
}

function myspaceid_wp_register_form() {
	$button_file = 'myspaceid-php-sdk/source/MySpaceID/images/MySpaceID-loginwith-156x28.png';
	$button_path = myspaceid_plugin_url() . '/' . $button_file;
	
	echo <<< REGISTER_FORM_END

		<script type='text/javascript'>
		msJQuery(function($) {
			$("#openid_split").clone().insertBefore("#openid_split");
			$("#myspaceid_login").insertAfter("#openid_split");
		});
		</script>
		<p id='myspaceid_login' class='myspaceid_login_button_p' style="width:100%;">
	Or
		<a href='#login' id='myspaceid_login_button' class='myspaceid_login_button_right' >
		  <img border='0' src='$button_path' alt='Login with MySpaceID' />
		</a>
		</p>
REGISTER_FORM_END;
}

?>