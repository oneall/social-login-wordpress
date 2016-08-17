=== Social Login ===
Contributors: ClaudeSchlesser
Tags: social login, social connect, facebook, linkedin, livejournal, google, yahoo, twitter, openid, pinterest, paypal, google, instagram, widget, plugin, social network login, comments
Requires at least: 3.0
Tested up to: 4.6
Stable tag: 5.2

Allow your visitors to comment and login with social networks like Twitter, Facebook, Paypal, LinkedIn, Instagram, OpenID, VKontakte, Google, Pinterest 

== Description ==

Social Login is a professionally developed and free Wordpress (BuddyPress compatible) plugin that allows your visitors to comment, 
login and register with 30+ Social Networks like for example Facebook, Twitter, Google, LinkedIn, PayPal, LiveJournal, Instagram, Вконтакте and Yahoo.<br /><br />
<strong>Make your blog social!</strong><br />

<strong>Choose where to add the Social Login Plugin:</strong>
<ul>
 <li>On the comment formular</li>
 <li>On the login page</li>
 <li>On the registration page</li>
 <li>In your sidebar</li>
 <li>With a shortcode</li>
</ul>

<strong>Optionally add the Social Login widget:</strong>
<ul>
 <li>A login widget that you can easily attach to your sidebar is provided</li>
</ul>

<strong>30+ Social Networks Availabe!</strong>
<ul>
 <li>Amazon</li>
 <li>Battle.net</li>
 <li>Blogger</li>
 <li>Disqus</li>
 <li>Dribbble</li>
 <li>Facebook</li>
 <li>Foursquare</li>
 <li>Github.com</li>
 <li>Google</li>
 <li>Instagram</li>
 <li>LinkedIn</li>
 <li>LiveJournal</li>
 <li>Mail.ru</li>
 <li>Odnoklassniki</li>
 <li>OpenID</li>
 <li>PayPal</li>
 <li>Pinterest</li>
 <li>PixelPin</li> 
 <li>Reddit</li>
 <li>Skyrock.com</li>		
 <li>StackExchange</li>
 <li>Steam</li>
 <li>Twitch.tv</li>
 <li>Twitter</li>
 <li>Vimeo</li>
 <li>VKontakte</li>
 <li>Windows Live</li>
 <li>WordPress.com</li>
 <li>Yahoo</li>
 <li>YouTube</li>
</ul>
 

<strong>Increase your wordpress/buddypress user engagement in a few simple steps with the Social Login Plugin!</strong>
Our users love it! Check out the <a href="http://wordpress.org/extend/plugins/oa-social-login/other_notes/">testimonials</a>!<br />

Social Login is maintained by <a href="http://www.oneall.com">OneAll</a>, a technology company offering a set of web-delivered
tools and services for establishing and optimizing a site's connection with social networks and identity providers such as Facebook, Twitter, 
Google, Yahoo!, LinkedIn, Paypal, Instagram amongst others.

== Installation ==

= Plugin Installation =
1. Upload the plugin folder to the "/wp-content/plugins/" directory of your WordPress site,
2. Activate the plugin through the 'Plugins' menu in WordPress,
3. Visit the "Settings\Social Login" administration page to setup the plugin. 

= API Connection =
The social network APIs are constantly changing and being updated. We monitor these changes and automatically 
update our APIs, so that you can be sure that Social Login will always run smoothly and with the most up-to-date 
API calls. 

In order to enable the plugin you must connect with the OneAll API and create a free account at https://app.oneall.com

== Frequently Asked Questions ==

= Do I have to add template tags to my theme? =

You should not have to change your templates. 
The Social Login seamlessly integrates into your blog by using predefined hooks.

= Can Social Login be embedded through a shortcode? =

The Social Login shortcode `[oa_social_login]` can be used in any page or post within your blog.
The shortcode will automatically be replaced by the icons of the social networks that you have
enabled in the Social Login settings in your WordPress administration area.

= I have a custom template and the plugin is not displayed correctly =

The plugin uses predefined hooks. If your theme does not support these hooks,
you can add the Social Login form manually to your theme by inserting the following code 
in your template (at the location where it should be displayed, i.e. above the comments).

`<?php do_action('oa_social_login'); ?>`

Do not hesitate to contact us if you need further assistance. 

= My users cannot login or leave comment with VKontakte (Вконтакте) =

Per default WordPress does not allow the use of special characters in usernames.
If you encounter any problems with users having cyrillic characters in their
usernames, please consider installing the following plugin to fix the problem:
<a href="http://wordpress.org/extend/plugins/wordpress-special-characters-in-usernames/">Wordpress Special Characters In Usernames</a>

