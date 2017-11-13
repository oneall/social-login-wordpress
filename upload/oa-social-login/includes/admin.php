<?php

/**
 * Admin User List: Add header column
 **/
function oa_social_login_admin_user_column_add ($columns)
{
	//Read settings
	$settings = get_option ('oa_social_login_settings');

	//Add column if enabled
	if (!empty ($settings ['plugin_add_column_user_list']))
	{
		$columns ['oa_social_login_registration'] = __ ('Registration', 'oa_social_login');
	}
	return $columns;
}
add_filter ('manage_users_columns', 'oa_social_login_admin_user_column_add');


/**
 * Admin User List: Add column content
 **/
function oa_social_login_admin_user_colum_display ($value, $column_name, $user_id)
{
	//Check if it is our own column
	if ($column_name <> 'oa_social_login_registration')
	{
		return $value;
	}

	//Read Identity Providers
	$identity_providers = trim (strval (get_user_meta ($user_id, 'oa_social_login_identity_provider', true)));

	// Some have been found
	if ( ! empty ($identity_providers))
	{
	    $identity_providers = array_unique (explode ("|", $identity_providers));
	    $identity_providers = array_map("ucwords", $identity_providers);
	}
	// None found
	else
	{
	    $identity_providers = array ();
	}

	// Social Login Registration
	if (count ($identity_providers) > 0)
	{
		return '<strong>Social Login</strong>: ' . implode (", ", $identity_providers);
	}
	//Traditional Registration
	else
	{
	    return __ ('Registration Form', 'oa_social_login');
	}
}
add_action ('manage_users_custom_column', 'oa_social_login_admin_user_colum_display', 10, 3);


/**
 * Add administration area links
 **/
function oa_social_login_admin_menu ()
{
	//Setup
	$page = add_menu_page ('OneAll Social Login ' . __ ('Setup', 'oa_social_login'), 'Social Login', 'manage_options', 'oa_social_login_setup', 'oa_display_social_login_setup');
	add_action ('admin_print_styles-' . $page, 'oa_social_login_admin_css');

	//Settings
	$page = add_submenu_page ('oa_social_login_setup', 'OneAll Social Login ' . __ ('Settings'), __ ('Settings'), 'manage_options', 'oa_social_login_settings', 'oa_display_social_login_settings');
	add_action ('admin_print_styles-' . $page, 'oa_social_login_admin_css');

	//Sharing
	$page = add_submenu_page ('oa_social_login_setup', 'OneAll Social Login ' . __ ('+More'), __ ('+More'), 'manage_options', 'oa_social_login_more', 'oa_display_social_login_more');
	add_action ('admin_print_styles-' . $page, 'oa_social_login_admin_css');

	//Fix Setup title
	global $submenu;
	if (is_array ($submenu) AND isset ($submenu ['oa_social_login_setup']))
	{
		$submenu ['oa_social_login_setup'] [0] [0] = __ ('Setup', 'oa_social_login');
	}

	add_action ('admin_enqueue_scripts', 'oa_social_login_admin_js');
	add_action ('admin_init', 'oa_register_social_login_settings');
	add_action ('admin_notices', 'oa_social_login_admin_message');
}
add_action ('admin_menu', 'oa_social_login_admin_menu');


/**
 * Automatically approve comments if option enabled
 **/
function oa_social_login_admin_pre_comment_approved ($approved)
{
	// No need to do the check if the comment has already been approved
	if (empty ($approved))
	{
		//Read settings
		$settings = get_option ('oa_social_login_settings');

		//Check if enabled
		if (!empty ($settings ['plugin_comment_auto_approve']))
		{
			$user_id = get_current_user_id ();
			if (is_numeric ($user_id))
			{
				if (get_user_meta ($user_id, 'oa_social_login_user_token', true) !== false)
				{
					$approved = 1;
				}
			}
		}
	}
	return $approved;
}
add_action ('pre_comment_approved', 'oa_social_login_admin_pre_comment_approved');


/**
 * Add an activation message to be displayed once
 */
function oa_social_login_admin_message ()
{
	if (get_option ('oa_social_login_activation_message') !== '1')
	{
		echo '<div class="updated"><p><strong>' . __ ('Thank you for using Social Login!', 'oa_social_login') . '</strong> ' . sprintf (__ ('Please complete the <strong><a href="%s">Social Login Setup</a></strong> to enable the plugin.', 'oa_social_login'), 'admin.php?page=oa_social_login_setup') . '</p></div>';
		update_option ('oa_social_login_activation_message', '1');
	}
}


/**
 * Autodetect API Connection Handler
 */
function oa_social_login_admin_autodetect_api_connection_handler ()
{
	//Check AJAX Nonce
	check_ajax_referer ('oa_social_login_ajax_nonce');

	//Check if CURL is available
	if (oa_social_login_check_curl_available ())
	{
		//Check CURL HTTPS - Port 443
		if (oa_social_login_check_curl (true) === true)
		{
			echo 'success_autodetect_api_curl_https';
			die ();
		}
		//Check CURL HTTP - Port 80
		elseif (oa_social_login_check_curl (false) === true)
		{
			echo 'success_autodetect_api_curl_http';
			die ();
		}
		else
		{
			echo 'error_autodetect_api_curl_ports_blocked';
			die ();
		}
	}
	//Check if FSOCKOPEN is available
	elseif (oa_social_login_check_fsockopen_available())
	{
		//Check FSOCKOPEN HTTPS - Port 443
		if (oa_social_login_check_fsockopen (true) == true)
		{
			echo 'success_autodetect_api_fsockopen_https';
			die ();
		}
		//Check FSOCKOPEN HTTP - Port 80
		elseif (oa_social_login_check_fsockopen (false) == true)
		{
			echo 'success_autodetect_api_fsockopen_http';
			die ();
		}
		else
		{
			echo 'error_autodetect_api_fsockopen_ports_blocked';
			die ();
		}
	}

	//No working handler found
	echo 'error_autodetect_api_no_handler';
	die ();
}
add_action ('wp_ajax_oa_social_login_autodetect_api_connection_handler', 'oa_social_login_admin_autodetect_api_connection_handler');


/**
 * Check API Settings through an Ajax Call
 */
