=== wp-myspaceid ===
Contributors: cbbaker, stevenng
Tags: myspace, myspaceid, openid, oauth, authentication, authorization, login, comments, registration
Requires at least: 2.3
Tested up to: 2.7
Stable tag: 1.6

This plug-in adds a `Login with MySpaceID' button to the login page.

== Description ==

Adding MySpaceID to a blog allows bloggers and commenters to log in
using their MySpace credentials rather than having to set up and
remember yet another username and password.

To log in, a user clicks on the "Login with MySpaceID" button, which
brings up a popup window with a special MySpace login page.  If the
user is already logged in to MySpace, the popup will show which
account they are using and the name of the blog, and ask them to
confirm that they want to use their MySpaceID to log in.  If they are
not logged in, it will show the blog name, give them a place to enter
their MySpace credentials, and ask for confirmation.  If the user logs
in successfully, the popup closes, and the plugin either creates a new
account on the blog for them, or logs them in with their existing
account.

This plug-in depends on the MySpaceID PHP SDK, which requires PHP 5 or
later.  It is not compatible with PHP 4.

Please report any problems or suggestions
[here](http://developer.myspace.com/Community/forums/108.aspx).

== Installation ==

For a detailed walk through of these instructions, please visit
[here](http://wiki.developer.myspace.com/index.php?title=MySpaceID_WordPress_Plug-in_Setup).

1. Install the following plug-ins using their instructions:
   * [xrds-simple](http://wordpress.org/extend/plugins/xrds-simple/)
   * [openid](http://wordpress.org/extend/plugins/openid/)

1. Download [wp-myspaceid.zip](http://wordpress.org/extend/plugins/wp-myspaceid/)

1. Unzip the wp-myspaceid.zip file to the /wp-content/plugins directory on the hosting server.

1. On the WordPress Blog, select the Plugins tab and click the
   Activate links for MySpaceID, OpenID and XDRS-Simple.

1. If you have not already done so, register your site as a MySpaceID
   application by following the instructions on [How to Set Up a New
   Application for
   OpenID](http://wiki.developer.myspace.com/index.php?title=How_to_Set_Up_a_New_Application_for_OpenID).

   **IMPORTANT**: When you register your site as a MySpaceID
   application, you'll enter your domain as a Relying Party
   Realm. When you do this, **be sure** to use a trailing slash at the
   end of the URL (i.e., use http://www.myblog.com/ NOT
   http://www.myblog.com).

1. On the WordPress Blog, select the **Settings** tab and click **MySpaceID**.

1. Enter your OAuth Consumer Key and OAuth Consumer Secret in the text
   fields provided and click **Update Options**.

Visitors to the WordPress blog are now able to log in the their
MySpace username and password.