= Do I have to change my Rewrite Settings? =

The plugins does not rely on mod_rewrite and does not need any additional rules.
It should work out of the box.


= Where can I report bugs, leave my feedback and get support? =

Our team answers your questions at:<br />
http://support.oneall.com/forums/

The plugin documentation is available at:<br />
http://docs.oneall.com/plugins/guide/social-login-wordpress/

== Screenshots ==

1. **Comment** - Comment formular (Social Network Buttons are included)
2. **Login** - Login formular (Social Network Buttons are included)
3. **Plugin Settings** - Plugin Settings in the Wordpress Administration Area
4. **Widget Settings** - Widget Settings in the Wordpress Administration Area
5. **Login** - Login formular with small buttons (Social Network Buttons are included)

== Changelog ==

= 5.2 =
* Buddypress avatar bugfix
* Do not create users without email addresses when plugin set to request emails
* Support for WP_PROXY_HOST added
* Filter for callback uri added
* More pannel added

= 5.0 =
* Social Network "Battle.net" added
* Social Link Hooks/Nonce added
* WooCommerce Actions added
* Login/Registration URL filters added
* Cache bug fixed
* WooCommerce Social Login Twice on Register Form
* Some minor bugs fixed

= 4.6 =
* Asynchronous JavaScript
* Social Network "Instagram" added
* Social Network "Vimeo" added
* Social Network "Reddit" added
* Social Network "Amazon" added
* French Translation Added
* Missing text domains added
* BuddyPress Avatars fixed
* Better WPEngine compatibility
* Email filter fixed

= 4.5 =
* Social Network "Twitch" added
* User Biography is now imported
* Better API Connection detection
* Many hooks and filters added
* Port detection improved
* WP Nonce added for Social Link

= 4.4 =
* Social Network "Xing" added

= 4.3 =
* Social Network Avatars improved
* Social Link shortcode/hook/action added
* Administration: Tabs for Social Login added
* Administration: Column Registration in the user list fixed
* Redirection filters added
* Settings security improved
* Button to cancel email confirmation added

= 4.0 =
* Social Link Service added
* Optimized for WordPress 3.5
* Meta "oa_social_login_identity_id" no longer used and removed
* German translations improved
* Social Network "YouTube" added
* Social Network "Odnoklassniki.ru" added
* Hook "after_signup_form" added

= 3.7 =
* Hook for BuddyPress Registration added
* Hook for BuddyPress Sidebar added
* Hook for Appthemes Vantage Theme added
* Filter for email addresses of new users added
* Admin page width fixed
* Minor text changes
* Social Network "Blogger" added
* Social Network "Disqus" added

= 3.6 =
* Debug Output Removed
 
= 3.5 = 
* Social Network "Foursquare.com" added
* Github 16x16px icon fixed
* Optionally get an email when a users registers with Social Login
* Redirection settings improved
* Hook for Thesis Theme added
* Hook for WordPress Profile Builder added
* Select to use Port 80 or 443
* Custom CSS filter added

= 3.2 = 
* Social Network "Skyrock.com" added
* Social Network "Github.com" added
* German translations improved

= 3.1 = 
* SSL detection improved
* Buddypress avatars improved

= 3.0 =
* SSL detection with nginx load-balancer fixed
* CDN path bug fixed
* Table width in administration area fixed
* Administration split to two pages 
* Optionally disable Social Login in comments
* Optionally request email from user
* Optionally show social networks in user list
* Social Network "Windows Mail" added
* Social Network "Mail.ru" added
* Error message if no social networks selected
* Class for Social Login label added
* Small icons fixed
* API settings verification fixed

= 2.5 =
* API Connection improved
* API Connection function moved to separate file
* Contact us link fixed
* Social Network Avatars fixed
* HTML for administration area fixed
* FSOCKOPEN Handler Added
* CURL/FSOCKOPEN selector added
* Social Network "Steam Community" added
* Social Network "StackExchange" added
* CSS served from CDN
* Optionally disable comment moderation

= 2.0 =
* WC3 Compliant callback uri
* HTTP/HTTPS Check for CSS files
* Shortcode handler fixed
* Wordpress Cookie now set for 14 days
* Wordpress display_name is now populated
* Redirection improved
* Now Buddypress compatible
* Link to settings page after installation
* Caching for socialize library improved
* Small buttons added as option
* Localization added
* German translation

= 1.6.1 =
* Provider unselect bug fixed
* Sanitize user strict added
* Custom namespace for add_settings_link