function oa_social_login_admin_check_api_settings ()
{
	check_ajax_referer ('oa_social_login_ajax_nonce');

	//Check if all fields have been filled out
	if (empty ($_POST ['api_subdomain']) OR empty ($_POST ['api_key']) OR empty ($_POST ['api_secret']))
	{
		echo 'error_not_all_fields_filled_out';
		delete_option ('oa_social_login_api_settings_verified');
		die ();
	}

	//Check the handler
	$api_connection_handler = ((!empty ($_POST ['api_connection_handler']) AND $_POST ['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
	$api_connection_use_https = ((!isset ($_POST ['api_connection_use_https']) OR $_POST ['api_connection_use_https'] == '1') ? true : false);

	//FSOCKOPEN
	if ($api_connection_handler == 'fsockopen')
	{
		if (!oa_social_login_check_fsockopen ($api_connection_use_https))
		{
			echo 'error_selected_handler_faulty';
			delete_option ('oa_social_login_api_settings_verified');
			die ();
		}
	}
	//CURL
	else
	{
		if (!oa_social_login_check_curl ($api_connection_use_https))
		{
			echo 'error_selected_handler_faulty';
			delete_option ('oa_social_login_api_settings_verified');
			die ();
		}
	}

	$api_subdomain = trim (strtolower ($_POST ['api_subdomain']));
	$api_key = trim ($_POST ['api_key']);
	$api_secret = trim ($_POST ['api_secret']);

	//Full domain entered
	if (preg_match ("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
	{
		$api_subdomain = $matches [1];
	}

	//Check subdomain format
	if (!preg_match ("/^[a-z0-9\-]+$/i", $api_subdomain))
	{
		echo 'error_subdomain_wrong_syntax';
		delete_option ('oa_social_login_api_settings_verified');
		die ();
	}

	//Domain
	$api_domain = $api_subdomain . '.api.oneall.com';

	//Connection to
	$api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_domain . '/tools/ping.json';

	//Get connection details
	$result = oa_social_login_do_api_request ($api_connection_handler, $api_resource_url, array ('api_key' => $api_key, 'api_secret' => $api_secret), 15);

	//Parse result
	if (is_object ($result) AND property_exists ($result, 'http_code') AND property_exists ($result, 'http_data'))
	{
		switch ($result->http_code)
		{
			//Success
			case 200:
				echo 'success';
				update_option ('oa_social_login_api_settings_verified', '1');
				break;

			//Authentication Error
			case 401:
				echo 'error_authentication_credentials_wrong';
				delete_option ('oa_social_login_api_settings_verified');
				break;

			//Wrong Subdomain
			case 404:
				echo 'error_subdomain_wrong';
				delete_option ('oa_social_login_api_settings_verified');
				break;

			//Other error
			default:
				echo 'error_communication';
				delete_option ('oa_social_login_api_settings_verified');
				break;
		}
	}
	else
	{
		echo 'error_communication';
		delete_option ('oa_social_login_api_settings_verified');
	}
	die ();
}
add_action ('wp_ajax_oa_social_login_check_api_settings', 'oa_social_login_admin_check_api_settings');


/**
 * Add Settings JS
 **/
function oa_social_login_admin_js ($hook)
{
	if (stripos ($hook, 'oa_social_login') !== false)
	{
		if (!wp_script_is ('oa_social_login_admin_js', 'registered'))
		{
			wp_register_script ('oa_social_login_admin_js', OA_SOCIAL_LOGIN_PLUGIN_URL . "/assets/js/admin.js");
		}

		$oa_social_login_ajax_nonce = wp_create_nonce ('oa_social_login_ajax_nonce');

		wp_enqueue_script ('oa_social_login_admin_js');
		wp_enqueue_script ('jquery');

		wp_localize_script ('oa_social_login_admin_js', 'objectL10n',
			array (
				'oa_social_login_ajax_nonce' => $oa_social_login_ajax_nonce,
				'oa_admin_js_1' => __ ('Contacting API - please wait this may take a few minutes ...', 'oa_social_login'),
				'oa_admin_js_101' => __ ('The settings are correct - do not forget to save your changes!', 'oa_social_login'),
				'oa_admin_js_111' => __ ('Please fill out each of the fields above.', 'oa_social_login'),
				'oa_admin_js_112' => __ ('The subdomain does not exist. Have you filled it out correctly?', 'oa_social_login'),
				'oa_admin_js_113' => __ ('The subdomain has a wrong syntax!', 'oa_social_login'),
				'oa_admin_js_114' => __ ('Could not contact API. Are outbound requests on port 443 allowed?', 'oa_social_login'),
				'oa_admin_js_115' => __ ('The API subdomain is correct, but one or both keys are invalid', 'oa_social_login'),
				'oa_admin_js_116' => __ ('Connection handler does not work, try using the Autodetection', 'oa_social_login'),
				'oa_admin_js_201a' => __ ('Detected CURL on Port 443 - do not forget to save your changes!', 'oa_social_login'),
				'oa_admin_js_201b' => __ ('Detected CURL on Port 80 - do not forget to save your changes!', 'oa_social_login'),
				'oa_admin_js_201c' => __ ('CURL is available but both ports (80, 443) are blocked for outbound requests', 'oa_social_login'),
				'oa_admin_js_202a' => __ ('Detected FSOCKOPEN on Port 443 - do not forget to save your changes!', 'oa_social_login'),
				'oa_admin_js_202b' => __ ('Detected FSOCKOPEN on Port 80 - do not forget to save your changes!', 'oa_social_login'),
				'oa_admin_js_202c' => __ ('FSOCKOPEN is available but both ports (80, 443) are blocked for outbound requests', 'oa_social_login'),
				'oa_admin_js_211' => sprintf (__ ('Autodetection Error - our <a href="%s" target="_blank">documentation</a> helps you fix this issue.', 'oa_social_login'), 'http://docs.oneall.com/plugins/guide/social-login-wordpress/#help')
			));
	}
}


/**
 * Add Settings CSS
 **/
function oa_social_login_admin_css ($hook = '')
{
	if (!wp_style_is ('oa_social_login_admin_css', 'registered'))
	{
		wp_register_style ('oa_social_login_admin_css', OA_SOCIAL_LOGIN_PLUGIN_URL . "/assets/css/admin.css");
	}

	if (did_action ('wp_print_styles'))
	{
		wp_print_styles ('oa_social_login_admin_css');
	}
	else
	{
		wp_enqueue_style ('oa_social_login_admin_css');
	}
}


/**
 * Register plugin settings and their sanitization callback
 */
function oa_register_social_login_settings ()
{
	register_setting ('oa_social_login_settings_group', 'oa_social_login_settings', 'oa_social_login_settings_validate');
}


/**
 *  Plugin settings sanitization callback
 */
function oa_social_login_settings_validate ($settings)
{
	//Import providers
	GLOBAL $oa_social_login_providers;

	//Settings page?
	$page = (!empty ($_POST ['page']) ? strtolower ($_POST ['page']) : '');

	//Store the sanitzed settings
	$sanitzed_settings = get_option ('oa_social_login_settings');

	//Check format
	if (!is_array ($sanitzed_settings))
	{
		$sanitzed_settings = array ();
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//Setup
	////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if ($page == 'setup')
	{

		//Extract fields
		foreach (array (
			'api_connection_handler',
			'api_connection_use_https',
			'api_subdomain',
		  'asynchronous_javascript',
			'api_key',
			'api_secret',
			'providers'
		) AS $key)
		{
			//Value is given
			if (isset ($settings [$key]))
			{
				//Provider tickboxes
				if ($key == 'providers')
				{
					//Resest providers
					$sanitzed_settings ['providers'] = array ();

					//Loop through new values
					if (is_array ($settings ['providers']))
					{
						//Loop through valid values
						foreach ($oa_social_login_providers AS $key => $name)
						{
							if (isset ($settings ['providers'] [$key]) AND $settings ['providers'] [$key] == '1')
							{
								$sanitzed_settings ['providers'] [$key] = 1;
							}
						}
					}
				}
				//Other field
				else
				{
					$sanitzed_settings [$key] = trim ($settings [$key]);
				}
			}
		}
		//Sanitize API Use HTTPS
		$sanitzed_settings ['api_connection_use_https'] = (empty ($sanitzed_settings ['api_connection_use_https']) ? 0 : 1);

		//Sanitize API Connection handler
		if (isset ($sanitzed_settings ['api_connection_handler']) AND in_array (strtolower ($sanitzed_settings ['api_connection_handler']), array ('curl','fsockopen')))
		{
			$sanitzed_settings ['api_connection_handler'] = strtolower ($sanitzed_settings ['api_connection_handler']);
		}
		else
		{
			$sanitzed_settings ['api_connection_handler'] = 'curl';
		}

		//Sanitize API Subdomain
		if (isset ($sanitzed_settings ['api_subdomain']))
		{
			//Subdomain is always in lowercase
			$api_subdomain = strtolower ($sanitzed_settings ['api_subdomain']);

			//Full domain entered
			if (preg_match ("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
			{
				$api_subdomain = $matches [1];
			}

			$sanitzed_settings ['api_subdomain'] = $api_subdomain;
		}

		//Sanitize Synchronous Flag
		$sanitzed_settings ['asynchronous_javascript'] = (empty ($sanitzed_settings ['asynchronous_javascript']) ? 0 : 1);

		//Done
		return $sanitzed_settings;
	}
	////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//Setup
	////////////////////////////////////////////////////////////////////////////////////////////////////////////
	elseif ($page == 'settings')
	{
		//Extract fields
		foreach (array (
			'plugin_add_column_user_list',
			'plugin_require_email',
			'plugin_require_email_text',
			'plugin_caption',
			'plugin_link_verified_accounts',
			'plugin_show_avatars_in_comments',
			'plugin_icon_theme',
			'plugin_display_in_login_form',
			'plugin_login_form_redirect',
			'plugin_login_form_redirect_custom_url',
			'plugin_protect_login_redirect_url',
			'plugin_display_in_registration_form',
			'plugin_registration_form_redirect',
			'plugin_registration_form_redirect_custom_url',
			'plugin_protect_registration_redirect_url',
			'plugin_comment_show_if_members_only',
			'plugin_comment_auto_approve',
			'plugin_comment_show',
			'plugin_profile_show',
			'plugin_shortcode_login_redirect',
			'plugin_shortcode_login_redirect_url',
			'plugin_shortcode_register_redirect',
			'plugin_shortcode_register_redirect_url',
			'plugin_notify_admin'
		) AS $key)
		{
			if (isset ($settings [$key]))
			{
				$sanitzed_settings [$key] = trim ($settings [$key]);
			}
			else
			{
				$sanitzed_settings [$key] = '';
			}
		}

		//Flag settings
		$sanitzed_settings ['plugin_add_column_user_list'] = ($sanitzed_settings ['plugin_add_column_user_list'] == '1' ? 1 : 0);
		$sanitzed_settings ['plugin_profile_show'] = ($sanitzed_settings ['plugin_profile_show'] == '0' ? 0 : 1);
		$sanitzed_settings ['plugin_notify_admin'] = ($sanitzed_settings ['plugin_notify_admin'] == '0' ? 0 : 1);
		$sanitzed_settings ['plugin_require_email'] = ($sanitzed_settings ['plugin_require_email'] == '1' ? 1 : 0);
		$sanitzed_settings ['plugin_comment_show'] = ($sanitzed_settings ['plugin_comment_show'] == '0' ? 0 : 1);
		$sanitzed_settings ['plugin_comment_show_if_members_only'] = ($sanitzed_settings ['plugin_comment_show_if_members_only'] == '0' ? 0 : 1);
		$sanitzed_settings ['plugin_icon_theme'] = (in_array ($sanitzed_settings ['plugin_icon_theme'], array (0, 1, 2)) ? $sanitzed_settings ['plugin_icon_theme'] : 0);
		$sanitzed_settings ['plugin_display_in_login_form'] = ($sanitzed_settings ['plugin_display_in_login_form'] == '0' ? 0 : 1);
		$sanitzed_settings ['plugin_display_in_registration_form'] = ($sanitzed_settings ['plugin_display_in_registration_form'] == '0' ? 0 : 1);
		$sanitzed_settings ['plugin_link_verified_accounts'] = ($sanitzed_settings ['plugin_link_verified_accounts'] == '0' ? 0 : 1);
		$sanitzed_settings ['plugin_comment_auto_approve'] = ($sanitzed_settings ['plugin_comment_auto_approve'] == '1' ? 1 : 0);
		$sanitzed_settings ['plugin_protect_registration_redirect_url'] = ($sanitzed_settings ['plugin_protect_registration_redirect_url'] == '1' ? 1 : 0);
		$sanitzed_settings ['plugin_protect_login_redirect_url'] = ($sanitzed_settings ['plugin_protect_login_redirect_url'] == '1' ? 1 : 0);


		//Multiple settings
		$sanitzed_settings ['plugin_show_avatars_in_comments'] = (in_array ($sanitzed_settings ['plugin_show_avatars_in_comments'], array (0, 1, 2)) ? $sanitzed_settings ['plugin_show_avatars_in_comments'] : 0);

		//Redirection Settings: Widget & Shortcode Login
		$sanitzed_settings ['plugin_shortcode_login_redirect'] = (in_array ($sanitzed_settings ['plugin_shortcode_login_redirect'], array ('current','dashboard','homepage','custom')) ? $sanitzed_settings ['plugin_shortcode_login_redirect'] : 'current');
		if ($sanitzed_settings ['plugin_shortcode_login_redirect'] == 'custom')
		{
			if (empty ($sanitzed_settings ['plugin_shortcode_login_redirect_url']))
			{
				$sanitzed_settings ['plugin_shortcode_login_redirect'] = 'current';
			}
		}
		else
		{
			$sanitzed_settings ['plugin_shortcode_login_redirect_url'] = '';
		}

		//Redirection Settings: Widget & Shortcode Registration
		$sanitzed_settings ['plugin_shortcode_register_redirect'] = (in_array ($sanitzed_settings ['plugin_shortcode_register_redirect'], array ('current','dashboard','homepage','custom')) ? $sanitzed_settings ['plugin_shortcode_register_redirect'] : 'current');
		if ($sanitzed_settings ['plugin_shortcode_register_redirect'] == 'custom')
		{
			if (empty ($sanitzed_settings ['plugin_shortcode_register_redirect_url']))
			{
				$sanitzed_settings ['plugin_shortcode_register_redirect'] = 'current';
			}
		}
		else
		{
			$sanitzed_settings ['plugin_shortcode_register_redirect_url'] = '';
		}

		//Redirection Settings: Form Login
		$sanitzed_settings ['plugin_login_form_redirect'] = (in_array ($sanitzed_settings ['plugin_login_form_redirect'], array ('current','dashboard','homepage','custom')) ? $sanitzed_settings ['plugin_login_form_redirect'] : 'homepage');
		if ($sanitzed_settings ['plugin_login_form_redirect'] == 'custom')
		{
			if (empty ($sanitzed_settings ['plugin_login_form_redirect_custom_url']))
			{
				$sanitzed_settings ['plugin_login_form_redirect'] = 'homepage';
			}
		}
		else
		{
			$sanitzed_settings ['plugin_login_form_redirect_custom_url'] = '';
		}


		//Redirection Settings: Form Registration
		$sanitzed_settings ['plugin_registration_form_redirect'] = (in_array ($sanitzed_settings ['plugin_registration_form_redirect'], array ('current','dashboard','homepage','custom')) ? $sanitzed_settings ['plugin_registration_form_redirect'] : 'dashboard');
		if ($sanitzed_settings ['plugin_registration_form_redirect'] == 'custom')
		{
			if (empty ($sanitzed_settings ['plugin_registration_form_redirect_custom_url']))
			{
				$sanitzed_settings ['plugin_registration_form_redirect'] = 'dashboard';
			}
		}
		else
		{
			$sanitzed_settings ['plugin_registration_form_redirect_custom_url'] = '';
		}

		//Done
		return $sanitzed_settings;
	}

	//Error
	return array ();
}


/**
 * Display More Page
 **/
function oa_display_social_login_more ()
{
	?>
		<div class="wrap">
			<div id="oa_social_login_page" class="oa_social_login_more">
				<h2>OneAll Social Login <?php echo (defined ('OA_SOCIAL_LOGIN_VERSION') ? OA_SOCIAL_LOGIN_VERSION : ''); ?></h2>

				<h2 class="nav-tab-wrapper">
         			<a class="nav-tab" href="admin.php?page=oa_social_login_setup"><?php _e ('Setup', 'oa_social_login'); ?></a>
          			<a class="nav-tab" href="admin.php?page=oa_social_login_settings"><?php _e ('Settings', 'oa_social_login'); ?></a>
          			<a class="nav-tab nav-tab-active" href="admin.php?page=oa_social_login_more"><?php _e ('+More', 'oa_social_login'); ?></a>
        		</h2>

        		<p></p>

				<div class="oa_social_login_box" id="oa_social_login_box_help">
					<ul>
						<li><?php printf (__ ('<a target="_blank" href="%s">Follow us on Twitter</a> to stay informed about updates', 'oa_social_login'), 'http://www.twitter.com/oneall'); ?>;</li>
						<li><?php printf (__ ('<a target="_blank" href="%s">Read the online documentation</a> for more information about this plugin', 'oa_social_login'), 'http://docs.oneall.com/plugins/guide/social-login-wordpress/'); ?>;</li>
						<li><?php printf (__ ('<a target="_blank" href="%s">Contact us</a> if you have feedback or need assistance', 'oa_social_login'), 'http://www.oneall.com/company/contact-us/'); ?>.
						<li><?php printf (__ ('We also have <a target="_blank" href="%s">turnkey plugins</a> for Drupal, PrestaShop, Joomla, phpBB andy many others ...', 'oa_social_login'), 'http://docs.oneall.com/plugins/'); ?>.
						</li>
					</ul>
				</div>

				<h1><?php _e ('Discover our other plugins!', 'oa_social_login'); ?></h1>
				<?php
				    $more_url = admin_url('plugin-install.php?s=share+buttons+oneall&tab=search&type=term');
				?>
				<div class="oa_social_login_plugin">
					<div class="oa_social_login_plugin_img">
						<a href="<?php echo $more_url; ?>"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../assets/img/social_sharing.png' ?>" alt="<?php _e ('Social Login', 'oa_social_login') ?>" /></a>
					</div>
					<div class="oa_social_login_plugin_desc">
						<?php _e ('Allow your visitors to comment, login and register with 30+ social networks like for example Twitter, Facebook, Pinterest, Instagram, Paypal, LinkedIn, OpenID, VKontakte or Google+. Easy to use and 100% FREE.', 'oa_social_login'); ?>
						<a href="<?php echo $more_url; ?>" class="button-primary"><?php _e ('More Info', 'oa_social_login'); ?></a>
					</div>
				</div>

				<?php
				    $more_url = admin_url('plugin-install.php?s=loudvoice+oneall&tab=search&type=term');
				?>
				<div class="oa_social_login_plugin">
					<div class="oa_social_login_plugin_img">
						<a href="<?php echo $more_url; ?>"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../assets/img/loudvoice.png' ?>" alt="<?php _e ('LoudVoice Comment System', 'oa_social_login') ?>" /></a>
					</div>
					<div class="oa_social_login_plugin_desc">
						<?php _e ('LoudVoice replaces the basic WordPress comments by a powerful comment system that includes logging in with 30+ social networks, spam filters and more. Easy to use and 100% FREE. Existing comments can be imported!', 'oa_social_login'); ?>
						<a href="<?php echo $more_url; ?>" class="button-primary"><?php _e ('More Info', 'oa_social_login'); ?></a>
					</div>
				</div>
			</div>
		</div>
	<?php
}


/**
 * Display Settings Page
 **/
function oa_display_social_login_setup ()
{
	//Import providers
	GLOBAL $oa_social_login_providers;
	?>
		<div class="wrap">
			<div id="oa_social_login_page" class="oa_social_login_setup">
				<h2>OneAll Social Login <?php echo (defined ('OA_SOCIAL_LOGIN_VERSION') ? OA_SOCIAL_LOGIN_VERSION : ''); ?></h2>
				<h2 class="nav-tab-wrapper">
          			<a class="nav-tab nav-tab-active" href="admin.php?page=oa_social_login_setup"><?php _e ('Setup', 'oa_social_login'); ?></a>
          			<a class="nav-tab" href="admin.php?page=oa_social_login_settings"><?php _e ('Settings', 'oa_social_login'); ?></a>
          			<a class="nav-tab" href="admin.php?page=oa_social_login_more"><?php _e ('+More', 'oa_social_login'); ?></a>
        		</h2>
				<?php
					if (get_option ('oa_social_login_api_settings_verified') !== '1')
					{
						?>
							<p>
								<?php _e ('Allow your visitors to comment, login and register with 30+ Social Networks like for example Twitter, Facebook, LinkedIn, Instagram, VKontakte, Google or Yahoo.', 'oa_social_login'); ?>
								<strong><?php _e ('Draw a larger audience and increase your user engagement in a  few simple steps.', 'oa_social_login'); ?> </strong>
							</p>
							<div class="oa_social_login_box" id="oa_social_login_box_started">
								<div class="oa_social_login_box_title">
									<?php _e ('Thank you for using Social Login', 'oa_social_login'); ?>
								</div>
								<p>
									<?php printf (__ ('To be able to use this plugin you first of all need to create a free account at %s. By using OneAll you will be sure that your social media integration will always run smoothly and with the most up-to-date calls.', 'oa_social_login'), '<a href="https://app.oneall.com/signup/wp" target="_blank">http://www.oneall.com</a>'); ?>
								</p>
								<p>
									<?php _e ('After having created your account and setup a Site, please enter the Site settings below.', 'oa_social_login'); ?>
									<?php _e ("Don't worry the setup is free and takes only a few minutes!", 'oa_social_login'); ?>
										<?php printf (__ ('You are in good company! This plugin is used on more than %s websites!', 'oa_social_login'), '300,000'); ?>
								</p>
								<p class="oa_social_login_button_wrap">
									<a class="button-secondary" href="https://app.oneall.com/signup/wp" target="_blank"><strong><?php _e ('Click here to setup your free account', 'oa_social_login'); ?></strong></a>
								</p>
								<h3>

								</h3>
							</div>
						<?php
					}
					else
					{
						?>
							<p></p>
							<div class="oa_social_login_box" id="oa_social_login_box_status">
								<p>
									<?php _e ('Login to your OneAll account to manage your social networks and to access your Social Analytics, Graphs &amp; Statistics.', 'oa_social_login'); ?>
								</p>
								<p class="oa_social_login_button_wrap">
									<a class="button-secondary" href="https://app.oneall.com/signin/" target="_blank"><strong><?php _e ('Login to my OneAll account', 'oa_social_login'); ?></strong> </a>
									<a class="button-secondary" href="https://app.oneall.com/insights/" target="_blank"><strong><?php _e ('Access my Social Analytics', 'oa_social_login'); ?></strong> </a>
								</p>
							</div>
						<?php
					}

					?>

					<div class="oa_social_login_box" id="oa_social_login_box_help">
					<ul>
						<li><?php printf (__ ('Checkout the <a target="_blank" href="%s">online documentation</a> for more information about this plugin', 'oa_social_login'), 'http://docs.oneall.com/plugins/guide/social-login-wordpress/'); ?>;</li>
						<li><?php printf (__ ('Do not hesitate to <a target="_blank" href="%s">contact us</a> if you have feedback or need assistance', 'oa_social_login'), 'http://www.oneall.com/company/contact-us/'); ?>.</li>
					</ul>
				</div>
					<?php
					if (!empty ($_REQUEST ['settings-updated']) AND strtolower ($_REQUEST ['settings-updated']) == 'true')
					{
						?>
							<div class="oa_social_login_box" id="oa_social_login_box_updated">
								<?php _e ('Your modifications have been saved successfully!'); ?>
							</div>
						<?php
					}
				?>
				<form method="post" action="options.php">
					<?php
						settings_fields ('oa_social_login_settings_group');
						$settings = get_option ('oa_social_login_settings');
					?>
					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th colspan="2"><?php _e ('API Connection Handler', 'oa_social_login'); ?>
							</th>
						</tr>
						<?php
							$api_connection_handler = ((empty ($settings ['api_connection_handler']) OR $settings ['api_connection_handler'] <> 'fsockopen') ? 'curl' : 'fsockopen');
						?>
						<tr class="row_even">
							<td rowspan="2" class="row_multi" style="width:200px">
								<label><?php _e ('API Connection Handler', 'oa_social_login'); ?>:</label>
							</td>
							<td>
								<input type="radio" id="oa_social_login_api_connection_handler_curl" name="oa_social_login_settings[api_connection_handler]" value="curl" <?php echo (($api_connection_handler <> 'fsockopen') ? 'checked="checked"' : ''); ?> />
								<label for="oa_social_login_api_connection_handler_curl"><?php _e ('Use PHP CURL to communicate with the API', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<span class="description"><?php _e ('Using CURL is recommended but it might be disabled on some servers.', 'oa_social_login'); ?></span>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<input type="radio" id="oa_social_login_api_connection_handler_fsockopen" name="oa_social_login_settings[api_connection_handler]" value="fsockopen" <?php echo (($api_connection_handler == 'fsockopen') ? 'checked="checked"' : ''); ?> />
								<label for="oa_social_login_api_connection_handler_fsockopen"><?php _e ('Use PHP FSOCKOPEN to communicate with the API', 'oa_social_login'); ?> </label><br />
								<span class="description"><?php _e ('Try using FSOCKOPEN if you encounter any problems with CURL.', 'oa_social_login'); ?></span>
							</td>
						</tr>
						<?php
							$api_connection_use_https = ((!isset ($settings ['api_connection_use_https']) OR $settings ['api_connection_use_https'] == '1') ? true : false);
						?>
						<tr class="row_even">
							<td rowspan="2" class="row_multi" style="width:200px">
								<label><?php _e ('API Connection Port', 'oa_social_login'); ?>:</label>
							</td>
							<td>
								<input type="radio" id="oa_social_login_api_connection_handler_use_https_1" name="oa_social_login_settings[api_connection_use_https]" value="1" <?php echo ($api_connection_use_https ? 'checked="checked"' : ''); ?> />
								<label for="oa_social_login_api_connection_handler_use_https_1"><?php _e ('Communication via HTTPS on port 443', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<span class="description"><?php _e ('Using port 443 is secure but you might need OpenSSL', 'oa_social_login'); ?></span>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<input type="radio" id="oa_social_login_api_connection_handler_use_https_0" name="oa_social_login_settings[api_connection_use_https]" value="0" <?php echo (!$api_connection_use_https ? 'checked="checked"' : ''); ?> />
								<label for="oa_social_login_api_connection_handler_use_https_0"><?php _e ('Communication via HTTP on port 80', 'oa_social_login'); ?> </label><br />
								<span class="description"><?php _e ("Using port 80 is a bit faster, doesn't need OpenSSL but is less secure", 'oa_social_login'); ?></span>
							</td>
						</tr>
						<tr class="row_foot">
							<td>
								<a class="button-primary" id="oa_social_login_autodetect_api_connection_handler" href="#"><?php _e ('Autodetect API Connection', 'oa_social_login'); ?></a>
							</td>
							<td>
								<div id="oa_social_login_api_connection_handler_result"></div>
							</td>
						</tr>
					</table>
					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th>
								<?php _e ('API Settings', 'oa_social_login'); ?>
							</th>
							<th><a href="https://app.oneall.com/applications/" target="_blank"><?php _e ('Click here to create and view your API Credentials', 'oa_social_login'); ?></a>
							</th>
						</tr>
						<tr class="row_even">
							<td style="width:200px">
								<label for="oa_social_login_settings_api_subdomain"><?php _e ('API Subdomain', 'oa_social_login'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_social_login_settings_api_subdomain" name="oa_social_login_settings[api_subdomain]" size="65" value="<?php echo (isset ($settings ['api_subdomain']) ? htmlspecialchars ($settings ['api_subdomain']) : ''); ?>" />
							</td>
						</tr>
						<tr class="row_odd">
							<td style="width:200px">
								<label for="oa_social_login_settings_api_key"><?php _e ('API Public Key', 'oa_social_login'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_social_login_settings_api_key" name="oa_social_login_settings[api_key]" size="65" value="<?php echo (isset ($settings ['api_key']) ? htmlspecialchars ($settings ['api_key']) : ''); ?>" />
							</td>
						</tr>
						<tr class="row_even">
							<td style="width:200px">
								<label for="oa_social_login_settings_api_secret"><?php _e ('API Private Key', 'oa_social_login'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_social_login_settings_api_secret" name="oa_social_login_settings[api_secret]" size="65" value="<?php echo (isset ($settings ['api_secret']) ? htmlspecialchars ($settings ['api_secret']) : ''); ?>" />
							</td>
						</tr>
						<tr class="row_foot">
							<td>
								<a class="button-primary" id="oa_social_login_test_api_settings" href="#"><?php _e ('Verify API Settings', 'oa_social_login'); ?> </a>
							</td>
							<td>
								<div id="oa_social_login_api_test_result"></div>
							</td>
						</tr>
					</table>

					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th colspan="2"><?php _e ('JavaScript Settings', 'oa_social_login'); ?>
							</th>
						</tr>
						<?php

						  //We dont have a value yet
						  if ( ! isset ($settings['asynchronous_javascript']))
						  {
						    //No subdomain, this is probably a new installation.
						    if ( ! isset ($settings ['api_subdomain']))
						    {
						      //Enable asynchronous JavaScript.
						      $asynchronous_javascript = 1;
						    }
						    //We have a subdomain, this is probably an updated version of the plugin.
						    else
						    {
						      //Disable asynchronous JavaScript.
						      $asynchronous_javascript = 0;
						    }
						  }
						  //We have a value.
						  else
						  {
						    $asynchronous_javascript = ( ! empty ($settings ['asynchronous_javascript']) ? 1 : 0);
						  }

						?>
						<tr class="row_even">
							<td rowspan="2" class="row_multi" style="width:200px">
								<label><?php _e ('JavaScript Usage', 'oa_social_login'); ?>:</label>
							</td>
							<td>
								<input type="radio" id="oa_social_login_asynchronous_javascript_1" name="oa_social_login_settings[asynchronous_javascript]" value="1" <?php echo ( ! empty ($asynchronous_javascript) ? 'checked="checked"' : ''); ?> />
								<label for="oa_social_login_asynchronous_javascript_1"><?php _e ('Asynchronous JavaScript', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<span class="description"><?php _e ('Background loading without interfering with the display and behavior of the existing page.', 'oa_social_login'); ?></span>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<input type="radio" id="oa_social_login_asynchronous_javascript_0" name="oa_social_login_settings[asynchronous_javascript]" value="0" <?php echo (empty ($asynchronous_javascript) ? 'checked="checked"' : ''); ?> />
								<label for="oa_social_login_asynchronous_javascript_0"><?php _e ('Synchronous JavaScript', 'oa_social_login'); ?> </label><br />
								<span class="description"><?php _e ('Real-time loading when the page is being rendered by the browser.', 'oa_social_login'); ?></span>
							</td>
						</tr>
					</table>

					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th colspan="2">
								<?php _e ('Enable the social networks/identity providers of your choice', 'oa_social_login'); ?>
							</th>
						</tr>
						<?php
							$i = 0;
							foreach ($oa_social_login_providers AS $key => $provider_data)
							{
								?>
									<tr class="row_provider <?php echo ((($i++) % 2) == 0) ? 'row_even' : 'row_odd' ?>">
										<td class="cell_provider_icon">
											<label for="oneall_social_login_provider_<?php echo $key; ?>">
											  <span class="oa_social_login_provider oa_social_login_provider_<?php echo $key; ?>" title="<?php echo htmlspecialchars ($provider_data ['name']); ?>"><?php echo htmlspecialchars ($provider_data ['name']); ?> </span>
											 </label>
										</td>
										<td class="cell_provider_label">
											<input type="checkbox" id="oneall_social_login_provider_<?php echo $key; ?>" name="oa_social_login_settings[providers][<?php echo $key; ?>]" value="1" <?php checked ('1', ((isset ($settings ['providers']) && !empty ($settings ['providers'] [$key])) ? $settings ['providers'] [$key] : 0)); ?> />
											<label for="oneall_social_login_provider_<?php echo $key; ?>"><?php echo htmlspecialchars ($provider_data ['name']); ?> </label>
											<?php
													if (in_array ($key, array ('vkontakte', 'mailru', 'odnoklassniki')))
													{
														echo ' - ' . sprintf (__ ('To enable cyrillic usernames, you might need <a target="_blank" href="%s">this plugin</a>', 'oa_social_login'), 'http://wordpress.org/extend/plugins/wordpress-special-characters-in-usernames/');
													}
											?>
										</td>
									</tr>
								<?php
							}
						?>
					</table>
					<p class="submit">
						<input type="hidden" name="page" value="setup" />
						<input type="submit" class="button-primary" value="<?php _e ('Save Changes', 'oa_social_login') ?>" />
					</p>
				</form>
			</div>
		</div>
	<?php
}


/**
 * Display Settings Page
 **/
function oa_display_social_login_settings ()
{
	?>
		<div class="wrap">
			<div id="oa_social_login_page" class="oa_social_login_settings">
				<h2>OneAll Social Login <?php echo (defined ('OA_SOCIAL_LOGIN_VERSION') ? OA_SOCIAL_LOGIN_VERSION : ''); ?></h2>
				<h2 class="nav-tab-wrapper">
         			<a class="nav-tab" href="admin.php?page=oa_social_login_setup"><?php _e ('Setup', 'oa_social_login'); ?></a>
          			<a class="nav-tab nav-tab-active" href="admin.php?page=oa_social_login_settings"><?php _e ('Settings', 'oa_social_login'); ?></a>
          			<a class="nav-tab" href="admin.php?page=oa_social_login_more"><?php _e ('+More', 'oa_social_login'); ?></a>
        		</h2>
        <p></p>
				<form method="post" action="options.php">
					<?php
						settings_fields ('oa_social_login_settings_group');
						$settings = get_option ('oa_social_login_settings');
					?>
					<div class="oa_social_login_box" id="oa_social_login_box_help">
						<div class="oa_social_login_box_title">
							<?php _e ('Logout to see the plugin in action!', 'oa_social_login'); ?>
						</div>
						<p>
							<?php
								_e ('Social Login is a plugin that allows your users to comment, login and register with their existing Social Network accounts. If a user is already logged in, the plugin will not be displayed. There is no need to give the user the possibility to connect with a social network if he is already connected.', 'oa_social_login');
							?>
							<strong><?php _e ('You therefore have to logout to see the plugin in action.', 'oa_social_login'); ?> </strong>
						</p>
					</div>
					<?php
						if (!empty ($_REQUEST ['settings-updated']) AND strtolower ($_REQUEST ['settings-updated']) == 'true')
						{
							?>
								<div class="oa_social_login_box" id="oa_social_login_box_updated">
									<?php _e ('Your modifications have been saved successfully!', 'oa_social_login'); ?>
								</div>
							<?php
						}
					?>
					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th>
								<?php _e ('General Settings', 'oa_social_login'); ?>
							</th>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Enter the description to be displayed above the Social Login buttons (leave empty for none):', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_caption = (isset ($settings ['plugin_caption']) ? $settings ['plugin_caption'] : __ ('Connect with:', 'oa_social_login'));
								?>
								<input type="text" name="oa_social_login_settings[plugin_caption]" size="90" value="<?php echo htmlspecialchars ($plugin_caption); ?>" />
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ("Please select the icon theme to use per default:", 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
							    <?php
							        $plugin_icon_theme = ((isset ($settings ['plugin_icon_theme']) AND in_array ($settings ['plugin_icon_theme'], array (0, 1, 2))) ? $settings ['plugin_icon_theme'] : 0);
							     ?>
							     <input type="radio" id="plugin_icon_theme_0" name="oa_social_login_settings[plugin_icon_theme]" value="0" <?php echo ($plugin_icon_theme == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_icon_theme_0"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../assets/img/theme_classic.png' ?>" alt="<?php _e('Classic', 'oa_social_login'); ?>" /></label>
							     <input type="radio" id="plugin_icon_theme_1" name="oa_social_login_settings[plugin_icon_theme]" value="1" <?php echo ($plugin_icon_theme == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_icon_theme_1"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../assets/img/theme_modern.png' ?>" alt="<?php _e('Modern', 'oa_social_login'); ?>" /></label>
							     <input type="radio" id="plugin_icon_theme_2" name="oa_social_login_settings[plugin_icon_theme]" value="2" <?php echo ($plugin_icon_theme == 2 ? 'checked="checked"' : ''); ?> /> <label for="plugin_icon_theme_2"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../assets/img/theme_small.png' ?>" alt="<?php _e('Small', 'oa_social_login'); ?>" /></label>
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Do you want to display the social networks used to connect in the user list of the administration area ?', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_add_column_user_list = ((isset ($settings ['plugin_add_column_user_list']) AND in_array ($settings ['plugin_add_column_user_list'], array (0, 1))) ? $settings ['plugin_add_column_user_list'] : 0);
								?>
								<input type="radio" id="plugin_add_column_user_list_1" name="oa_social_login_settings[plugin_add_column_user_list]" value="1" <?php echo ($plugin_add_column_user_list == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_add_column_user_list_1"><?php _e ('Yes, add a new column to the user list and display the social network that the user connected with', 'oa_social_login'); ?></label> <br />
								<input type="radio" id="plugin_add_column_user_list_0" name="oa_social_login_settings[plugin_add_column_user_list]" value="0" <?php echo ($plugin_add_column_user_list == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_add_column_user_list_0"><?php _e ('No, do not display the social networks in the user list', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label>
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Do you want to receive an email whenever a new user registers with Social Login ?', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_notify_admin = ((isset ($settings ['plugin_notify_admin']) AND in_array ($settings ['plugin_notify_admin'], array (0, 1))) ? $settings ['plugin_notify_admin'] : 1);
								?>
								<input type="radio" id="plugin_notify_admin_1" name="oa_social_login_settings[plugin_notify_admin]" value="1" <?php echo ($plugin_notify_admin == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_notify_admin_1"><?php _e ('Yes, send me an email whenever a new user registers with Social Login', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_notify_admin_0" name="oa_social_login_settings[plugin_notify_admin]" value="0" <?php echo ($plugin_notify_admin == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_notify_admin_0"><?php _e ('No, do not send me any emails', 'oa_social_login'); ?></label>
							</td>
						</tr>
					</table>
					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th>
								<?php _e ('User Settings', 'oa_social_login'); ?>
							</th>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ("If the user's social network profile has no email address, should we ask the user to enter it manually?", 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_require_email = ((isset ($settings ['plugin_require_email']) AND in_array ($settings ['plugin_require_email'], array (0, 1))) ? $settings ['plugin_require_email'] : 0);
								?>
								<input type="radio" id="plugin_require_email_0" name="oa_social_login_settings[plugin_require_email]" value="0" <?php echo ($plugin_require_email == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_require_email_0"><?php _e ('No, simplify the registration by automatically creating a placeholder email', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_require_email_1" name="oa_social_login_settings[plugin_require_email]" value="1" <?php echo ($plugin_require_email == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_require_email_1"><?php _e ('Yes, require the user to enter his email address manually and display this message:', 'oa_social_login'); ?></label> <br />
								<textarea name="oa_social_login_settings[plugin_require_email_text]" cols="100" rows="3"><?php echo (isset ($settings ['plugin_require_email_text']) ? htmlspecialchars ($settings ['plugin_require_email_text']) : _e ('<strong>We unfortunately could not retrieve your email address from %s.</strong> Please enter your email address in the form below in order to continue.', 'oa_social_login')); ?></textarea>
								<span class="description"><?php _e ('HTML is allowed, the placeholder %s is replaced by the name of the social network used to connect.', 'oa_social_login'); ?></span>
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ("If the user's social network profile has a verified email, should we try to link it to an existing account?", 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_link_verified_accounts = ((isset ($settings ['plugin_link_verified_accounts']) AND in_array ($settings ['plugin_link_verified_accounts'], array (0, 1))) ? $settings ['plugin_link_verified_accounts'] : 1);
								?>
								<input type="radio" id="plugin_link_verified_accounts_1" name="oa_social_login_settings[plugin_link_verified_accounts]" value="1" <?php echo ($plugin_link_verified_accounts == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_link_verified_accounts_1"><?php _e ('Yes, try to link verified social network profiles to existing blog accounts', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_link_verified_accounts_0" name="oa_social_login_settings[plugin_link_verified_accounts]" value="0" <?php echo ($plugin_link_verified_accounts == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_link_verified_accounts_0"><?php _e ('No, disable account linking', 'oa_social_login'); ?></label>
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ("If the user's social network profile has an avatar, should this avatar be used as default avatar for the user?", 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_show_avatars_in_comments = ((isset ($settings ['plugin_show_avatars_in_comments']) AND in_array ($settings ['plugin_show_avatars_in_comments'], array (0, 1, 2))) ? $settings ['plugin_show_avatars_in_comments'] : 0);
								?>
								<input type="radio" id="plugin_show_avatars_in_comments_0" name="oa_social_login_settings[plugin_show_avatars_in_comments]" value="0" <?php echo ($plugin_show_avatars_in_comments == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_show_avatars_in_comments_0"><?php _e ('No, do not use avatars from social networks', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_show_avatars_in_comments_1" name="oa_social_login_settings[plugin_show_avatars_in_comments]" value="1" <?php echo ($plugin_show_avatars_in_comments == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_show_avatars_in_comments_1"><?php _e ('Yes, use small avatars from social networks if available', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_show_avatars_in_comments_2" name="oa_social_login_settings[plugin_show_avatars_in_comments]" value="2" <?php echo ($plugin_show_avatars_in_comments == 2 ? 'checked="checked"' : ''); ?> /> <label for="plugin_show_avatars_in_comments_2"><?php _e ('Yes, use large avatars from social networks if available', 'oa_social_login'); ?></label>
							</td>
						</tr>
					</table>
					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th>
								<?php _e ('Comment Settings', 'oa_social_login'); ?>
							</th>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ("Show the Social Login buttons in the comment area?", 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_comment_show = ((isset ($settings ['plugin_comment_show']) AND in_array ($settings ['plugin_comment_show'], array (0, 1))) ? $settings ['plugin_comment_show'] : 1);
								?>
								<input type="radio" id="plugin_comment_show_1" name="oa_social_login_settings[plugin_comment_show]" value="1" <?php echo ($plugin_comment_show == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_comment_show_1"><?php _e ('Yes, show the Social Login buttons', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_comment_show_0" name="oa_social_login_settings[plugin_comment_show]" value="0" <?php echo ($plugin_comment_show == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_comment_show_0"><?php _e ('No, do not show the Social Login buttons', 'oa_social_login'); ?></label><br />
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ("Show the Social Login buttons in the comment area if comments are disabled for guests?", 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_comment_show_if_members_only = ((isset ($settings ['plugin_comment_show_if_members_only']) AND in_array ($settings ['plugin_comment_show_if_members_only'], array (0, 1))) ? $settings ['plugin_comment_show_if_members_only'] : 1);
								?>
								<span class="description"><?php _e ('The buttons will be displayed below the "You must be logged in to leave a comment" notice.'); ?> </span><br />
								<input type="radio" id="plugin_comment_show_if_members_only_1" name="oa_social_login_settings[plugin_comment_show_if_members_only]" value="1" <?php echo ($plugin_comment_show_if_members_only == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_comment_show_if_members_only_1"><?php _e ('Yes, show the Social Login buttons', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_comment_show_if_members_only_0" name="oa_social_login_settings[plugin_comment_show_if_members_only]" value="0" <?php echo ($plugin_comment_show_if_members_only == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_comment_show_if_members_only_0"><?php _e ('No, do not show the Social Login buttons', 'oa_social_login'); ?></label>
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ("Automatically approve comments left by users that connected by using Social Login?", 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_comment_auto_approve = ((isset ($settings ['plugin_comment_auto_approve']) AND in_array ($settings ['plugin_comment_auto_approve'], array (0, 1))) ? $settings ['plugin_comment_auto_approve'] : 0);
								?>
								<input type="radio" id="plugin_comment_auto_approve_1" name="oa_social_login_settings[plugin_comment_auto_approve]" value="1" <?php echo ($plugin_comment_auto_approve == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_comment_auto_approve_1"><?php _e ('Yes, automatically approve comments made by users that connected with Social Login', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_comment_auto_approve_0" name="oa_social_login_settings[plugin_comment_auto_approve]" value="0" <?php echo ($plugin_comment_auto_approve == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_comment_auto_approve_0"><?php _e ('No, do not automatically approve', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</label>
							</strong><br />
							</td>
						</tr>
					</table>

					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th>
								<?php _e ('Profile Settings', 'oa_social_login'); ?>
							</th>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ("Show the Social Link buttons in the user profile?", 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<span class="description"><?php _e ('Keep this option enabled to allow each user to connect multiple social networks to his own profile.'); ?> </span><br />
								<?php
									$plugin_profile_show = ((isset ($settings ['plugin_profile_show']) AND in_array ($settings ['plugin_profile_show'], array (0, 1))) ? $settings ['plugin_profile_show'] : 1);
								?>
								<input type="radio" id="plugin_profile_show_1" name="oa_social_login_settings[plugin_profile_show]" value="1" <?php echo ($plugin_profile_show == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_profile_show_1"><?php _e ('Yes, show the Social Link buttons', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_profile_show_0" name="oa_social_login_settings[plugin_profile_show]" value="0" <?php echo ($plugin_profile_show == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_profile_show_0"><?php _e ('No, do not show the Social Link buttons', 'oa_social_login'); ?></label><br />
							</td>
						</tr>
					</table>
					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th>
								<?php _e ('Login Page Settings', 'oa_social_login'); ?>
							</th>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Do you want to display Social Login on the login form of your blog?', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_display_in_login_form = ((isset ($settings ['plugin_display_in_login_form']) AND in_array ($settings ['plugin_display_in_login_form'], array (0, 1))) ? $settings ['plugin_display_in_login_form'] : 1);
								?>
								<input type="radio" id="plugin_display_in_login_form_1" name="oa_social_login_settings[plugin_display_in_login_form]" value="1" <?php echo ($plugin_display_in_login_form == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_display_in_login_form_1"><?php _e ('Yes, display the social network buttons below the login form', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_display_in_login_form_0" name="oa_social_login_settings[plugin_display_in_login_form]" value="0" <?php echo ($plugin_display_in_login_form == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_display_in_login_form_0"><?php _e ('No, disable social network buttons in the login form', 'oa_social_login'); ?></label>
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Where should users be redirected to after having logged in with Social Login on the login page?', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_login_form_redirect = ((isset ($settings ['plugin_login_form_redirect']) AND in_array ($settings ['plugin_login_form_redirect'], array ('current','homepage','dashboard','custom'))) ? $settings ['plugin_login_form_redirect'] : 'homepage');
									$plugin_login_form_redirect_custom_url = (isset ($settings ['plugin_login_form_redirect_custom_url']) ? $settings ['plugin_login_form_redirect_custom_url'] : '');
								?>
								<input type="radio" id="plugin_login_form_redirect_current" name="oa_social_login_settings[plugin_login_form_redirect]" value="current" <?php echo ($plugin_login_form_redirect == 'current' ? 'checked="checked"' : ''); ?> /> <label for="plugin_login_form_redirect_current"><?php _e ('Redirect users back to the current page', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_login_form_redirect_homepage" name="oa_social_login_settings[plugin_login_form_redirect]" value="homepage" <?php echo ($plugin_login_form_redirect == 'homepage' ? 'checked="checked"' : ''); ?> /> <label for="plugin_login_form_redirect_homepage"><?php _e ('Redirect users to the homepage of my blog', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_login_form_redirect_dashboard" name="oa_social_login_settings[plugin_login_form_redirect]" value="dashboard" <?php echo ($plugin_login_form_redirect == 'dashboard' ? 'checked="checked"' : ''); ?> /> <label for="plugin_login_form_redirect_dashboard"><?php _e ('Redirect users to their account dashboard', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_login_form_redirect_custom" name="oa_social_login_settings[plugin_login_form_redirect]" value="custom" <?php echo ($plugin_login_form_redirect == 'custom' ? 'checked="checked"' : ''); ?> /> <label for="plugin_login_form_redirect_custom"><?php _e ('Redirect users to the following url', 'oa_social_login'); ?>:</label><br />
								<input type="text" name="oa_social_login_settings[plugin_login_form_redirect_custom_url]" size="90" value="<?php echo htmlspecialchars ($plugin_login_form_redirect_custom_url); ?>" />
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Allow other plugins to change the redirection url that you have chosen by using a hook/filter?', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_protect_login_redirect_url = (empty ($settings ['plugin_protect_login_redirect_url']) ? 0 : 1);
								?>
								<input type="radio" id="plugin_protect_login_redirect_url_0" name="oa_social_login_settings[plugin_protect_login_redirect_url]" value="0" <?php echo ($plugin_protect_login_redirect_url == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_protect_login_redirect_url_0"><?php _e ('Yes, allow plugins to change the redirection url', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_protect_login_redirect_url_1" name="oa_social_login_settings[plugin_protect_login_redirect_url]" value="1" <?php echo ($plugin_protect_login_redirect_url == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_protect_login_redirect_url_1"><?php _e ('No, protect the redirection url (Use this option if the redirection does not work correctly)', 'oa_social_login'); ?></label>
							</td>
						</tr>



					</table>
					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th>
								<?php _e ('Registration Page Settings', 'oa_social_login'); ?>
							</th>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Do you want to display Social Login on the registration form of your blog?', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_display_in_registration_form = ((isset ($settings ['plugin_display_in_registration_form']) AND in_array ($settings ['plugin_display_in_registration_form'], array (0, 1))) ? $settings ['plugin_display_in_registration_form'] : 1);
								?>
								<input type="radio" id="plugin_display_in_registration_form_1" name="oa_social_login_settings[plugin_display_in_registration_form]" value="1" <?php echo ($plugin_display_in_registration_form == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_display_in_registration_form_1"><?php _e ('Yes, display the social network buttons below the registration form', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_display_in_registration_form_0" name="oa_social_login_settings[plugin_display_in_registration_form]" value="0" <?php echo ($plugin_display_in_registration_form == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_display_in_registration_form_0"><?php _e ('No, disable social network buttons in the registration form', 'oa_social_login'); ?></label>
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Where should users be redirected to after having registered with Social Login on the registration page?', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_registration_form_redirect = ((isset ($settings ['plugin_registration_form_redirect']) AND in_array ($settings ['plugin_registration_form_redirect'], array ('current','homepage','dashboard','custom'))) ? $settings ['plugin_registration_form_redirect'] : 'dashboard');
									$plugin_registration_form_redirect_custom_url = (isset ($settings ['plugin_registration_form_redirect_custom_url']) ? $settings ['plugin_registration_form_redirect_custom_url'] : '');
								?>
								<input type="radio" id="plugin_registration_form_redirect_current" name="oa_social_login_settings[plugin_registration_form_redirect]" value="current" <?php echo ($plugin_registration_form_redirect == 'current' ? 'checked="checked"' : ''); ?> /> <label for="plugin_registration_form_redirect_current"><?php _e ('Redirect users back to the current page', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_registration_form_redirect_homepage" name="oa_social_login_settings[plugin_registration_form_redirect]" value="homepage" <?php echo ($plugin_registration_form_redirect == 'homepage' ? 'checked="checked"' : ''); ?> /> <label for="plugin_registration_form_redirect_homepage"><?php _e ('Redirect users to the homepage of my blog', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_registration_form_redirect_dashboard" name="oa_social_login_settings[plugin_registration_form_redirect]" value="dashboard" <?php echo ($plugin_registration_form_redirect == 'dashboard' ? 'checked="checked"' : ''); ?> /> <label for="plugin_registration_form_redirect_dashboard"><?php _e ('Redirect users to their account dashboard', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_registration_form_redirect_custom" name="oa_social_login_settings[plugin_registration_form_redirect]" value="custom" <?php echo ($plugin_registration_form_redirect == 'custom' ? 'checked="checked"' : ''); ?> /> <label for="plugin_registration_form_redirect_custom"><?php _e ('Redirect users to the following url', 'oa_social_login'); ?>:</label><br />
								<input type="text" name="oa_social_login_settings[plugin_registration_form_redirect_custom_url]" size="90" value="<?php echo htmlspecialchars ($plugin_registration_form_redirect_custom_url); ?>" />
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Allow other plugins to change the redirection url that you have chosen by using a hook/filter?', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_protect_registration_redirect_url = (empty ($settings ['plugin_protect_registration_redirect_url']) ? 0 : 1);
								?>
								<input type="radio" id="plugin_protect_registration_redirect_url_0" name="oa_social_login_settings[plugin_protect_registration_redirect_url]" value="0" <?php echo ($plugin_protect_registration_redirect_url == 0 ? 'checked="checked"' : ''); ?> /> <label for="plugin_protect_registration_redirect_url_0"><?php _e ('Yes, allow plugins to change the redirection url', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_protect_registration_redirect_url_1" name="oa_social_login_settings[plugin_protect_registration_redirect_url]" value="1" <?php echo ($plugin_protect_registration_redirect_url == 1 ? 'checked="checked"' : ''); ?> /> <label for="plugin_protect_registration_redirect_url_1"><?php _e ('No, protect the redirection url (Use this option if the redirection does not work correctly)', 'oa_social_login'); ?></label>
							</td>
						</tr>
					</table>
					<table class="form-table oa_social_login_table">
						<tr class="row_head">
							<th>
								<?php _e ('Widget &amp; Shortcode Settings', 'oa_social_login'); ?>
							</th>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Redirect users to this page after they have logged in with Social Login embedded by Widget/Shortcode:', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_shortcode_login_redirect = ((isset ($settings ['plugin_shortcode_login_redirect']) AND in_array ($settings ['plugin_shortcode_login_redirect'], array ('current', 'dashboard', 'homepage', 'custom'))) ? $settings ['plugin_shortcode_login_redirect'] : 'current');
									$plugin_shortcode_login_redirect_url = (isset ($settings ['plugin_shortcode_login_redirect_url']) ? $settings ['plugin_shortcode_login_redirect_url'] : '');
								?>
								<input type="radio" id="plugin_shortcode_login_redirect_current" name="oa_social_login_settings[plugin_shortcode_login_redirect]" value="current" <?php echo ($plugin_shortcode_login_redirect == 'current' ? 'checked="checked"' : ''); ?> /> <label for="plugin_shortcode_login_redirect_current"><?php _e ('Redirect users back to the current page', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_shortcode_login_redirect_homepage" name="oa_social_login_settings[plugin_shortcode_login_redirect]" value="homepage" <?php echo ($plugin_shortcode_login_redirect == 'homepage' ? 'checked="checked"' : ''); ?> /> <label for="plugin_shortcode_login_redirect_homepage"><?php _e ('Redirect users to the homepage of my blog', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_shortcode_login_redirect_dashboard" name="oa_social_login_settings[plugin_shortcode_login_redirect]" value="dashboard" <?php echo ($plugin_shortcode_login_redirect == 'dashboard' ? 'checked="checked"' : ''); ?> /> <label for="plugin_shortcode_login_redirect_dashboard"><?php _e ('Redirect users to their account dashboard', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_shortcode_login_redirect_custom" name="oa_social_login_settings[plugin_shortcode_login_redirect]" value="custom" <?php echo ($plugin_shortcode_login_redirect == 'custom' ? 'checked="checked"' : ''); ?> /> <label for="plugin_shortcode_login_redirect_custom"><?php _e ('Redirect users to the following url', 'oa_social_login'); ?>:</label><br />
								<input type="text" name="oa_social_login_settings[plugin_shortcode_login_redirect_url]" size="90" value="<?php echo htmlspecialchars ($plugin_shortcode_login_redirect_url); ?>" />
							</td>
						</tr>
						<tr class="row_odd">
							<td>
								<strong><?php _e ('Redirect users to this page after they have registered with Social Login embedded by Widget/Shortcode:', 'oa_social_login'); ?></strong>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<?php
									$plugin_shortcode_register_redirect = ((isset ($settings ['plugin_shortcode_register_redirect']) AND in_array ($settings ['plugin_shortcode_register_redirect'], array ('current', 'dashboard', 'homepage', 'custom'))) ? $settings ['plugin_shortcode_register_redirect'] : 'current');
									$plugin_shortcode_register_redirect_url = (isset ($settings ['plugin_shortcode_register_redirect_url']) ? $settings ['plugin_shortcode_register_redirect_url'] : '');
								?>
								<input type="radio" id="plugin_shortcode_register_redirect_current" name="oa_social_login_settings[plugin_shortcode_register_redirect]" value="current" <?php echo ($plugin_shortcode_register_redirect == 'current' ? 'checked="checked"' : ''); ?> /> <label for="plugin_shortcode_register_redirect_current"><?php _e ('Redirect users back to the current page', 'oa_social_login'); ?> <strong>(<?php _e ('Default', 'oa_social_login') ?>)</strong></label><br />
								<input type="radio" id="plugin_shortcode_register_redirect_homepage" name="oa_social_login_settings[plugin_shortcode_register_redirect]" value="homepage" <?php echo ($plugin_shortcode_register_redirect == 'homepage' ? 'checked="checked"' : ''); ?> /> <label for="plugin_shortcode_register_redirect_homepage"><?php _e ('Redirect users to the homepage of my blog', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_shortcode_register_redirect_dashboard" name="oa_social_login_settings[plugin_shortcode_register_redirect]" value="dashboard" <?php echo ($plugin_shortcode_register_redirect == 'dashboard' ? 'checked="checked"' : ''); ?> /> <label for="plugin_shortcode_register_redirect_dashboard"><?php _e ('Redirect users to their account dashboard', 'oa_social_login'); ?></label><br />
								<input type="radio" id="plugin_shortcode_register_redirect_custom" name="oa_social_login_settings[plugin_shortcode_register_redirect]" value="custom" <?php echo ($plugin_shortcode_register_redirect == 'custom' ? 'checked="checked"' : ''); ?> /> <label for="plugin_shortcode_register_redirect_custom"><?php _e ('Redirect users to the following url', 'oa_social_login'); ?>:</label><br />
								<input type="text" name="oa_social_login_settings[plugin_shortcode_register_redirect_url]" size="90" value="<?php echo htmlspecialchars ($plugin_shortcode_register_redirect_url); ?>" />
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="hidden" name="page" value="settings" />
						<input type="submit" class="button-primary" value="<?php _e ('Save Changes', 'oa_social_login') ?>" />
					</p>
				</form>
			</div>
		</div>
	<?php
}