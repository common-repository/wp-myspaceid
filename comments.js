function myspaceid_add_name_style() {
  msJQuery("#commentform a[href$='profile.php']").addClass('myspaceid_link');
}

function add_myspaceid_to_comment_form(button_path) {

  if (msJQuery("#myspaceid_login_button").size() > 0)
	return;

  var button_code = ' <a id="myspaceid_login_button" href="#login" ' +
					  'onclick="myspaceid_login()" > ' +
					  '<img border="0" src=' + button_path + ' alt="Login with MySpaceID" /> ' +
					  '</a> ';

  // this is really bitchy--we want to put the button floated on the opposite side as the comment button
  // but sometimes the comment button's parent is floated

  var insert_point  = msJQuery("#submit");
  var float_side = insert_point.css('float');

  insert_point.before(button_code);
  var button_class = float_side == 'right' ? "myspaceid_login_button_left" : "myspaceid_login_button_right";
  msJQuery("#myspaceid_login_button").addClass(button_class);
}