= 1.6 = 
* LiveJournal added
* PayPal added
* Settings link added
* API Communication Check added
* Cyrillic character support

= 1.5 =
* Social Network Avatars fixed
* Social Buttons no longer displayed for customs hooks if logged in
* KISS for API Settings Setup 

= 1.4 =
* Social Network Avatars can be displayed in comments
* Social Login can be disabled below the login form
* Social Login can be disabled below the registration form
* Select redirection target after login
* Select redirection target after registration
* Enable account linking

= 1.3.5 =
* Administration area redirection fixed
* Automatic email creation added
* Email verification added

= 1.3.4 =
* Multisite issues with Widget fixed

= 1.3.2 =
* Stable Version

= 1.0.2 = 
* Version numbers fixed

= 1.0.1 =
* Hook oa_social_login fixed
* Plugin description changed

= 1.0 =
* Initial release

== Testimonials ==

<strong>Used by thousands of users around the world!</strong>

<em>The plugin in is one of the best I've seen so far. Extremely easy to implement and run. The support is great too. 
No concerns on my side. Keep it up!</em>
<strong>livia</strong>

<em>Loving the service, seen a massive increase in painless signups to my blog. Thanks!</em>
<strong>Richard B.</strong>

<em>You have no idea how it THRILLED me to integrate oneall. It was SO amazingly easy, your team has simplified the whole process of signing up for 
authorization on multiple social media sites. I HAD NO QUESTIONS/STEPS THAT YOU HADN'T ALREADY ANTICIPATED. It saved me HOURS of work!</em>
<strong>Kelly C.</strong>
 
<em>This is cool. Nice work. I'm VERY impressed. You've made this about as painless as it gets and the value it adds is incredible.</em>
<strong>Jason M.</strong>

<em>This service is simply remarkable, I've tried integrating logins before and it has never been this easy!</em>
<strong>Andrew C.</strong>

<em>I found it extremely straightforward. I just figured it out easily and make my website capable of connecting 
to many social networks by your plugin.</em>
<strong>Deha K.</strong>

<em>Just wanted to let you know how happy i am that i stumbled onto your service. This was the 6 Facebook/Twitter integration 
i tried and was starting to loose hope that i could actually find one that worked for me.</em>
<strong>Kyle L.</strong>

<em>I would like to thank YOU! Seriously, the WordPress plugin has been a huge life saver for me.</em>
<strong>Piero B.</strong>

<em>Thank you for the wonderful plugin</em>
<strong>Martin P.</strong>

<em>The service is excellent for what i need, simple to set up. All situations about seting up are well explained, so 
there are no difficulties</em>
<strong>Facundo S.</strong>

<em>I really like the plugin, the capabilities you provide for management and your prompt reply for support.</em>
<strong>Tom B.</strong>

<em>It was extremely easy to set up and use.  The documentation to set up the FB and twitter API
was easy to follow and implement. I was struggling with a couple of other plugins till I stumbled on this one.</em>
<strong>Deepa V.</strong>

<em>Works like a charm!</em>
<strong>Fredrik L.</strong>

<em>Not sure how you can improve it's a Damn! Good product. 100% User friendly easy to setup. Thanks!</em>
<strong>Cody L.</strong>

<em>So far oneall.com is the perfect solution for my site and works flawlessly.  I am extremely impressed and grateful.</em>
<strong>Terry P.</strong>

<em>I've gone in and tweaked it, tested it and it's good to go now! Wonderful, I feel like a grown up blogger now.</em>
<strong>Brian J.</strong>

<em>I am really impressed with your product! Its very dynamic and its gives me the flexibility I need for integration into my own business.</em>
<strong>Braxton D.</strong>

<em>Your delivery is superb. You should change your name to WONall because you won it all with me. You are awesome, stay that way please.</em>
<strong>Nicholas L.</strong>

<em>I especially enjoy the step by step process that guides you through the Social website App creation process. In the end I would like to thank you 
for putting together such a great product that so many users can implement with ease.</em>
<strong>Stefan C.</strong>

<em>Thanks for a such a great plugin! I was really impressed with the simplicity of the installation directions and the clean design.</em>
<strong>Janae S.</strong>

<em>I love your service the way it is, it's amazing how easy the logging-in-via-social-network is integrated into a wordpress website!</em>
<strong>Martin S.</strong>

<em>The site and the plugin are working magnificently. Thank you one million times for making your products/services available in the manner that you have.</em>
<strong>Herman G.</strong>

<em>Very user friendly, there are guides and screenshot on how to set things up. Thank you so much for this awesome plugin!</em>
<strong>Cebututs</strong> 
